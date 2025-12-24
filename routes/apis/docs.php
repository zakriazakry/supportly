<?php

use App\Http\Controllers\Docs\WhatsappDocsController;
use Illuminate\Support\Facades\Route;

Route::controller(WhatsappDocsController::class)->prefix('v1/whatsapp-developer')->group(function () {
    Route::post('send-message', 'sendMessage');
    Route::post('send-image', 'sendImage');
    Route::post('send-video', 'sendVideo');
    Route::post('send-audio', 'sendAudio');
    Route::post('send-document', 'sendDocument');
});
