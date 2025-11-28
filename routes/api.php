<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AuthController;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
});