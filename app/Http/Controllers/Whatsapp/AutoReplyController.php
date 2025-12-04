<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppInstance;
use App\Services\EvolutionService;
use Illuminate\Http\Request;
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
        $instanceName = $data['instance'] ?? null;
        $message      = $data['message'] ?? null;
        $fromNumber   = $data['fromNumber'] ?? null;

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

        $autoReply = $user->whasAppReply;
        if (!$autoReply) {
            Log::error('User auto-reply settings not found or expired', ['user_id' => $user->id]);
            return;
        }

        // Always send back the received message (if intended)
        $this->evolutionService->sendText($instanceName, $fromNumber, $message);

        // Conditional automatic replies
        if ($autoReply->welcome) {
            $this->welcomeReply($instanceName, $fromNumber, $message);
        }

        if ($autoReply->ai) {
            $this->aiReply($instanceName, $fromNumber, $message);
        }

        if ($autoReply->number) {
            $this->normalReply($instanceName, $fromNumber, $message);
        }
    }

    private function welcomeReply($instanceName, $number, $msg)
    {
        // Example:
        $this->evolutionService->sendText($instanceName, $number, "Welcome!");
    }

    private function aiReply($instanceName, $number, $msg)
    {
        // Example:
        // $reply = $this->generateAIResponse($msg);
        // $this->evolutionService->sendText($instanceName, $number, $reply);
    }

    private function normalReply($instanceName, $number, $msg)
    {
        // Example:
        // $this->evolutionService->sendText($instanceName, $number, "Thanks for your message!");
    }
}
