<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FacebookAccountsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $accounts = $user->facebookAccounts;
        return responseFormat($accounts, 200);
    }
}
