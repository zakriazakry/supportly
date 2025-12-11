<?php

use App\Http\Controllers\User\WhatsAppController;
use App\Http\Controllers\Whatsapp\AutoReplyController;
use App\Http\Controllers\Webhook\WebhookController;
use App\Http\Controllers\Whatsapp\WhatsappWebhooksController;
use Illuminate\Support\Facades\Route;

Route::post('/evolution/webhook', [WebhookController::class, 'handle']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(WhatsAppController::class)->prefix('whatsapp')->group(function () {
        // Instance Management
        Route::get('/instances', 'getInstances')->middleware('feature:whatsapp');
        Route::get('/instances-by-id/{instanceId}', 'getInstance')->middleware('feature:whatsapp');
        Route::post('/instances', 'createInstance')->middleware('feature:whatsapp,whatsapp_accounts'); // Check Feature + Limit
        Route::post('/instances/{instanceName}/logout', 'logoutInstance')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/disconnect', 'disconnectInstance')->middleware('feature:whatsapp');
        Route::delete('/instances/{instanceName}', 'deleteInstance');

        // Connection & QR Code
        Route::post('/instances/{instanceName}/qr', 'generateQRCode')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/qr/refresh', 'refreshQRCode')->middleware('feature:whatsapp');
        Route::get('/instances/{instanceName}/qr-code', 'getQRCode')->middleware('feature:whatsapp');
        Route::get('/instances/{instanceName}/status', 'getConnectionStatus')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/settings', 'updateInstanceSettings')->middleware('feature:whatsapp');

        // Messages
        Route::get('/instances/{instanceName}/messages', 'getMessages')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/messages', 'sendMessage')->middleware('feature:whatsapp');
        Route::get('/instances/{instanceName}/chats/{contactId}/messages', 'getChatMessages')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/send-message', 'sendMessage')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/send-media', 'sendMedia')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/send-buttons', 'sendButtons')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/send-list', 'sendList')->middleware('feature:whatsapp');
        Route::post('/instances/{instanceName}/mark-as-read', 'markAsRead')->middleware('feature:whatsapp');

        // Groups
        Route::post('/instances/{instanceName}/groups', 'createGroup')->middleware('feature:whatsapp');
        Route::get('/instances/{instanceName}/groups', 'getGroups')->middleware('feature:whatsapp');

        // Contacts & Chats
        Route::get('/instances/{instanceName}/contacts', 'getContacts')->middleware('feature:whatsapp');
        Route::get('/instances/{instanceName}/chats', 'getActiveChats')->middleware('feature:whatsapp');

        // Statistics
        Route::get('/instances/{instanceName}/stats', 'getInstanceStats')->middleware('feature:whatsapp');
    });

    // ==========================================
    //      Auto Reply & AI Settings Routes
    // ==========================================
    Route::controller(AutoReplyController::class)->prefix('whatsapp/instances/{instanceId}')->middleware('feature:whatsapp')->group(function () {
        // إعدادات الرد التلقائي العامة
        Route::middleware('feature:whatsapp_auto_reply')->group(function () {
            Route::get('/auto-reply/settings', 'getAutoReplySettings');
            Route::post('/auto-reply/settings', 'updateAutoReplySettings');
            Route::post('/auto-reply/toggle', 'toggleAutoReply');

            // قواعد الرد التلقائي
            Route::get('/auto-reply/rules', 'getRules');
            Route::get('/auto-reply/rules/{ruleId}', 'getRule');
            Route::post('/auto-reply/rules', 'createRule'); // Limit check for templates could be here if we had 'whatsapp_templates' limit
            Route::post('/auto-reply/rules/{ruleId}', 'updateRule');
            Route::delete('/auto-reply/rules/{ruleId}', 'deleteRule');
            Route::post('/auto-reply/rules/{ruleId}/toggle', 'toggleRule');
            Route::post('/auto-reply/rules/reorder', 'reorderRules');
        });

        // إعدادات الذكاء الاصطناعي
        Route::middleware('feature:whatsapp_ai_reply')->group(function () {
            Route::get('/ai-reply/settings', 'getAiSettings');
            Route::post('/ai-reply/settings', 'updateAiSettings');
            Route::post('/ai-reply/toggle', 'toggleAi');
            Route::post('/ai-reply/test', 'testAi');
            Route::get('/ai-reply/stats', 'getAiStats');
        });
    });

    // ==========================================
    //      Webhooks \u0026 API Keys Routes
    // ==========================================
    Route::controller(WhatsappWebhooksController::class)
        ->prefix('whatsapp/instances/{instanceId}')
        ->middleware(['feature:whatsapp', 'feature:whatsapp_developer']) // Check both WA and Developer feature
        ->group(function () {
            // Webhooks Routes
            Route::prefix('webhooks')->group(function () {
                Route::get('/', 'getWebhooks');
                Route::get('/{webhookId}', 'getWebhook');
                Route::post('/', 'createWebhook');
                Route::post('/{webhookId}', 'updateWebhook');
                Route::delete('/{webhookId}', 'deleteWebhook');
                Route::post('/{webhookId}/toggle', 'toggleWebhook');
                Route::post('/{webhookId}/test', 'testWebhook');
                Route::get('/{webhookId}/events', 'getWebhookEvents');
            });

            // API Keys Routes
            Route::prefix('api-keys')->group(function () {
                Route::get('/', 'getApiKeys');
                Route::get('/{keyId}', 'getApiKey');
                Route::post('/', 'createApiKey');
                Route::post('/{keyId}', 'updateApiKey');
                Route::delete('/{keyId}', 'deleteApiKey');
                Route::post('/{keyId}/toggle', 'toggleApiKey');
            });
        });
});
