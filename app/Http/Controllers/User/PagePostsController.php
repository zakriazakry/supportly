<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
}
