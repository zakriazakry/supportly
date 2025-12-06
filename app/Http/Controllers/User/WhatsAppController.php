<?php

namespace App\Http\Controllers\User;

use App\Events\WhatsApp\InstanceConnected;
use App\Events\WhatsApp\InstanceDisconnected;
use App\Events\WhatsApp\Instanceopen;
use App\Events\WhatsApp\InstanceDisopen;
use App\Http\Controllers\Controller;
use App\Services\EvolutionService;
use App\Models\WhatsAppInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    protected $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
    }

    /**
     * Create a new WhatsApp instance
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get all instances
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInstances(Request $request)
    {
        $instances = WhatsAppInstance::where('user_id', $request->user()->id)->get();
        $providerData = $this->evolutionService->fetchInstances();
        // providerData : 
        // [
        //     {
        //         "id": "5fae50bf-19e1-4d1e-850a-f587043fbc08",
        //         "name": "Zakria Zakry",
        //         "connectionStatus": "open",
        //         "ownerJid": "218921730546@s.whatsapp.net",
        //         "profileName": null,
        //         "profilePicUrl": null,
        //         "integration": "WHATSAPP-BAILEYS",
        //         "number": null,
        //         "businessId": null,
        //         "token": "D46C4F6F-37E5-4EDD-88C8-BD4A1BC80471",
        //         "clientName": "evolution_exchange",
        //         "disconnectionReasonCode": 401,
        //         "disconnectionObject": "{\"error\":{\"data\":null,\"isBoom\":true,\"isServer\":false,\"output\":{\"statusCode\":401,\"payload\":{\"statusCode\":401,\"error\":\"Unauthorized\",\"message\":\"Log out instance: Zakria Zakry\"},\"headers\":{}}},\"date\":\"2025-12-04T09:26:24.599Z\"}",
        //         "disconnectionAt": "2025-12-04T09:26:24.608Z",
        //         "createdAt": "2025-12-04T09:21:01.917Z",
        //         "updatedAt": "2025-12-04T09:27:01.055Z",
        //         "Chatwoot": null,
        //         "Proxy": null,
        //         "Rabbitmq": null,
        //         "Nats": null,
        //         "Sqs": null,
        //         "Websocket": null,
        //         "Setting": {
        //             "id": "cmir87f1b0075ms4qa6a52fgw",
        //             "rejectCall": true,
        //             "msgCall": "عذراً، لا أقبل المكالمات",
        //             "groupsIgnore": false,
        //             "alwaysOnline": true,
        //             "readMessages": false,
        //             "readStatus": false,
        //             "syncFullHistory": false,
        //             "wavoipToken": "",
        //             "createdAt": "2025-12-04T09:21:01.920Z",
        //             "updatedAt": "2025-12-04T09:21:01.920Z",
        //             "instanceId": "5fae50bf-19e1-4d1e-850a-f587043fbc08"
        //         },
        //         "_count": {
        //             "Message": 24,
        //             "Contact": 353,
        //             "Chat": 4
        //         }
        //     }
        // ]

        return responseFormat($instances);
    }

    /**
     * Get a specific instance
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInstance(Request $request, $id)
    {
        $instance = WhatsAppInstance::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or you do not have access to it', 403);
        }

        $providerData = $this->evolutionService->fetchInstances($instance->name);
        $instance->evo = $providerData['data'][0] ?? null;
        return responseFormat($instance);
    }

    /**
     * Create a new WhatsApp instance
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createInstance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:whats_app_instances,instance_name',
            'phone_number' => 'required|string',
            'integration' => 'nullable|in:WHATSAPP-BAILEYS,WHATSAPP-BUSINESS',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        // Create instance via Evolution API
        $result = $this->evolutionService->createInstance(
            $request->name,
            [
                'qrcode' => true,
                'integration' => $request->integration ?? 'WHATSAPP-BAILEYS',
                'webhook' => [
                    'url' => url('/api/evolution/webhook'),
                    'byEvents' => false,
                    'base64' => true,
                    'events' => [
                        'MESSAGES_UPSERT',
                        'CONNECTION_UPDATE',
                        'QRCODE_UPDATED',
                    ]
                ],
                'rejectCall' => true,
                'msgCall' => 'عذراً، لا أقبل المكالمات',
                'alwaysOnline' => true,
                'readMessages' => false,
            ]
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Save instance to database
        $instance = WhatsAppInstance::create([
            'user_id' => $request->user()->id,
            'instance_name' => $request->name,
            'phone_number' => $request->phone_number,
            'token' => $result['data']['hash'] ?? null,
            'qr_code' => $result['data']['qrcode']['base64'] ?? null,
            'status' => 'pending',
            'integration_type' => $request->integration ?? 'WHATSAPP-BAILEYS',
        ]);

        return responseFormat($instance);
    }

    /**
     * Generate QR code for instance connection
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateQRCode(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $result = $this->evolutionService->connectInstance($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Update QR code in database
        $instance->update([
            'qr_code' => $result['data']['base64'] ?? null,
            'status' => 'pending',
        ]);

        return responseFormat([
            'qr_code' => $result['data']['base64'] ?? null,
            'pairingCode' => $result['data']['pairingCode'] ?? null,
        ]);
    }

    /**
     * Refresh QR code (called every 20 seconds)
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshQRCode(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        // Check if already open
        if ($instance->status === 'open') {
            return responseFormat([
                'status' => 'open',
                'message' => 'Instance already open'
            ]);
        }

        $result = $this->evolutionService->connectInstance($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Update QR code in database
        $instance->update([
            'qr_code' => $result['data']['base64'] ?? null,
        ]);

        return responseFormat([
            'qr_code' => $result['data']['base64'] ?? null,
            'pairingCode' => $result['data']['pairingCode'] ?? null,
        ]);
    }

    /**
     * Get QR code for instance
     * 
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQRCode(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $result = $this->evolutionService->connectInstance($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Update QR code in database
        $instance->update([
            'qr_code' => $result['data']['base64'] ?? null,
            'status' => 'pending',
        ]);

        return responseFormat([
            'qr_code' => $result['data']['base64'] ?? null,
        ]);
    }

    /**
     * Get connection status
     * 
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConnectionStatus(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $result = $this->evolutionService->getConnectionStatus($instanceName);
        Log::info('Connection status', ['data' => $result]);
        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        $instance->update([
            'status' => $result['data']['instance']['state'],
        ]);

        return responseFormat($result['data']);
    }

    /**
     * Get instance settings
     * 
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInstanceSettings(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }
        // TODO
        $result = $this->evolutionService->setSettings($instanceName, $request->all());

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat($result['data']);
    }

    /**
     * Send text message
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string',
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->sendText(
            $instanceName,
            $request->number,
            $request->text
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat([
            'message' => 'تم إرسال الرسالة بنجاح',
            'data' => $result['data']
        ]);
    }

    /**
     * Send media message
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMedia(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string',
            'media_type' => 'required|in:image,video,document',
            'media_url' => 'required|url',
            'caption' => 'nullable|string',
            'file_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->sendMedia(
            $instanceName,
            $request->number,
            $request->media_url,
            $request->media_type,
            [
                'caption' => $request->caption,
                'fileName' => $request->file_name,
            ]
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat([
            'message' => 'تم إرسال الوسائط بنجاح',
            'data' => $result['data']
        ]);
    }

    /**
     * Create group
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function createGroup(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string',
            'participants' => 'required|array|min:1',
            'participants.*' => 'required|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->createGroup(
            $instanceName,
            $request->subject,
            $request->participants
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat([
            'message' => 'تم إنشاء المجموعة بنجاح',
            'data' => $result['data']
        ]);
    }

    /**
     * Get all groups
     * 
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroups(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->fetchAllGroups($instanceName, true);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat($result['data']);
    }

    /**
     * Get all contacts
     * 
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContacts(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->findContacts($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat($result['data']);
    }

    /**
     * Get chat messages
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'remote_jid' => 'required|string',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->findMessages(
            $instanceName,
            ['key' => ['remoteJid' => $request->remote_jid]]
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat($result['data']);
    }

    /**
     * Send buttons message
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendButtons(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string',
            'title' => 'nullable|string',
            'description' => 'required|string',
            'buttons' => 'required|array|min:1',
            'buttons.*.text' => 'required|string',
            'buttons.*.id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->sendQuickReply(
            $instanceName,
            $request->number,
            $request->description,
            $request->buttons
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat([
            'message' => 'تم إرسال الأزرار بنجاح',
            'data' => $result['data']
        ]);
    }

    /**
     * Send list message
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendList(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string',
            'title' => 'required|string',
            'description' => 'required|string',
            'button_text' => 'required|string',
            'sections' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->sendList(
            $instanceName,
            $request->number,
            $request->title,
            $request->description,
            $request->button_text,
            $request->sections
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat([
            'message' => 'تم إرسال القائمة بنجاح',
            'data' => $result['data']
        ]);
    }

    /**
     * Mark messages as read
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'messages' => 'required|array|min:1',
            'messages.*.remoteJid' => 'required|string',
            'messages.*.fromMe' => 'required|boolean',
            'messages.*.id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->markAsRead(
            $instanceName,
            $request->messages
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat([
            'message' => 'تم تحديد الرسائل كمقروءة',
            'data' => $result['data']
        ]);
    }

    /**
     * Delete instance
     * 
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteInstance(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $result = $this->evolutionService->deleteInstance($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Delete from database
        $instance->delete();

        return responseFormat('تم حذف الـ instance بنجاح');
    }

    /**
     * Logout instance
     * 
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutInstance(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $result = $this->evolutionService->logoutInstance($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Update status in database
        $instance->update([
            'status' => 'disopen',
            'qr_code' => null,
        ]);

        return responseFormat('تم تسجيل الخروج بنجاح');
    }

    /**
     * Disconnect instance
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function disconnectInstance(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $result = $this->evolutionService->logoutInstance($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Update status in database
        $instance->update([
            'status' => 'disopen',
            'qr_code' => null,
        ]);

        return responseFormat('تم قطع الاتصال بنجاح');
    }

    /**
     * Get chat messages for a specific contact
     * 
     * @param Request $request
     * @param string $instanceName
     * @param string $contactId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatMessages(Request $request, $instanceName, $contactId)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->findMessages(
            $instanceName,
            ['key' => ['remoteJid' => $contactId]]
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat($result['data']);
    }

    /**
     * Get active chats
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveChats(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found or not open', 404);
        }

        $result = $this->evolutionService->findChats($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat($result['data']);
    }

    /**
     * Get instance statistics
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInstanceStats(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }
        $providerData = $this->evolutionService->getConnectionStatus($instanceName);

        Log::info('providerData');
        Log::info($providerData);
        // Get basic stats from database and Evolution API
        $stats = [
            'instance_name' => $instance->instance_name,
            'status' => $providerData['data']['instance']['state'],
            'phone_number' => $instance->phone_number,
            'profile_name' => $instance->profile_name,
            'created_at' => $instance->created_at,
        ];
        $instance->update([
            'status' => $providerData['data']['instance']['state'],
        ]);
        // If open, get additional stats
        if ($providerData['data']['instance']['state'] === 'open') {
            $contactsResult = $this->evolutionService->findContacts($instanceName);
            $chatsResult = $this->evolutionService->findChats($instanceName);
            $groupsResult = $this->evolutionService->fetchAllGroups($instanceName, false);

            $stats['total_contacts'] = $contactsResult;
            $stats['total_chats'] = $chatsResult;
            $stats['total_groups'] = $groupsResult;
        }

        return responseFormat($stats);
    }

    /**
     * Get auto-reply rules
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAutoReplyRules(Request $request, $instanceName)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $rules = $instance->autoReplyRules ?? [];

        return responseFormat($rules);
    }

    /**
     * Create auto-reply rule
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAutoReplyRule(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'trigger' => 'required|string',
            'response' => 'required|string',
            'type' => 'nullable|in:exact,contains,starts_with,ends_with',
            'enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $rules = $instance->auto_reply_rules ?? [];

        $newRule = [
            'id' => uniqid('rule_'),
            'trigger' => $request->trigger,
            'response' => $request->response,
            'type' => $request->type ?? 'contains',
            'enabled' => $request->enabled ?? true,
            'created_at' => now()->toISOString(),
        ];

        $rules[] = $newRule;

        $instance->update(['auto_reply_rules' => $rules]);

        return responseFormat($newRule);
    }

    /**
     * Update auto-reply rule
     * 
     * @param Request $request
     * @param string $instanceName
     * @param string $ruleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAutoReplyRule(Request $request, $instanceName, $ruleId)
    {
        $validator = Validator::make($request->all(), [
            'trigger' => 'nullable|string',
            'response' => 'nullable|string',
            'type' => 'nullable|in:exact,contains,starts_with,ends_with',
            'enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $rules = $instance->auto_reply_rules ?? [];
        $ruleIndex = array_search($ruleId, array_column($rules, 'id'));

        if ($ruleIndex === false) {
            return responseFormat('Rule not found', 404);
        }

        // Update rule
        if ($request->has('trigger')) {
            $rules[$ruleIndex]['trigger'] = $request->trigger;
        }
        if ($request->has('response')) {
            $rules[$ruleIndex]['response'] = $request->response;
        }
        if ($request->has('type')) {
            $rules[$ruleIndex]['type'] = $request->type;
        }
        if ($request->has('enabled')) {
            $rules[$ruleIndex]['enabled'] = $request->enabled;
        }

        $rules[$ruleIndex]['updated_at'] = now()->toISOString();

        $instance->update(['auto_reply_rules' => $rules]);

        return responseFormat($rules[$ruleIndex]);
    }

    /**
     * Delete auto-reply rule
     * 
     * @param Request $request
     * @param string $instanceName
     * @param string $ruleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAutoReplyRule(Request $request, $instanceName, $ruleId)
    {
        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $rules = $instance->auto_reply_rules ?? [];
        $ruleIndex = array_search($ruleId, array_column($rules, 'id'));

        if ($ruleIndex === false) {
            return responseFormat('Rule not found', 404);
        }

        // Remove rule
        array_splice($rules, $ruleIndex, 1);

        $instance->update(['auto_reply_rules' => $rules]);

        return responseFormat('تم حذف القاعدة بنجاح');
    }

    /**
     * Toggle auto-reply on/off
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAutoReply(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('Instance not found', 404);
        }

        $instance->update(['auto_reply_enabled' => $request->enabled]);

        return responseFormat([
            'enabled' => $request->enabled,
            'message' => $request->enabled ? 'تم تفعيل الرد التلقائي' : 'تم تعطيل الرد التلقائي'
        ]);
    }
}
