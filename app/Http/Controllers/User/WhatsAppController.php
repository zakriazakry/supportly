<?php

namespace App\Http\Controllers\User;

use App\Events\WhatsApp\InstanceConnected;
use App\Events\WhatsApp\InstanceDisconnected;
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
        return responseFormat($instances);
    }

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
            'phone_number' => $request->phoneNumber,
            'token' => $result['data']['hash'] ?? null,
            'qr_code' => $result['data']['qrcode']['base64'] ?? null,
            'status' => 'pending',
            'integration_type' => $request->integration ?? 'WHATSAPP-BAILEYS',
        ]);

        return responseFormat($instance);
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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->where('status', 'connected')
            ->firstOrFail();

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
            ->firstOrFail();

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
            ->firstOrFail();

        $result = $this->evolutionService->logoutInstance($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Update status in database
        $instance->update([
            'status' => 'disconnected',
            'qr_code' => null,
        ]);

        return responseFormat('تم تسجيل الخروج بنجاح');
    }

    /**
     * Webhook handler for Evolution API events
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->all();

            // Log the webhook data for debugging
            Log::info('Evolution Webhook Received', $data);

            // Get instance name from webhook data
            $instanceName = $data['instance'] ?? null;

            if (!$instanceName) {
                return response()->json(['status' => 'error', 'message' => 'Instance name not found'], 400);
            }

            // Find instance in database
            $instance = WhatsAppInstance::where('instance_name', $instanceName)->first();

            if (!$instance) {
                return response()->json(['status' => 'error', 'message' => 'Instance not found'], 404);
            }

            // Handle different event types
            $event = $data['event'] ?? null;

            switch ($event) {
                case 'qrcode.updated':
                    // Update QR code in database
                    $instance->update([
                        'qr_code' => $data['data']['qrcode']['base64'] ?? null,
                        'status' => 'pending',
                    ]);
                    break;

                case 'connection.update':
                    // Update connection status
                    $state = $data['data']['state'] ?? null;

                    if ($state === 'open') {
                        $connectionData = [
                            'phone_number' => $data['data']['instance']['wuid'] ?? null,
                            'profile_name' => $data['data']['instance']['profileName'] ?? null,
                            'profile_picture_url' => $data['data']['instance']['profilePictureUrl'] ?? null,
                        ];

                        $instance->updateConnectionStatus('connected', $connectionData);

                        // إطلاق Event
                        event(new InstanceConnected($instance, $connectionData));
                    } elseif ($state === 'close') {
                        $instance->updateConnectionStatus('disconnected');

                        // إطلاق Event
                        event(new InstanceDisconnected($instance));
                    }
                    break;

                case 'messages.upsert':
                    // Handle incoming messages
                    $messages = $data['data']['messages'] ?? [];

                    foreach ($messages as $message) {
                        // Check if message is from user (not from bot)
                        if (!($message['key']['fromMe'] ?? false)) {
                            // إطلاق Event
                            event(new \App\Events\WhatsApp\MessageReceived($instance, $message));

                            // Process incoming message
                            $this->processIncomingMessage($instance, $message);
                        }
                    }
                    break;

                case 'messages.update':
                    // Handle message updates (read receipts, etc.)
                    Log::info('Message Updated', $data['data']);
                    break;

                default:
                    Log::info('Unknown webhook event', ['event' => $event]);
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Process incoming message (to be customized for bot logic)
     * 
     * @param WhatsAppInstance $instance
     * @param array $message
     * @return void
     */
    protected function processIncomingMessage($instance, $message)
    {
        try {
            // استخدام خدمة البوت الآلي
            $botService = app(\App\Services\AutoReplyBotService::class);
            $botService->handleIncomingMessage($instance, $message);
        } catch (\Exception $e) {
            Log::error('Error processing incoming message', [
                'error' => $e->getMessage(),
                'instance' => $instance->instance_name ?? 'unknown'
            ]);
        }
    }
}
