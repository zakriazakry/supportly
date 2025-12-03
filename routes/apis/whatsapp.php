<?php

use App\Http\Controllers\User\WhatsAppController;
use App\Http\Controllers\Webhook\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/evolution/webhook', [WebhookController::class, 'handle']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('whatsapp')->group(function () {
        Route::get('/instances', [WhatsAppController::class, 'getInstances']);
        Route::post('/instances', [WhatsAppController::class, 'createInstance']);
        Route::get('/instances/{instanceName}/qrcode', [WhatsAppController::class, 'getQRCode']);
        Route::get('/instances/{instanceName}/status', [WhatsAppController::class, 'getConnectionStatus']);
        Route::post('/instances/{instanceName}/logout', [WhatsAppController::class, 'logoutInstance']);
        Route::delete('/instances/{instanceName}', [WhatsAppController::class, 'deleteInstance']);
        Route::prefix('instances/{instanceName}')->group(function () {
            Route::post('/messages/text', [WhatsAppController::class, 'sendMessage']);
            Route::post('/messages/media', [WhatsAppController::class, 'sendMedia']);
            Route::post('/messages/buttons', [WhatsAppController::class, 'sendButtons']);
            Route::post('/messages/list', [WhatsAppController::class, 'sendList']);
            Route::post('/messages/mark-read', [WhatsAppController::class, 'markAsRead']);
            Route::get('/messages', [WhatsAppController::class, 'getMessages']);
            Route::prefix('groups')->group(function () {
                Route::post('/', [WhatsAppController::class, 'createGroup']);
                Route::get('/', [WhatsAppController::class, 'getGroups']);
            });
            Route::get('/contacts', [WhatsAppController::class, 'getContacts']);
        });
    });
});
