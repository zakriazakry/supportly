<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FacebookPage;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PagePostsController extends Controller
{
    protected $fb;

    public function __construct()
    {
        $this->fb = new FacebookLibsServices();
    }

    /**
     * عرض منشورات صفحة معينة
     */
    public function index(Request $request, $page_id)
    {
        $page = $request->user()->facebookPages()->where('page_id', $page_id)->first();

        if (!$page) {
            return responseFormat('الصفحة غير موجودة.', 422);
        }

        $rawPosts = $this->fb->getPagePosts($page->page_id, $page->access_token);

        $posts = Cache::remember('page_posts_' . $page_id, now()->addMinutes(10), function () use ($rawPosts) {
            return $this->formatPosts($rawPosts);
        });

        return responseFormat($posts, 200);
    }

    private function formatPosts($posts)
    {
        if (!isset($posts['data'])) return [];

        return array_values(collect($posts['data'])->map(function ($post) {
            return [
                'id' => $post['id'] ?? null,
                'message' => $post['message'] ?? null,
                'created_time' => $post['created_time'] ?? null,

                // الصور
                'images' => $this->extractPostImages($post),

                // عدد الإعجابات
                'likes' => $post['likes']['summary']['total_count'] ?? 0,

                // عدد التعليقات
                'comments' => $post['comments']['summary']['total_count'] ?? 0,

                // معلومات الناشر
                'from' => [
                    'id' => $post['from']['id'] ?? null,
                    'name' => $post['from']['name'] ?? null,
                ]
            ];
        })->toArray());
    }

    private function extractPostImages($post)
    {
        $images = [];

        if (!empty($post['full_picture'])) {
            $images[] = $post['full_picture'];
        }

        if (!empty($post['attachments']['data'])) {
            foreach ($post['attachments']['data'] as $att) {
                if (!empty($att['media']['image']['src'])) {
                    $images[] = $att['media']['image']['src'];
                }

                if (!empty($att['subattachments']['data'])) {
                    foreach ($att['subattachments']['data'] as $sub) {
                        if (!empty($sub['media']['image']['src'])) {
                            $images[] = $sub['media']['image']['src'];
                        }
                    }
                }
            }
        }

        return array_values(array_unique($images));
    }
    // ------------------------------
    public function getSettings(Request $request, $post_id)
    {
        $post = $request->user()->posts()->where('post_id', $post_id)->first();

        if (!$post) {
            return responseFormat('المنشور غير موجود.', 422);
        }

        return responseFormat($post, 200);
    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
            'page_id' => 'required',
            'enabled' => 'required|boolean',
            'like_comment_enabled' => 'required|boolean',
            'reply_to_comment_enabled' => 'required|boolean',
            'reply_to_private_message_enabled' => 'required|boolean',
            'mention_enabled' => 'required|boolean',
            'share_enabled' => 'required|boolean',
            'comment_reply_template' => 'nullable|string',
            'private_message_template' => 'nullable|string',
            'mention_reply_template' => 'nullable|string',
            'keywords' => 'nullable|array',
            'exclude_keywords' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        // التحقق من وجود الصفحة
        $pageExists = FacebookPage::where('page_id', $request->page_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (!$pageExists) {
            return responseFormat('الصفحة غير موجودة أو غير مملوكة لك.', 422);
        }

        // upsert باستخدام updateOrCreate
        $post = $request->user()->posts()->updateOrCreate(
            ['post_id' => $request->post_id], // شرط البحث
            [
                'page_id' => $request->page_id,
                'enabled' => $request->enabled,
                'like_comment_enabled' => $request->like_comment_enabled,
                'reply_to_comment_enabled' => $request->reply_to_comment_enabled,
                'reply_to_private_message_enabled' => $request->reply_to_private_message_enabled,
                'mention_enabled' => $request->mention_enabled,
                'share_enabled' => $request->share_enabled,
                'comment_reply_template' => $request->comment_reply_template ?? '',
                'private_message_template' => $request->private_message_template ?? '',
                'mention_reply_template' => $request->mention_reply_template ?? '',
                'keywords' => json_encode($request->keywords ?? []),
                'exclude_keywords' => json_encode($request->exclude_keywords ?? []),
            ]
        );

        return responseFormat('تم تحديث الإعدادات بنجاح.', 200);
    }
}
