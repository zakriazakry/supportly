<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FacebookAccountsController extends Controller
{
    public $fb;
    public function __construct()
    {
        $this->fb = new FacebookLibsServices();
    }
    public function index(Request $request)
    {
        $user = $request->user();
        $accounts = $user->facebookAccounts;
        return responseFormat($accounts, 200);
    }

    public function addAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'access_token' => 'required',
        ]);
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        $user = $request->user();
        $access_token = $this->fb->exchangeLongLivedUserToken($request->access_token)['access_token'];
        $profile = $this->fb->getProfile($access_token);
        $account = $user->facebookAccounts()->updateOrCreate(
            [
                'facebook_user_id' => $profile['id'],
            ],
            [
                'name' => $profile['name'],
                'image' => $profile['picture']['data']['url'],
                'access_token' => $access_token,
                'token_expires_at' => now()->addDays(60),
            ]
        );

        return responseFormat($account, 200);
    }

    public function deleteAccount(Request $request, $facebook_user_id)
    {
        $user = $request->user();
        $account = $user->facebookAccounts()->where('facebook_user_id', $facebook_user_id)->first();
        if (!$account) {
            return responseFormat('Account not found', 404);
        }
        $account->delete();
        return responseFormat('Account deleted successfully', 200);
    }
}
