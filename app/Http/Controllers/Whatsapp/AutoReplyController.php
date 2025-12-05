<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppInstance;
use App\Services\EvolutionService;
use App\Services\OllamaService;
use Illuminate\Support\Facades\Log;

class AutoReplyController extends Controller
{
    protected EvolutionService $evolutionService;
    protected OllamaService $ollamaService;

    public function __construct(EvolutionService $evolutionService, OllamaService $ollamaService)
    {
        $this->evolutionService = $evolutionService;
        $this->ollamaService = $ollamaService;
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

        $this->aiReply($instanceName, $fromNumber, $message);

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

    private function aiReply($instanceName, $number, $msg)
    {
        try {
            // Start a background process to keep typing indicator active
            $keepTyping = true;
            $typingInterval = 10; // seconds - refresh typing indicator every 10 seconds

            // Show initial typing indicator
            $this->evolutionService->sendChatPresence($instanceName, $number, 'composing', 0);

            // Generate AI response (this may take a while)
            // We use a closure to periodically refresh the typing indicator
            $startTime = time();
            $lastTypingUpdate = $startTime;

            // Create a promise-like pattern using a generator or process the AI request
            // Since PHP is synchronous, we'll refresh typing before and use a longer delay
            $aiResponse = $this->generateAiResponseWithTyping($instanceName, $number, $msg);

            // Stop typing indicator (send 'paused' to stop composing)
            $this->evolutionService->sendChatPresence($instanceName, $number, 'paused', 0);

            // Send the AI response
            $this->evolutionService->sendText($instanceName, $number, $aiResponse);

            Log::info('AI Reply sent successfully', [
                'instance' => $instanceName,
                'to' => $number,
                'user_message' => $msg,
                'ai_response_length' => strlen($aiResponse),
                'processing_time_seconds' => time() - $startTime
            ]);
        } catch (\Exception $e) {
            // Stop typing indicator on error
            $this->evolutionService->sendChatPresence($instanceName, $number, 'paused', 0);

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

    /**
     * Generate AI response with periodic typing indicator refresh
     * This method handles the AI request and refreshes typing status for long requests
     */
    private function generateAiResponseWithTyping($instanceName, $number, $msg)
    {
        // For very long AI responses, we use streaming or chunked approach
        // Since Ollama might take time, we set a reasonable timeout and refresh typing

        // Use cURL with a custom callback to refresh typing during the request
        $aiResponse = '';
        $lastTypingRefresh = time();
        $typingRefreshInterval = 8; // Refresh every 8 seconds

        // Make the AI request with streaming support if available
        try {
            // Attempt to use streaming for real-time updates
            $aiResponse = $this->ollamaService->generateSupportReplyWithCallback(
                $msg,
                function () use ($instanceName, $number, &$lastTypingRefresh, $typingRefreshInterval) {
                    // Refresh typing indicator periodically during streaming
                    if (time() - $lastTypingRefresh >= $typingRefreshInterval) {
                        $this->evolutionService->sendChatPresence($instanceName, $number, 'composing', 0);
                        $lastTypingRefresh = time();
                    }
                }
            );
        } catch (\BadMethodCallException $e) {
            // Fallback: If streaming method doesn't exist, use the regular method
            // Show typing indicator with maximum delay
            $this->evolutionService->sendChatPresence($instanceName, $number, 'composing', 0);
            $aiResponse = $this->ollamaService->generateSupportReply($msg);
        }

        return $aiResponse;
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
