<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;

class FacebookPagesController extends Controller
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
        $pages = collect([]);
        foreach ($accounts as $account) {
            $pages->push([
                'account' => $account,
                'pages' => $this->fb->getPages($account->access_token)
            ]);
        }
        return responseFormat($pages->toArray(), 200);
    }
}
