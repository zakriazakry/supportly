<?php

use App\Http\Controllers\Docs\WhatsappDocsController;
use Illuminate\Support\Facades\Route;

Route::controller(WhatsappDocsController::class)->middleware('auth:sanctum')->prefix('v1/whatsapp-developer')->group(function () {
    Route::post('send-message', 'sendMessage');
});
