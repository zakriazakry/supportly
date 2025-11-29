<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            Log::error('Facebook account validation failed: ' . $validator->errors()->first(), ['user_id' => $request->user()->id]);
            return responseFormat($validator->errors()->first(), 422);
        }

        $user = $request->user();
        try {
            Log::info('Attempting to exchange long-lived token for user.', ['user_id' => $user->id]);
            $longLivedTokenResponse = $this->fb->exchangeLongLivedUserToken($request->access_token);
            $access_token = $longLivedTokenResponse['access_token'];
            Log::info('Successfully exchanged long-lived token for user.', ['user_id' => $user->id]);

            Log::info('Attempting to get Facebook profile for user.', ['user_id' => $user->id]);
            $profile = $this->fb->getProfile($access_token);
            Log::info('Successfully retrieved Facebook profile for user.', ['user_id' => $user->id, 'facebook_user_id' => $profile['id']]);

            $account = $user->facebookAccounts()->create([
                'facebook_user_id' => $profile['id'],
                'name' => $profile['name'],
                'image' => $profile['picture']['data']['url'],
                'access_token' => $access_token,
                'token_expires_at' => now()->addDays(60),
            ]);
            Log::info('Facebook account created successfully.', ['user_id' => $user->id, 'facebook_account_id' => $account->id]);
            return responseFormat($account, 200);
        } catch (\Exception $e) {
            Log::error('Failed to add Facebook account: ' . $e->getMessage(), ['user_id' => $user->id, 'exception' => $e]);
            return responseFormat('Failed to add Facebook account: ' . $e->getMessage(), 500);
        }
    }
}
