<?php

use App\Http\Controllers\User\FacebookAccountsController;
use App\Http\Controllers\User\FacebookPagesController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\PagePostsController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\SupportingPagesController;
use App\Http\Controllers\User\TemeplatesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(HomeController::class)->prefix('home')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(FacebookAccountsController::class)->prefix('facebook-accounts')->group(function () {
        Route::get('', 'index');
        Route::post('add-account', 'addAccount');
        Route::delete('delete-account/{facebook_user_id}', 'deleteAccount');
    });

    Route::controller(FacebookPagesController::class)->prefix('facebook-pages')->group(function () {
        Route::get('/', 'index');
        Route::get('my-pages', 'myPages');
        Route::post('link-page', 'linkPage');
        Route::delete('unlink-page/{account_id}/{page_id}', 'unlinkPage');
    });

    Route::controller(SupportingPagesController::class)->prefix('support')->group(function () {
        Route::post('send-ticket', 'sendTicket');
    });

    Route::controller(PagePostsController::class)->prefix('page-posts')->group(function () {
        Route::get('/{page_id}', 'index');
        Route::get('get-settings/{post_id}', 'getSettings');
        Route::post('update-settings', 'updateSettings');
    });

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'index');
    });
});
