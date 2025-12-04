<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppInstance;
use App\Services\EvolutionService;
use Illuminate\Support\Facades\Log;

class AutoReplyController extends Controller
{
    protected EvolutionService $evolutionService;

    public function __construct(EvolutionService $service)
    {
        $this->evolutionService = $service;
    }

    public function whenReceiveTextMessage(array $data)
    {
        // FIXED: using correct webhook keys
        $instanceName = $data['instanceName'] ?? null;
        $message      = $data['message'] ?? null;
        $fromNumber   = $data['form_number'] ?? null;

        if (!$instanceName || !$fromNumber || !$message) {
            Log::warning('Missing required fields in WhatsApp webhook', $data);
            return;
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)->first();
        if (!$instance) {
            Log::error('WhatsApp instance not found', ['instance_name' => $instanceName]);
            return;
        }

        $user = User::find($instance->user_id);
        if (!$user) {
            Log::error('User not found for instance', ['user_id' => $instance->user_id]);
            return;
        }

        // Auto-reply 
        $autoReply = $user->whasAppReply ?? null;

        // if (!$autoReply) {
        //     Log::error('User auto-reply settings missing or expired', ['user_id' => $user->id]);
        //     return;
        // }

        // Safe to use $autoReply now (no more undefined errors)

        // Mark message as read (seen)
        $messageKey = $data['key'] ?? null;
        if ($messageKey) {
            $this->evolutionService->markAsRead($instanceName, [$messageKey]);
        }

        // Show typing indicator
        $this->evolutionService->sendChatPresence($instanceName, $fromNumber, 'composing', 5000);

        // Default echo-back message
        $this->evolutionService->sendText($instanceName, $fromNumber, "مرحبا بك ي غالي \n كيف يمكنني مساعدتك؟");

        // Auto reply conditions
        if (!empty($autoReply->welcome)) {
            $this->welcomeReply($instanceName, $fromNumber, $message);
        }

        if (!empty($autoReply->ai)) {
            $this->aiReply($instanceName, $fromNumber, $message);
        }

        if (!empty($autoReply->number)) {
            $this->normalReply($instanceName, $fromNumber, $message);
        }
    }

    private function welcomeReply($instanceName, $number, $msg)
    {
        $this->evolutionService->sendText($instanceName, $number, "مرحبا بك ي غالي \n كيف يمكنني مساعدتك؟");
    }

    private function aiReply($instanceName, $number, $msg)
    {
        // your AI logic here
    }

    private function normalReply($instanceName, $number, $msg)
    {
        // normal reply logic
    }
}
