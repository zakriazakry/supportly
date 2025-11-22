<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FacebookController;

// صفحة البداية لتسجيل الدخول
Route::get('/', function () {
    return view('welcome');
});

// المسار الذي يحول المستخدم إلى فيسبوك
Route::get('auth/facebook', [FacebookController::class, 'redirectToFacebook'])->name('facebook.login');

// المسار الذي يستقبل الرد من فيسبوك
Route::get('auth/facebook/callback', [FacebookController::class, 'handleFacebookCallback']);

// مسار اختيار الصفحة (قد تحتاجه بعد المصادقة)
Route::get('facebook/select-page', [FacebookController::class, 'selectPage']);
