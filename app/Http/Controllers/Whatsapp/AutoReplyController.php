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
        $botPrompt = "";

        $messageKey = $data['key'] ?? null;
        if ($messageKey) {
            $this->evolutionService->markAsRead($instanceName, [$messageKey]);
        } else {
            Log::warning('Missing message key in WhatsApp webhook', $data);
        }

        $this->aiReply($instanceName, $fromNumber, $message, $botPrompt);

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
            $start = microtime(true);


            $aiResponse = $this->ai->generate($msg, $system_prompt, 'openai');
            $this->evolutionService->sendChatPresence($instanceName, $number, 'composing', 2000);

            // إرسال الرد النهائي
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
                'to' => $number,
                'user_message' => $msg
            ]);

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
