<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EvolutionService;
use App\Models\WhatsAppInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Example Controller for Evolution API Integration
 * 
 * This controller demonstrates how to use EvolutionService
 * for managing WhatsApp instances and sending messages
 */
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
    public function createInstance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'instance_name' => 'required|string|unique:whats_app_instances,instance_name',
            'integration' => 'nullable|in:WHATSAPP-BAILEYS,WHATSAPP-BUSINESS',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        // Create instance via Evolution API
        $result = $this->evolutionService->createInstance([
            'instanceName' => $request->instance_name,
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
            'settings' => [
                'rejectCall' => true,
                'msgCall' => 'عذراً، لا أقبل المكالمات',
                'alwaysOnline' => true,
                'readMessages' => false,
            ]
        ]);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Save instance to database
        $instance = WhatsAppInstance::create([
            'user_id' => $request->user()->id,
            'instance_name' => $request->instance_name,
            'token' => $result['data']['hash'] ?? null,
            'qr_code' => $result['data']['qrcode']['base64'] ?? null,
            'status' => 'qr_code',
            'integration_type' => $request->integration ?? 'WHATSAPP-BAILEYS',
        ]);

        return responseFormat([
            'message' => 'تم إنشاء الـ instance بنجاح',
            'instance' => $instance,
            'qr_code' => $result['data']['qrcode']['base64'] ?? null,
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
            ->firstOrFail();

        $result = $this->evolutionService->connectInstance($instanceName);

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        // Update QR code in database
        $instance->update([
            'qr_code' => $result['data']['base64'] ?? null,
            'status' => 'qr_code',
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

        $result = $this->evolutionService->sendText($instanceName, [
            'number' => $request->number,
            'text' => $request->text,
        ]);

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

        $result = $this->evolutionService->sendMedia($instanceName, [
            'number' => $request->number,
            'mediatype' => $request->media_type,
            'media' => $request->media_url,
            'caption' => $request->caption,
            'fileName' => $request->file_name,
        ]);

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
            $request->participants,
            $request->description
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
            ['key' => ['remoteJid' => $request->remote_jid]],
            $request->page ?? 1,
            $request->limit ?? 20
        );

        if (!$result['success']) {
            return responseFormat($result['error'], 500);
        }

        return responseFormat($result['data']);
    }

    /**
     * Update profile settings
     * 
     * @param Request $request
     * @param string $instanceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request, $instanceName)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'status' => 'nullable|string',
            'picture_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->where('status', 'connected')
            ->firstOrFail();

        $results = [];

        // Update name
        if ($request->has('name')) {
            $result = $this->evolutionService->updateProfileName($instanceName, $request->name);
            $results['name'] = $result;
        }

        // Update status
        if ($request->has('status')) {
            $result = $this->evolutionService->updateProfileStatus($instanceName, $request->status);
            $results['status'] = $result;
        }

        // Update picture
        if ($request->has('picture_url')) {
            $result = $this->evolutionService->updateProfilePicture($instanceName, $request->picture_url);
            $results['picture'] = $result;
        }

        return responseFormat([
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => $results
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
}
