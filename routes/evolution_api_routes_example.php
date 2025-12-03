<?php

/**
 * Evolution API Routes Example
 * 
 * Add these routes to your routes/api.php file
 */

use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Evolution\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhook endpoint (no authentication required)
Route::post('/evolution/webhook', [WebhookController::class, 'handle'])
    ->name('evolution.webhook');

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    // Instance Management
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {

        // Create new instance
        Route::post('/instances', [WhatsAppController::class, 'createInstance'])
            ->name('instances.create');

        // Get QR code for instance
        Route::get('/instances/{instanceName}/qrcode', [WhatsAppController::class, 'getQRCode'])
            ->name('instances.qrcode');

        // Get connection status
        Route::get('/instances/{instanceName}/status', [WhatsAppController::class, 'getConnectionStatus'])
            ->name('instances.status');

        // Logout instance
        Route::post('/instances/{instanceName}/logout', [WhatsAppController::class, 'logoutInstance'])
            ->name('instances.logout');

        // Delete instance
        Route::delete('/instances/{instanceName}', [WhatsAppController::class, 'deleteInstance'])
            ->name('instances.delete');

        // Messages
        Route::prefix('instances/{instanceName}')->name('instances.')->group(function () {

            // Send text message
            Route::post('/messages/text', [WhatsAppController::class, 'sendMessage'])
                ->name('messages.send');

            // Send media message
            Route::post('/messages/media', [WhatsAppController::class, 'sendMedia'])
                ->name('messages.media');

            // Get messages
            Route::get('/messages', [WhatsAppController::class, 'getMessages'])
                ->name('messages.get');

            // Groups
            Route::prefix('groups')->name('groups.')->group(function () {

                // Create group
                Route::post('/', [WhatsAppController::class, 'createGroup'])
                    ->name('create');

                // Get all groups
                Route::get('/', [WhatsAppController::class, 'getGroups'])
                    ->name('list');
            });

            // Contacts
            Route::get('/contacts', [WhatsAppController::class, 'getContacts'])
                ->name('contacts.list');

            // Profile
            Route::put('/profile', [WhatsAppController::class, 'updateProfile'])
                ->name('profile.update');
        });
    });
});
