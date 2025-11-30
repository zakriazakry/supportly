<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\FacebookPage; // تأكد أن لديك الموديل
use DB;
use Illuminate\Support\Facades\Cache;

class FacebookPagesController extends Controller
{
    protected $fb;

    public function __construct()
    {
        $this->fb = new FacebookLibsServices();
    }

    /**
     * عرض جميع صفحات كل حساب
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $accounts = $user->facebookAccounts;

        $data = [];

        foreach ($accounts as $account) {
            $cacheKey = 'facebook_pages_' . $account->id;
            $pages = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($account) {
                return $this->fb->getPages($account->access_token);
            });

            $pagesData = collect($pages['data'] ?? [])->map(function ($page) use ($account) {
                $dbPage = FacebookPage::where('page_id', $page['id'])->first();
                return [
                    'page_id' => $page['id'],
                    'name' => $page['name'],
                    'image' => $page['picture']['data']['url'] ?? null, // Added null coalescing for safety
                    'category' => $page['category'] ?? null,
                    'access_token' => $page['access_token'],
                    'tasks' => $page['tasks'] ?? [],
                    'linked' => $dbPage ? true : false,
                ];
            });

            $data[] = [
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'facebook_user_id' => $account->facebook_user_id,
                    'image' => $account->image
                ],
                'pages' => $pagesData
            ];
        }

        return responseFormat($data, 200);
    }

    /**
     * عرض جميع صفحات المستخدم
     */
    public function myPages(Request $request)
    {
        $user = $request->user();
        $pages = $user->facebookPages;

        return responseFormat($pages, 200);
    }
    /**
     * إضافة صفحة جديدة إلى قاعدة البيانات
     */
    public function linkPage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:facebook_accounts,id',
            'page_id' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $user = $request->user();

        // التحقق من وجود اشتراك نشط
        if (!$user->hasActiveSubscription()) {
            return responseFormat('يجب أن يكون لديك اشتراك نشط لربط صفحات فيسبوك', 403);
        }

        // التحقق من القيود
        if (!$user->canAdd('facebook_pages')) {
            $limit = $user->getLimit('facebook_pages');
            $current = $user->facebookPages()->count();
            return responseFormat('لقد وصلت للحد الأقصى من صفحات فيسبوك المسموحة في باقتك', 403);
        }

        $account = $user->facebookAccounts()->find($request->account_id);
        if (!$account) {
            return responseFormat('الحساب غير موجود أو لا يخصك.', 422);
        }

        // تحقق إذا كانت الصفحة موجودة مسبقًا
        $exists = FacebookPage::where('page_id', $request->page_id)->first();
        if ($exists) {
            return responseFormat('هذه الصفحة موجودة بالفعل في النظام.', 422);
        }

        $pages = $this->fb->getPages($account->access_token);
        $pageData = collect($pages['data'] ?? [])->firstWhere('id', $request->page_id);

        if (!$pageData) {
            return responseFormat('الصفحة غير موجودة أو لا يمكن الوصول إليها عبر Facebook API.', 422);
        }

        $page = FacebookPage::create([
            'facebook_account_id' => $account->id,
            'user_id' => $user->id,
            'page_id' => $pageData['id'],
            'name' => $pageData['name'],
            'image' => $pageData['picture']['data']['url'] ?? '-',
            'access_token' => $pageData['access_token'], // Page Access Token
            'category' => $pageData['category'] ?? null,
        ]);

        return responseFormat($page, 201);
    }

    public function unlinkPage(Request $request, $account_id, $page_id)
    {

        $account = $request->user()->facebookAccounts()->find($account_id);
        if (!$account) {
            return responseFormat('الحساب غير موجود أو لا يخصك.', 422);
        }

        $page = FacebookPage::where('page_id', $page_id)->first();
        if (!$page) {
            return responseFormat('الصفحة غير موجودة.', 422);
        }

        $page->delete();

        return responseFormat('تم إزالة الصفحة بنجاح.', 200);
    }
}
