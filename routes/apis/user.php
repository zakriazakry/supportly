<?php

use App\Http\Controllers\User\FacebookAccountsController;
use App\Http\Controllers\User\FacebookPagesController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\PagePostsController;
use App\Http\Controllers\User\PackageController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\SupportingPagesController;
use App\Http\Controllers\User\TemeplatesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(HomeController::class)->prefix('home')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(FacebookAccountsController::class)->prefix('facebook-accounts')->middleware('feature:facebook')->group(function () {
        Route::get('', 'index');
        Route::post('add-account', 'addAccount')->middleware('feature:facebook,facebook_accounts');
        Route::delete('delete-account/{facebook_user_id}', 'deleteAccount');
    });

    Route::controller(FacebookPagesController::class)->prefix('facebook-pages')->middleware('feature:facebook')->group(function () {
        Route::get('/', 'index');
        Route::get('my-pages', 'myPages');
        Route::post('link-page', 'linkPage')->middleware('feature:facebook,facebook_pages');
        Route::delete('unlink-page/{account_id}/{page_id}', 'unlinkPage');
    });

    Route::controller(SupportingPagesController::class)->prefix('support')->group(function () {
        Route::get('', 'index');
        Route::post('send-ticket', 'sendTicket');
    });

    Route::controller(PagePostsController::class)->prefix('page-posts')->middleware('feature:facebook')->group(function () {
        Route::get('/{page_id}', 'index');
        Route::get('get-settings/{post_id}', 'getSettings');
        Route::post('update-settings', 'updateSettings')->middleware('feature:facebook_auto_reply');
    });


    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'index');
        Route::post('update', 'updateProfile');
        // Subscription routes
        Route::get('subscription/current', 'getCurrentSubscription');
        Route::get('subscription/history', 'getSubscriptionHistory');
        Route::post('subscription/subscribe', 'subscribe');
        Route::post('subscription/cancel', 'cancelSubscription');
        Route::get('subscription/check-limits', 'checkLimits');
        // update-password
        Route::post('update-password', 'updatePassword');
        Route::post('update-image', 'updateImage');
        // Packages routes
        Route::get('packages', 'getPackages');

        // Wallet routes
        Route::get('wallets', 'getWallets');
        Route::post('wallets/switch', 'switchWallet');
        Route::get('wallets/transactions', 'getWalletTransactions');
        Route::post('wallets/apply-coupon', 'applyCoupon');
        Route::post('wallets/purchase-subscription', 'purchaseWithWallet');

        // delete Account
        Route::delete('delete-account', 'deleteAccount');
    });
    Route::controller(PackageController::class)->prefix('packages')->group(function () {
        Route::get('/', 'index');
        Route::post('subscribe', [ProfileController::class, 'subscribe']);
    });
});
