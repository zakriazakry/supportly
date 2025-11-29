<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;

class PagePostsController extends Controller
{
    protected $fb;

    public function __construct()
    {
        $this->fb = new FacebookLibsServices();
    }

    /**
     * عرض جميع صفحات كل حساب
     */
    public function index(Request $request, $page_id)
    {
        $page = $request->user()->facebookPages()->find($page_id);
        if (!$page) {
            return responseFormat('الصفحة غير موجودة.', 422);
        }

        $cacheKey = 'page_posts_' . $page->page_id;
        $posts = cache()->remember($cacheKey, 60 * 10, function () use ($page) {
            return $this->fb->getPagePosts($page->page_id, $page->access_token);
        });

        return responseFormat($posts, 200);
    }
}
