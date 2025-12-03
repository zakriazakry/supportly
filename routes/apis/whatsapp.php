<?php

use App\Http\Controllers\Api\WhatsAppController;
use Illuminate\Support\Facades\Route;

// Webhook endpoint (no authentication required)
Route::post('/evolution/webhook', [WhatsAppController::class, 'webhook']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('whatsapp')->group(function () {
        // Instance Management
        Route::post('/instances', [WhatsAppController::class, 'createInstance']);
        Route::get('/instances/{instanceName}/qrcode', [WhatsAppController::class, 'getQRCode']);
        Route::get('/instances/{instanceName}/status', [WhatsAppController::class, 'getConnectionStatus']);
        Route::post('/instances/{instanceName}/logout', [WhatsAppController::class, 'logoutInstance']);
        Route::delete('/instances/{instanceName}', [WhatsAppController::class, 'deleteInstance']);

        // Instance-specific operations
        Route::prefix('instances/{instanceName}')->group(function () {
            // Messages
            Route::post('/messages/text', [WhatsAppController::class, 'sendMessage']);
            Route::post('/messages/media', [WhatsAppController::class, 'sendMedia']);
            Route::post('/messages/buttons', [WhatsAppController::class, 'sendButtons']);
            Route::post('/messages/list', [WhatsAppController::class, 'sendList']);
            Route::post('/messages/mark-read', [WhatsAppController::class, 'markAsRead']);
            Route::get('/messages', [WhatsAppController::class, 'getMessages']);

            // Groups
            Route::prefix('groups')->group(function () {
                Route::post('/', [WhatsAppController::class, 'createGroup']);
                Route::get('/', [WhatsAppController::class, 'getGroups']);
            });

            // Contacts
            Route::get('/contacts', [WhatsAppController::class, 'getContacts']);
        });
    });
});
