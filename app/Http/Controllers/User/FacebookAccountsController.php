<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FacebookAccountsController extends Controller
{
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
        $account = $user->facebookAccounts()->create([
            'page_id' => $request->page_id,
            'page_name' => $request->page_name,
            'page_token' => $request->page_token,
        ]);
        return responseFormat($account, 200);
    }
}
