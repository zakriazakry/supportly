<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppInstance;
use App\Services\AiManagerService;
use App\Services\EvolutionService;
use Illuminate\Support\Facades\Log;

class AutoReplyController extends Controller
{
    protected EvolutionService $evolutionService;
    protected AiManagerService $ai;


    public function __construct(EvolutionService $evolutionService, AiManagerService $ai)
    {
        $this->evolutionService = $evolutionService;
        $this->ai = $ai;
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
        $autoReply = $user->whasAppReply ?? null;

        $messageKey = $data['key'] ?? null;
        if ($messageKey) {
            $this->evolutionService->markAsRead($instanceName, [$messageKey]);
        }

        $this->aiReply($instanceName, $fromNumber, $message, "اسمك نوح بوت رد الي");

        // Auto reply conditions
        // if (!empty($autoReply->welcome)) {
        //     $this->welcomeReply($instanceName, $fromNumber, $message);
        // }

        // if (!empty($autoReply->ai)) {
        //     $this->aiReply($instanceName, $fromNumber, $message);
        // }

        // if (!empty($autoReply->number)) {
        //     $this->normalReply($instanceName, $fromNumber, $message);
        // }
    }

    private function welcomeReply($instanceName, $number, $msg)
    {
        $this->evolutionService->sendText($instanceName, $number, "مرحبا بك ي غالي \n كيف يمكنني مساعدتك؟");
    }

    private function aiReply($instanceName, $number, $msg, $system_prompt)
    {
        try {
            // Show typing indicator while AI is processing
            $this->evolutionService->sendChatPresence($instanceName, $number, 'composing', 8000);

            // Generate AI response
            $aiResponse = $this->ai->generate($msg, $system_prompt, 'ollama');

            // Send the AI response
            $this->evolutionService->sendText($instanceName, $number, $aiResponse);

            Log::info('AI Reply sent successfully', [
                'instance' => $instanceName,
                'to' => $number,
                'user_message' => $msg,
                'ai_response_length' => strlen($aiResponse)
            ]);
        } catch (\Exception $e) {
            Log::error('AI Reply failed', [
                'error' => $e->getMessage(),
                'instance' => $instanceName,
                'to' => $number
            ]);

            // Send fallback message on error
            $this->evolutionService->sendText(
                $instanceName,
                $number,
                "شكراً لتواصلك معنا! 🙏\nنعتذر عن التأخير، سيتم الرد عليك قريباً."
            );
        }
    }

    private function normalReply($instanceName, $number, $msg)
    {
        // normal reply logic - echo back the message
        $this->evolutionService->sendText(
            $instanceName,
            $number,
            "تم استلام رسالتك: " . $msg
        );
    }
}
