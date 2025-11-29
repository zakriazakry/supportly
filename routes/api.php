<?php

use App\Http\Controllers\AuthController;
use App\Services\FacebookLibsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
});
Route::post('test', function (Request $request) {
    $fb = new FacebookLibsServices();
    return responseFormat($fb->exchangeLongLivedUserToken($request->token)['access_token'], 200);
});
require_once base_path('routes/apis/user.php');
