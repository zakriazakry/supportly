<?php

use App\Http\Controllers\Webhook\FacebookWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('facebook/webhook', [FacebookWebhookController::class, 'verify']);

Route::post('facebook/webhook', [FacebookWebhookController::class, 'receive']);
