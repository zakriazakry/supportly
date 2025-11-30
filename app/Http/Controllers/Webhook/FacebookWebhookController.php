<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostReplyState;
use App\Models\FacebookPage;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    protected $facebookService;

    public function __construct(FacebookLibsServices $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    /**
     * التحقق من صحة الـ Webhook
     */
    public function verify(Request $request)
    {
        $verifyToken = env('FACEBOOK_VERIFY_TOKEN', 'test');

        if ($request->hub_verify_token === $verifyToken) {
            return response($request->hub_challenge, 200);
        }

        Log::warning('Facebook Webhook: Invalid verification token', [
            'received_token' => $request->hub_verify_token
        ]);

        return response("Invalid token", 403);
    }

    /**
     * استقبال ومعالجة أحداث الـ Webhook
     */
    public function receive(Request $request)
    {
        $data = $request->all();

        Log::info('Facebook Webhook received', ['data' => $data]);

        if (!isset($data['entry'])) {
            return response("OK", 200);
        }

        foreach ($data['entry'] as $entry) {
            if (!isset($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'feed') {
                    continue;
                }

                $this->handleFeedChange($change['value'] ?? []);
            }
        }

        return response("OK", 200);
    }

    /**
     * معالجة تغييرات الـ Feed (التعليقات)
     */
    protected function handleFeedChange($value)
    {
        if (!isset($value['comment_id']) || !isset($value['post_id'])) {
            return;
        }

        $commentId = $value['comment_id'];
        $postId = $value['post_id'];
        $commentText = $value['message'] ?? '';
        $fromId = $value['from']['id'] ?? '';
        $verb = $value['verb'] ?? 'add'; // add, edited, remove

        // تجاهل التعليقات المحذوفة
        if ($verb === 'remove') {
            return;
        }

        Log::info("Processing comment", [
            'comment_id' => $commentId,
            'post_id' => $postId,
            'text' => $commentText
        ]);

        // البحث عن إعدادات المنشور في قاعدة البيانات
        $post = Post::where('post_id', $postId)->first();

        if (!$post || !$post->enabled) {
            Log::info("Post not found or disabled", ['post_id' => $postId]);
            return;
        }

        // الحصول على معلومات الصفحة
        $page = FacebookPage::where('page_id', $post->page_id)->first();

        if (!$page) {
            Log::error("Facebook page not found", ['page_id' => $post->page_id]);
            return;
        }

        // تجاهل تعليقات الصفحة نفسها
        if ($fromId === $post->page_id) {
            return;
        }

        // التحقق من عدم تكرار الرد على نفس التعليق
        $alreadyReplied = PostReplyState::where('post_id', $post->id)
            ->where('user_id', $fromId)
            ->where('reply', $commentId)
            ->exists();

        if ($alreadyReplied) {
            Log::info("Already replied to this comment", ['comment_id' => $commentId]);
            return;
        }

        // فحص الكلمات المستبعدة
        if ($this->hasExcludedKeywords($commentText, $post->exclude_keywords)) {
            Log::info("Comment contains excluded keywords", ['comment_id' => $commentId]);
            return;
        }

        // فحص الكلمات المفتاحية المطلوبة
        if (!$this->hasRequiredKeywords($commentText, $post->keywords)) {
            Log::info("Comment doesn't contain required keywords", ['comment_id' => $commentId]);
            return;
        }

        // تنفيذ الإجراءات المطلوبة
        $this->executeAutoReply($post, $page, $commentId, $fromId);
    }

    /**
     * تنفيذ الرد التلقائي
     */
    protected function executeAutoReply($post, $page, $commentId, $fromId)
    {
        $pageAccessToken = $page->access_token;

        try {
            // 1. الإعجاب بالتعليق
            if ($post->like_comment_enabled) {
                $this->facebookService->likeComment($commentId, $pageAccessToken);
                Log::info("Liked comment", ['comment_id' => $commentId]);
            }

            // 2. الرد على التعليق
            if ($post->reply_to_comment_enabled && $post->comment_reply_template) {
                $replyText = $this->processTemplate($post->comment_reply_template);
                $this->facebookService->replyToComment($commentId, $pageAccessToken, $replyText);
                Log::info("Replied to comment", [
                    'comment_id' => $commentId,
                    'reply' => $replyText
                ]);
            }

            // 3. إرسال رسالة خاصة
            if ($post->reply_to_private_message_enabled && $post->private_message_template) {
                $privateMessage = $this->processTemplate($post->private_message_template);
                $this->facebookService->sendPrivateMessage($commentId, $pageAccessToken, $privateMessage);
                Log::info("Sent private message", [
                    'comment_id' => $commentId,
                    'message' => $privateMessage
                ]);
            }

            // حفظ حالة الرد لمنع التكرار
            PostReplyState::create([
                'post_id' => $post->id,
                'user_id' => $fromId,
                'reply' => $commentId,
                'if_has' => true
            ]);

            Log::info("Auto-reply completed successfully", ['comment_id' => $commentId]);
        } catch (\Exception $e) {
            Log::error("Error in auto-reply", [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * فحص وجود كلمات مستبعدة
     * 
     * المنطق:
     * - إذا كانت الكلمات المستبعدة فارغة: لا يتم استبعاد أي تعليق
     * - إذا كانت هناك كلمات مستبعدة: يتم تجاهل التعليقات التي تحتوي على أي منها
     * 
     * أمثلة:
     * - exclude_keywords = [] أو null → لا يستبعد أي تعليق
     * - exclude_keywords = ['شكراً', 'تم'] → يتجاهل التعليقات التي فيها "شكراً" أو "تم"
     */
    protected function hasExcludedKeywords($text, $excludeKeywords)
    {
        if (empty($excludeKeywords) || !is_array($excludeKeywords)) {
            return false;
        }

        $text = mb_strtolower($text);
        $foundExcluded = [];

        foreach ($excludeKeywords as $keyword) {
            if (empty($keyword)) {
                continue;
            }

            if (mb_stripos($text, mb_strtolower($keyword)) !== false) {
                $foundExcluded[] = $keyword;
            }
        }

        if (!empty($foundExcluded)) {
            Log::info("Comment contains excluded keywords", ['excluded' => $foundExcluded]);
            return true;
        }

        return false;
    }

    /**
     * فحص وجود الكلمات المفتاحية المطلوبة
     * 
     * المنطق:
     * - إذا كانت الكلمات المفتاحية فارغة أو null: يرد على جميع التعليقات
     * - إذا كانت هناك كلمات مفتاحية: يرد فقط على التعليقات التي تحتوي على أي كلمة منها
     * 
     * أمثلة:
     * - keywords = [] أو null → يرد على كل التعليقات
     * - keywords = ['السعر', 'الباقات'] → يرد فقط على التعليقات التي فيها "السعر" أو "الباقات"
     */
    protected function hasRequiredKeywords($text, $keywords)
    {
        // إذا لم تكن هناك كلمات مفتاحية، نقبل جميع التعليقات
        if (empty($keywords) || !is_array($keywords)) {
            Log::info("No keywords defined - accepting all comments");
            return true;
        }

        $text = mb_strtolower($text);
        $foundKeywords = [];

        foreach ($keywords as $keyword) {
            if (empty($keyword)) {
                continue;
            }

            if (mb_stripos($text, mb_strtolower($keyword)) !== false) {
                $foundKeywords[] = $keyword;
            }
        }

        if (!empty($foundKeywords)) {
            Log::info("Comment matches keywords", ['found' => $foundKeywords]);
            return true;
        }

        Log::info("Comment doesn't match any keyword", ['keywords' => $keywords]);
        return false;
    }

    /**
     * معالجة القالب واستبدال المتغيرات
     */
    protected function processTemplate($template)
    {
        // يمكن إضافة متغيرات ديناميكية هنا
        // مثل: {name}, {time}, {date}, إلخ

        $replacements = [
            '{time}' => now()->format('H:i'),
            '{date}' => now()->format('Y-m-d'),
            '{datetime}' => now()->format('Y-m-d H:i:s'),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
