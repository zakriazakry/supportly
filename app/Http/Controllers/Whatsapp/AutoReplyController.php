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

    /**
     * Handle incoming text message from webhook
     *
     * Example $data structure:
     *  'from' => $sender,
     *  'form_number' => explode('@', $sender)[0],
     *  'to' => $receiver,
     *  'message' => $messageContent['conversation'],
     *  'pushName' => $pushName,
     *  'messageTimestamp' => $messageTimestamp,
     *  'messageId' => $messageId,
     *  'fromMe' => $fromMe,
     *  'remoteJid' => $remoteJid,
     *  'key' => $key,
     *  'messageInfo' => $messageInfo,
     *  'instanceName' => $instanceName,
     */
    public function whenReceiveTextMessage(array $data)
    {
        $instanceName = $data['instanceName'] ?? null;   // FIXED
        $message      = $data['message'] ?? null;
        $fromNumber   = $data['form_number'] ?? null;    // FIXED

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

        // $autoReply = $user->whasAppReply;
        // if (!$autoReply) {
        //     Log::error('User auto-reply settings missing or expired', ['user_id' => $user->id]);
        //     return;
        // }

        // Basic echo-back or default processing
        $this->evolutionService->sendText($instanceName, $fromNumber, $message);

        // // Auto-reply features
        // if ($autoReply->welcome) {
        //     $this->welcomeReply($instanceName, $fromNumber, $message);
        // }

        // if ($autoReply->ai) {
        //     $this->aiReply($instanceName, $fromNumber, $message);
        // }

        // if ($autoReply->number) {
        //     $this->normalReply($instanceName, $fromNumber, $message);
        // }
    }

    private function welcomeReply($instanceName, $number, $msg)
    {
        $this->evolutionService->sendText($instanceName, $number, "Welcome!");
    }

    private function aiReply($instanceName, $number, $msg)
    {
        // Example AI logic:
        // $response = $this->aiService->reply($msg);
        // $this->evolutionService->sendText($instanceName, $number, $response);
    }

    private function normalReply($instanceName, $number, $msg)
    {
        // Example basic reply:
        // $this->evolutionService->sendText($instanceName, $number, "Thanks for your message!");
    }
}
