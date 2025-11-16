<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/bot/webhook', [TelegramController::class, 'handle']);
Route::get('facebook/webhook', function (Request $request) {
    Log::info($request->all());
});
