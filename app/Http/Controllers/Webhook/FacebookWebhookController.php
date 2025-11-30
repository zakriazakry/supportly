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
            if (!isset($entry['changes'])) continue;

            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'feed') continue;

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
        if (!isset($value['comment_id']) || !isset($value['post_id'])) return;

        $commentId = $value['comment_id'];
        $postId = $value['post_id'];
        $commentText = $value['message'] ?? '';
        $verb = $value['verb'] ?? 'add'; // add, edited, remove

        if ($verb === 'remove') return;

        Log::info("Processing comment", [
            'comment_id' => $commentId,
            'post_id' => $postId,
            'text' => $commentText,
        ]);

        $post = Post::where('post_id', $postId)->first();
        if (!$post || !$post->enabled) {
            Log::info("Post not found or disabled", ['post_id' => $postId]);
            return;
        }

        $page = FacebookPage::where('page_id', $post->page_id)->first();
        if (!$page) {
            Log::error("Facebook page not found", ['page_id' => $post->page_id]);
            return;
        }

        // جلب التعليق من API للحصول على PSID الصحيح
        $commentData = $this->facebookService->getPostComments($postId, $page->access_token);
        $fromId = '';
        $fromName = '';

        if (!empty($commentData['data'])) {
            foreach ($commentData['data'] as $c) {
                if ($c['id'] === $commentId) {
                    $fromId = $c['from']['id'];
                    $fromName = $c['from']['name'];
                    break;
                }
            }
        }

        if (!$fromId) {
            Log::warning("Cannot get PSID for comment", ['comment_id' => $commentId]);
            return;
        }

        // تجاهل تعليقات الصفحة نفسها
        if ($fromId === $post->page_id) return;

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
        $this->executeAutoReply($post, $page, $commentId, $fromId, $fromName);
    }

    /**
     * تنفيذ الرد التلقائي
     */
    protected function executeAutoReply($post, $page, $commentId, $fromId, $fromName = '')
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
                $replyText = $this->processTemplate($post->comment_reply_template, $fromName);

                // إضافة المنشن
                if ($post->mention_enabled) {
                    $replyText = "@[$fromId] " . $replyText;
                }

                $this->facebookService->replyToComment($commentId, $pageAccessToken, $replyText);
                Log::info("Replied to comment", [
                    'comment_id' => $commentId,
                    'reply' => $replyText,
                    'mention_enabled' => $post->mention_enabled
                ]);
            }

            // 3. إرسال رسالة خاصة
            if ($post->reply_to_private_message_enabled) {
                $privateMessage = $this->processTemplate($post->private_message_template, $fromName);
                $this->facebookService->sendPrivateMessage($commentId, $pageAccessToken, $privateMessage, $page->page_id);
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

    protected function hasExcludedKeywords($text, $excludeKeywords)
    {
        if (empty($excludeKeywords) || !is_array($excludeKeywords)) return false;

        $text = mb_strtolower($text);
        foreach ($excludeKeywords as $keyword) {
            if (!empty($keyword) && mb_stripos($text, mb_strtolower($keyword)) !== false) {
                Log::info("Comment contains excluded keyword", ['keyword' => $keyword]);
                return true;
            }
        }
        return false;
    }

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
}
