<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\FacebookPage; // تأكد أن لديك الموديل
use DB;

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
            $pages = $this->fb->getPages($account->access_token);

            $pagesData = collect($pages['data'] ?? [])->map(function ($page) use ($account) {
                $dbPage = FacebookPage::where('page_id', $page['id'])->first();
                return [
                    'page_id' => $page['id'],
                    'name' => $page['name'],
                    'image' => $page['image'],
                    'category' => $page['category'] ?? null,
                    'access_token' => $page['access_token'],
                    'tasks' => $page['tasks'] ?? [],
                    'linked' => $dbPage ? true : false
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
     * إضافة صفحة جديدة إلى قاعدة البيانات
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facebook_account_id' => 'required|exists:facebook_accounts,id',
            'page_id' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $account = $request->user()->facebookAccounts()->find($request->facebook_account_id);
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
            'page_id' => $pageData['id'],
            'name' => $pageData['name'],
            'access_token' => $pageData['access_token'], // Page Access Token
            'category' => $pageData['category'] ?? null,
        ]);

        return responseFormat($page, 201);
    }
}
