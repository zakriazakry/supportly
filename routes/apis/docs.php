<?php

use App\Http\Controllers\Docs\WhatsappDocsController;
use App\Http\Middleware\APIKeyVaildatorMiddleware;
use Illuminate\Support\Facades\Route;

Route::controller(WhatsappDocsController::class)->middleware([APIKeyVaildatorMiddleware::class])->prefix('v1/whatsapp-developer')->group(function () {
    Route::post('send-message', 'sendMessage');
    Route::post('send-media', 'sendMedia');
    Route::post('show-write', 'showWirte');
    Route::post('seen-message', 'seen');
});
