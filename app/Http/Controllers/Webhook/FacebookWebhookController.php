<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\FacebookPage;
use App\Jobs\ProcessAutoReplyJob;
use App\Models\FacebookAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
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

        // الرد الفوري لـ Facebook (مهم جداً!)
        // يجب الرد خلال 20 ثانية وإلا سيعتبر Facebook الـ webhook فاشل
        if (!isset($data['entry'])) {
            return response("OK", 200);
        }

        // معالجة الأحداث بشكل غير متزامن
        foreach ($data['entry'] as $entry) {
            if (!isset($entry['changes'])) continue;

            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'feed') continue;

                $this->handleFeedChange($change['value'] ?? []);
            }
        }

        // الرد الفوري
        return response("OK", 200);
    }

    /**
     * معالجة تغييرات الـ Feed (التعليقات)
     */
    protected function handleFeedChange($value)
    {
        // التحقق من البيانات الأساسية
        if (!isset($value['comment_id']) || !isset($value['post_id'])) {
            Log::debug('Missing comment_id or post_id in webhook data');
            return;
        }

        $commentId = $value['comment_id'];
        $postId = $value['post_id'];
        $commentText = $value['message'] ?? '';
        $verb = $value['verb'] ?? 'add';

        // تجاهل التعليقات المحذوفة
        if ($verb === 'remove') {
            Log::debug('Comment removed, ignoring', ['comment_id' => $commentId]);
            return;
        }

        // التحقق من وجود البوست في قاعدة البيانات
        $post = Post::where('post_id', $postId)->first();
        if (!$post || !$post->enabled) {
            Log::debug('Post not found or disabled', ['post_id' => $postId]);
            return;
        }

        // التحقق من وجود الصفحة
        $page = FacebookPage::where('page_id', $post->page_id)->first();
        if (!$page) {
            Log::warning('Page not found for post', [
                'post_id' => $postId,
                'page_id' => $post->page_id
            ]);
            return;
        }

        // الحصول على معلومات المعلق
        $fromId = $value['from']['id'] ?? '';
        $fromName = $value['from']['name'] ?? '';

        if (!$fromId) {
            Log::debug('Missing commenter ID');
            return;
        }

        // تجاهل تعليقات الصفحة نفسها
        if ($fromId === $post->page_id) {
            Log::debug('Comment from page itself, ignoring', ['page_id' => $post->page_id]);
            return;
        }

        // التحقق من الاشتراك النشط للمستخدم
        $user = $page->user;
        if (!$user || !$user->hasActiveSubscription()) {
            Log::warning('User has no active subscription', [
                'user_id' => $user?->id,
                'page_id' => $page->page_id
            ]);
            return;
        }

        // إرسال الـ Job إلى الـ Queue
        try {
            // تحديد الأولوية بناءً على الباقة
            $delay = 0; // بدون تأخير افتراضياً

            if ($user->hasFeature('priority_processing')) {
                // معالجة فورية للمستخدمين ذوي الأولوية
                $delay = 0;
            } else {
                // تأخير بسيط للمستخدمين العاديين (5-10 ثواني)
                $delay = rand(5, 10);
            }
            $userCount = FacebookAccount::count();
            ProcessAutoReplyJob::dispatch(
                $postId,
                $commentId,
                $commentText,
                $fromId,
                $fromName,
                $page->page_id,
                $userCount
            )->delay(now()->addSeconds($delay));

            Log::info('Auto-reply job dispatched', [
                'comment_id' => $commentId,
                'post_id' => $postId,
                'user_id' => $user->id,
                'delay' => $delay,
                'has_priority' => $user->hasFeature('priority_processing')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch auto-reply job', [
                'comment_id' => $commentId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
