<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\WhatsAppInstance;
use App\Models\Webhook;
use App\Models\WebhookEvent;
use App\Models\ApiKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WhatsappWebhooksController extends Controller
{
    // ==========================================
    //         Webhooks Management
    // ==========================================

    /**
     * Get all webhooks for an instance
     * GET /api/whatsapp/instances/{instanceId}/webhooks
     */
    public function getWebhooks(Request $request, $instanceId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $webhooks = Webhook::where('whatsapp_instance_id', $instance->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return responseFormat($webhooks);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Get a single webhook
     * GET /api/whatsapp/instances/{instanceId}/webhooks/{webhookId}
     */
    public function getWebhook(Request $request, $instanceId, $webhookId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $webhook = Webhook::where('whatsapp_instance_id', $instance->id)
                ->where('id', $webhookId)
                ->first();

            if (!$webhook) {
                return responseFormat('Webhook not found', 404);
            }

            return responseFormat($webhook);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Create a new webhook
     * POST /api/whatsapp/instances/{instanceId}/webhooks
     */
    public function createWebhook(Request $request, $instanceId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'url' => 'required|url|max:500',
                'events' => 'required|array|min:1',
                'events.*' => 'required|string|in:APPLICATION.STARTUP,CALL,CHATS.DELETE,CHATS.SET,CHATS.UPDATE,CHATS.UPSERT,CONNECTION.UPDATE,CONTACTS.SET,CONTACTS.UPDATE,CONTACTS.UPSERT,GROUP.PARTICIPANTS.UPDATE,GROUP.UPDATE,GROUPS.UPSERT,LABELS.ASSOCIATION,LABELS.EDIT,LOGOUT.INSTANCE,MESSAGES.DELETE,MESSAGES.SET,MESSAGES.UPDATE,MESSAGES.UPSERT,PRESENCE.UPDATE,QRCODE.UPDATED,REMOVE.INSTANCE,SEND.MESSAGE,TYPEBOT.CHANGE.STATUS,TYPEBOT.START',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return responseFormat($validator->errors()->first(), 422);
            }

            // Create webhook
            $webhook = Webhook::create([
                'whatsapp_instance_id' => $instance->id,
                'name' => $request->name,
                'url' => $request->url,
                'events' => $request->events,
                'secret' => Str::random(32), // Generate secret for signature verification
                'is_active' => $request->is_active ?? true,
                'total_calls' => 0,
                'success_rate' => 100.00
            ]);

            return responseFormat($webhook, 201);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Update a webhook
     * PUT /api/whatsapp/instances/{instanceId}/webhooks/{webhookId}
     */
    public function updateWebhook(Request $request, $instanceId, $webhookId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $webhook = Webhook::where('whatsapp_instance_id', $instance->id)
                ->where('id', $webhookId)
                ->first();

            if (!$webhook) {
                return responseFormat('Webhook not found', 404);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'url' => 'required|url|max:500',
                'events' => 'required|array|min:1',
                'events.*' => 'required|string|in:APPLICATION.STARTUP,CALL,CHATS.DELETE,CHATS.SET,CHATS.UPDATE,CHATS.UPSERT,CONNECTION.UPDATE,CONTACTS.SET,CONTACTS.UPDATE,CONTACTS.UPSERT,GROUP.PARTICIPANTS.UPDATE,GROUP.UPDATE,GROUPS.UPSERT,LABELS.ASSOCIATION,LABELS.EDIT,LOGOUT.INSTANCE,MESSAGES.DELETE,MESSAGES.SET,MESSAGES.UPDATE,MESSAGES.UPSERT,PRESENCE.UPDATE,QRCODE.UPDATED,REMOVE.INSTANCE,SEND.MESSAGE,TYPEBOT.CHANGE.STATUS,TYPEBOT.START',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return responseFormat($validator->errors()->first(), 422);
            }

            // Update webhook
            $webhook->update([
                'name' => $request->name,
                'url' => $request->url,
                'events' => $request->events,
                'is_active' => $request->is_active ?? $webhook->is_active
            ]);

            return responseFormat($webhook->fresh());
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Delete a webhook
     * DELETE /api/whatsapp/instances/{instanceId}/webhooks/{webhookId}
     */
    public function deleteWebhook(Request $request, $instanceId, $webhookId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $webhook = Webhook::where('whatsapp_instance_id', $instance->id)
                ->where('id', $webhookId)
                ->first();

            if (!$webhook) {
                return responseFormat('Webhook not found', 404);
            }

            $webhook->delete();

            return responseFormat('Webhook deleted successfully');
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Toggle webhook status
     * POST /api/whatsapp/instances/{instanceId}/webhooks/{webhookId}/toggle
     */
    public function toggleWebhook(Request $request, $instanceId, $webhookId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $webhook = Webhook::where('whatsapp_instance_id', $instance->id)
                ->where('id', $webhookId)
                ->first();

            if (!$webhook) {
                return responseFormat('Webhook not found', 404);
            }

            $webhook->is_active = !$webhook->is_active;
            $webhook->save();

            return responseFormat(['is_active' => $webhook->is_active]);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Test a webhook
     * POST /api/whatsapp/instances/{instanceId}/webhooks/{webhookId}/test
     */
    public function testWebhook(Request $request, $instanceId, $webhookId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $webhook = Webhook::where('whatsapp_instance_id', $instance->id)
                ->where('id', $webhookId)
                ->first();

            if (!$webhook) {
                return responseFormat('Webhook not found', 404);
            }

            // Prepare test payload
            $testPayload = [
                'event' => 'test.webhook',
                'instance_id' => $instance->id,
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'message' => 'This is a test webhook event',
                    'test' => true
                ]
            ];

            // Send webhook
            $result = sendWebhook($webhook, $testPayload);

            return responseFormat($result);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Get webhook events (logs)
     * GET /api/whatsapp/instances/{instanceId}/webhooks/{webhookId}/events
     */
    public function getWebhookEvents(Request $request, $instanceId, $webhookId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $webhook = Webhook::where('whatsapp_instance_id', $instance->id)
                ->where('id', $webhookId)
                ->first();

            if (!$webhook) {
                return responseFormat('Webhook not found', 404);
            }

            $limit = $request->get('limit', 50);

            $events = WebhookEvent::where('webhook_id', $webhook->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return responseFormat($events);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    // ==========================================
    //         API Keys Management
    // ==========================================

    /**
     * Get all API keys for an instance
     * GET /api/whatsapp/instances/{instanceId}/api-keys
     */
    public function getApiKeys(Request $request, $instanceId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $apiKeys = ApiKey::where('whatsapp_instance_id', $instance->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return responseFormat($apiKeys);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Get a single API key
     * GET /api/whatsapp/instances/{instanceId}/api-keys/{keyId}
     */
    public function getApiKey(Request $request, $instanceId, $keyId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $apiKey = ApiKey::where('whatsapp_instance_id', $instance->id)
                ->where('id', $keyId)
                ->first();

            if (!$apiKey) {
                return responseFormat('API Key not found', 404);
            }

            // Hide the full API key
            $apiKey->key = $this->maskApiKey($apiKey->key);

            return responseFormat($apiKey);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Create a new API key
     * POST /api/whatsapp/instances/{instanceId}/api-keys
     */
    public function createApiKey(Request $request, $instanceId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'required|string|in:messages.send,messages.read,contacts.read,contacts.manage,groups.read,groups.manage,instance.read,instance.manage'
            ]);

            if ($validator->fails()) {
                return responseFormat($validator->errors()->first(), 422);
            }

            // Generate API key
            $plainKey = 'sk_live_' . Str::random(32);
            $hashedKey = hash('sha256', $plainKey);

            // Create API key
            $apiKey = ApiKey::create([
                'whatsapp_instance_id' => $instance->id,
                'name' => $request->name,
                'key' => $hashedKey,
                'permissions' => $request->permissions,
                'is_active' => true
            ]);

            // Return full key only once
            $apiKey->key = $plainKey;

            return responseFormat($apiKey, 201, 'API Key created successfully. Please save this key, it will not be shown again.');
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Update an API key
     * PUT /api/whatsapp/instances/{instanceId}/api-keys/{keyId}
     */
    public function updateApiKey(Request $request, $instanceId, $keyId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $apiKey = ApiKey::where('whatsapp_instance_id', $instance->id)
                ->where('id', $keyId)
                ->first();

            if (!$apiKey) {
                return responseFormat('API Key not found', 404);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'required|string|in:messages.send,messages.read,contacts.read,contacts.manage,groups.read,groups.manage,instance.read,instance.manage',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return responseFormat($validator->errors()->first(), 422);
            }

            // Update API key (but not the key itself)
            $apiKey->update([
                'name' => $request->name,
                'permissions' => $request->permissions,
                'is_active' => $request->is_active ?? $apiKey->is_active
            ]);

            // Hide the full API key
            $apiKey->key = $this->maskApiKey($apiKey->key);

            return responseFormat($apiKey->fresh());
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Delete an API key
     * DELETE /api/whatsapp/instances/{instanceId}/api-keys/{keyId}
     */
    public function deleteApiKey(Request $request, $instanceId, $keyId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $apiKey = ApiKey::where('whatsapp_instance_id', $instance->id)
                ->where('id', $keyId)
                ->first();

            if (!$apiKey) {
                return responseFormat('API Key not found', 404);
            }

            $apiKey->delete();

            return responseFormat('API Key deleted successfully');
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Toggle API key status
     * POST /api/whatsapp/instances/{instanceId}/api-keys/{keyId}/toggle
     */
    public function toggleApiKey(Request $request, $instanceId, $keyId)
    {
        try {
            $instance = $this->verifyInstanceOwnership($request, $instanceId);

            $apiKey = ApiKey::where('whatsapp_instance_id', $instance->id)
                ->where('id', $keyId)
                ->first();

            if (!$apiKey) {
                return responseFormat('API Key not found', 404);
            }

            $apiKey->is_active = !$apiKey->is_active;
            $apiKey->save();

            return responseFormat(['is_active' => $apiKey->is_active]);
        } catch (\Exception $e) {
            return responseFormat($e->getMessage(), 500);
        }
    }

    // ==========================================
    //         Helper Methods
    // ==========================================

    /**
     * Verify that the instance belongs to the authenticated user
     */
    private function verifyInstanceOwnership(Request $request, $instanceId)
    {
        $instance = WhatsAppInstance::where('id', $instanceId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            throw new \Exception('Instance not found or access denied');
        }

        return $instance;
    }


    /**
     * Mask API key for security
     */
    private function maskApiKey($key)
    {
        if (strlen($key) <= 12) {
            return 'sk_***';
        }

        return substr($key, 0, 8) . '***' . substr($key, -4);
    }
}
