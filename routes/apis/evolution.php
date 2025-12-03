<?php

use App\Http\Controllers\Evolution\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/evolution', [WebhookController::class, 'handle']);
