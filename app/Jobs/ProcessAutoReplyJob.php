<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostReplyState;
use App\Models\FacebookPage;
use App\Services\FacebookLibsServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 دقيقة timeout
    public $tries = 3; // عدد المحاولات
    public $backoff = [60, 180, 300]; // الانتظار بين المحاولات (1 دقيقة، 3 دقائق، 5 دقائق)
    public $userCount = 0; // عدد المحاولات الأقصى
    protected $postId;
    protected $commentId;
    protected $commentText;
    protected $fromId;
    protected $fromName;
    protected $pageId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $postId,
        string $commentId,
        string $commentText,
        string $fromId,
        string $fromName,
        string $pageId,
        int $userCount
    ) {
        $this->postId = $postId;
        $this->commentId = $commentId;
        $this->commentText = $commentText;
        $this->fromId = $fromId;
        $this->fromName = $fromName;
        $this->pageId = $pageId;
        $this->userCount = $userCount;
        // تعيين الـ Queue بناءً على الأولوية
        $this->onQueue('auto-replies');
    }

    /**
     * Execute the job.
     */
    public function handle(FacebookLibsServices $facebookService): void
    {
        // الحصول على البوست
        $post = Post::where('post_id', $this->postId)->first();
        if (!$post || !$post->enabled) {
            Log::info("Post not found or disabled", ['post_id' => $this->postId]);
            return;
        }

        // الحصول على الصفحة
        $page = FacebookPage::where('page_id', $this->pageId)->first();
        if (!$page) {
            Log::error("Page not found", ['page_id' => $this->pageId]);
            return;
        }

        // الحصول على المستخدم
        $user = $page->user;
        if (!$user) {
            Log::error("User not found for page", ['page_id' => $this->pageId]);
            return;
        }

        // 1. التحقق من الاشتراك النشط
        if (!$user->hasActiveSubscription()) {
            Log::warning("User has no active subscription", ['user_id' => $user->id]);
            return;
        }

        // 2. التحقق من القيود الشهرية
        if (!$user->canSendAutoReply()) {
            Log::warning("User reached monthly auto-reply limit", [
                'user_id' => $user->id,
                'remaining' => $user->getRemainingAutoReplies()
            ]);
            return;
        }

        // 3. التحقق من عدم الرد المسبق
        $alreadyReplied = PostReplyState::where('post_id', $post->id)
            ->where('user_id', $this->fromId)
            ->where('reply', $this->commentId)
            ->exists();

        if ($alreadyReplied) {
            Log::info("Already replied to this comment", ['comment_id' => $this->commentId]);
            return;
        }

        // 4. التحقق من الكلمات المستبعدة
        if ($this->hasExcludedKeywords($this->commentText, $post->exclude_keywords)) {
            Log::info("Comment contains excluded keywords", ['comment_id' => $this->commentId]);
            return;
        }

        // 5. التحقق من الكلمات المطلوبة
        if (!$this->hasRequiredKeywords($this->commentText, $post->keywords)) {
            Log::info("Comment doesn't contain required keywords", ['comment_id' => $this->commentId]);
            return;
        }

        // 6. تنفيذ الرد مع Rate Limiting
        $this->executeAutoReplyWithRateLimit($post, $page, $user, $facebookService);
    }

    /**
     * تنفيذ الرد التلقائي مع مراعاة Rate Limiting
     * Rate Limit هو لكل Page Access Token (200 طلب/ساعة)
     */
    protected function executeAutoReplyWithRateLimit($post, $page, $user, $facebookService)
    {
        $pageAccessToken = $page->access_token;
        $pageId = $page->page_id;

        try {
            $rateLimitKey = "rate_limit:page:{$pageId}:" . now()->format('Y-m-d-H');
            $requestCount = Cache::get($rateLimitKey, 0);
            $maxLimit = $this->userCount * 200;
            $maxRequestsPerHour = $maxLimit * 0.7;
            if ($user->hasFeature('priority_processing')) {
                $maxRequestsPerHour = $maxLimit * 0.9;
            }
            if ($requestCount >= $maxRequestsPerHour) {
                $this->release(75 * 60);
                Log::warning("Rate limit reached for page, job delayed", [
                    'page_id' => $pageId,
                    'page_name' => $page->name,
                    'user_id' => $user->id,
                    'requests' => $requestCount,
                    'max' => $maxRequestsPerHour,
                    'delayed_until' => now()->addMinutes(75)->format('Y-m-d H:i:s')
                ]);
                return;
            }

            // تنفيذ الإجراءات
            $requestsMade = 0;

            // 1. الإعجاب بالتعليق
            if ($post->like_comment_enabled) {
                $this->waitForRateLimit();
                $facebookService->likeComment($this->commentId, $pageAccessToken);
                $requestsMade++;
                Log::info("Liked comment", [
                    'comment_id' => $this->commentId,
                    'page_id' => $pageId
                ]);
            }

            // 2. الرد على التعليق
            if ($post->reply_to_comment_enabled && $post->comment_reply_template) {
                $this->waitForRateLimit();

                $replyText = $this->processTemplate($post->comment_reply_template, $this->fromName);

                if ($post->mention_enabled) {
                    $replyText = "@[{$this->fromId}] " . $replyText;
                }

                $facebookService->replyToComment($this->commentId, $pageAccessToken, $replyText);
                $requestsMade++;
                Log::info("Replied to comment", [
                    'comment_id' => $this->commentId,
                    'page_id' => $pageId
                ]);
            }

            // 3. إرسال رسالة خاصة
            if ($post->reply_to_private_message_enabled && $post->private_message_template) {
                $this->waitForRateLimit();

                $privateMessage = $this->processTemplate($post->private_message_template, $this->fromName);
                $facebookService->sendPrivateMessage(
                    $this->commentId,
                    $pageAccessToken,
                    $privateMessage,
                    $page->page_id
                );
                $requestsMade++;
                Log::info("Sent private message", [
                    'comment_id' => $this->commentId,
                    'page_id' => $pageId
                ]);
            }

            // تحديث عداد الطلبات لهذه الصفحة (صالح لمدة 75 دقيقة)
            Cache::put($rateLimitKey, $requestCount + $requestsMade, now()->addMinutes(75));

            // حفظ حالة الرد
            PostReplyState::create([
                'post_id' => $post->id,
                'user_id' => $this->fromId,
                'reply' => $this->commentId,
                'if_has' => true
            ]);

            // تحديث عداد الردود الشهرية للمستخدم
            $user->incrementAutoReplyCount();

            Log::info("Auto-reply completed successfully", [
                'comment_id' => $this->commentId,
                'page_id' => $pageId,
                'page_name' => $page->name,
                'user_id' => $user->id,
                'requests_made' => $requestsMade,
                'total_requests_this_hour' => $requestCount + $requestsMade,
                'remaining_requests' => $maxRequestsPerHour - ($requestCount + $requestsMade)
            ]);
        } catch (\Exception $e) {
            Log::error("Error in auto-reply job", [
                'comment_id' => $this->commentId,
                'page_id' => $pageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // إعادة المحاولة
            throw $e;
        }
    }

    /**
     * انتظار صغير بين الطلبات لتجنب Rate Limiting
     */
    protected function waitForRateLimit()
    {
        // انتظار 1-2 ثانية بين كل طلب
        usleep(rand(1000000, 2000000)); // 1-2 ثانية
    }

    /**
     * التحقق من الكلمات المستبعدة
     */
    protected function hasExcludedKeywords($text, $excludeKeywords)
    {
        if (empty($excludeKeywords) || !is_array($excludeKeywords)) return false;

        $text = mb_strtolower($text);
        foreach ($excludeKeywords as $keyword) {
            if (!empty($keyword) && mb_stripos($text, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * التحقق من الكلمات المطلوبة
     */
    protected function hasRequiredKeywords($text, $keywords)
    {
        if (empty($keywords) || !is_array($keywords)) return true;

        $text = mb_strtolower($text);
        foreach ($keywords as $keyword) {
            if (!empty($keyword) && mb_stripos($text, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * معالجة القالب
     */
    protected function processTemplate($template, $userName = '')
    {
        $replacements = [
            '{name}' => $userName,
            '{time}' => now()->format('H:i'),
            '{date}' => now()->format('Y-m-d'),
            '{datetime}' => now()->format('Y-m-d H:i:s'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * معالجة الفشل
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Auto-reply job failed permanently", [
            'comment_id' => $this->commentId,
            'post_id' => $this->postId,
            'page_id' => $this->pageId,
            'error' => $exception->getMessage()
        ]);
    }
}
