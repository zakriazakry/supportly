<?php

use App\Http\Controllers\User\FacebookAccountsController;
use App\Http\Controllers\User\FacebookPagesController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\SupportingPagesController;
use App\Http\Controllers\User\TemeplatesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(HomeController::class)->prefix('home')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(FacebookAccountsController::class)->prefix('facebook-accounts')->group(function () {
        Route::get('/', 'index');
        Route::post('/add-account', 'addAccount');
        Route::delete('/delete-account/{id}', 'deleteAccount');
    });

    Route::controller(FacebookPagesController::class)->prefix('facebook-pages')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(SupportingPagesController::class)->prefix('supporting-pages')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(TemeplatesController::class)->prefix('templates')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'index');
    });
});
