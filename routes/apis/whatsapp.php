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
        Route::get('/instances', 'getInstances');
        Route::get('/instances-by-id/{instanceId}', 'getInstance');
        Route::post('/instances', 'createInstance');
        Route::post('/instances/{instanceName}/logout', 'logoutInstance');
        Route::post('/instances/{instanceName}/disconnect', 'disconnectInstance');
        Route::delete('/instances/{instanceName}', 'deleteInstance');

        // Connection & QR Code
        Route::post('/instances/{instanceName}/qr', 'generateQRCode');
        Route::post('/instances/{instanceName}/qr/refresh', 'refreshQRCode');
        Route::get('/instances/{instanceName}/qr-code', 'getQRCode');
        Route::get('/instances/{instanceName}/status', 'getConnectionStatus');
        Route::post('/instances/{instanceName}/settings', 'updateInstanceSettings');

        // Messages
        Route::get('/instances/{instanceName}/messages', 'getMessages');
        Route::post('/instances/{instanceName}/messages', 'sendMessage');
        Route::get('/instances/{instanceName}/chats/{contactId}/messages', 'getChatMessages');
        Route::post('/instances/{instanceName}/send-message', 'sendMessage');
        Route::post('/instances/{instanceName}/send-media', 'sendMedia');
        Route::post('/instances/{instanceName}/send-buttons', 'sendButtons');
        Route::post('/instances/{instanceName}/send-list', 'sendList');
        Route::post('/instances/{instanceName}/mark-as-read', 'markAsRead');

        // Groups
        Route::post('/instances/{instanceName}/groups', 'createGroup');
        Route::get('/instances/{instanceName}/groups', 'getGroups');

        // Contacts & Chats
        Route::get('/instances/{instanceName}/contacts', 'getContacts');
        Route::get('/instances/{instanceName}/chats', 'getActiveChats');

        // Statistics
        Route::get('/instances/{instanceName}/stats', 'getInstanceStats');
    });

    // ==========================================
    //      Auto Reply & AI Settings Routes
    // ==========================================
    Route::controller(AutoReplyController::class)->prefix('whatsapp/instances/{instanceId}')->group(function () {
        // إعدادات الرد التلقائي العامة
        Route::get('/auto-reply/settings', 'getAutoReplySettings');
        Route::post('/auto-reply/settings', 'updateAutoReplySettings');
        Route::post('/auto-reply/toggle', 'toggleAutoReply');

        // قواعد الرد التلقائي
        Route::get('/auto-reply/rules', 'getRules');
        Route::get('/auto-reply/rules/{ruleId}', 'getRule');
        Route::post('/auto-reply/rules', 'createRule');
        Route::post('/auto-reply/rules/{ruleId}', 'updateRule');
        Route::delete('/auto-reply/rules/{ruleId}', 'deleteRule');
        Route::post('/auto-reply/rules/{ruleId}/toggle', 'toggleRule');
        Route::post('/auto-reply/rules/reorder', 'reorderRules');

        // إعدادات الذكاء الاصطناعي
        Route::get('/ai-reply/settings', 'getAiSettings');
        Route::post('/ai-reply/settings', 'updateAiSettings');
        Route::post('/ai-reply/toggle', 'toggleAi');
        Route::post('/ai-reply/test', 'testAi');
        Route::get('/ai-reply/stats', 'getAiStats');
    });

    // ==========================================
    //      Webhooks \u0026 API Keys Routes
    // ==========================================
    Route::controller(WhatsappWebhooksController::class)->prefix('whatsapp/instances/{instanceId}')->group(function () {
        // Webhooks Routes
        Route::prefix('webhooks')->group(function () {
            Route::get('/', 'getWebhooks');                            // GET /webhooks
            Route::get('/{webhookId}', 'getWebhook');                  // GET /webhooks/{webhook_id}
            Route::post('/', 'createWebhook');                         // POST /webhooks
            Route::post('/{webhookId}', 'updateWebhook');               // post /webhooks/{webhook_id}
            Route::delete('/{webhookId}', 'deleteWebhook');            // DELETE /webhooks/{webhook_id}
            Route::post('/{webhookId}/toggle', 'toggleWebhook');       // POST /webhooks/{webhook_id}/toggle
            Route::post('/{webhookId}/test', 'testWebhook');           // POST /webhooks/{webhook_id}/test
            Route::get('/{webhookId}/events', 'getWebhookEvents');     // GET /webhooks/{webhook_id}/events
        });

        // API Keys Routes
        Route::prefix('api-keys')->group(function () {
            Route::get('/', 'getApiKeys');                             // GET /api-keys
            Route::get('/{keyId}', 'getApiKey');                       // GET /api-keys/{key_id}
            Route::post('/', 'createApiKey');                          // POST /api-keys
            Route::post('/{keyId}', 'updateApiKey');                    // post /api-keys/{key_id}
            Route::delete('/{keyId}', 'deleteApiKey');                 // DELETE /api-keys/{key_id}
            Route::post('/{keyId}/toggle', 'toggleApiKey');            // POST /api-keys/{key_id}/toggle
        });
    });
});
