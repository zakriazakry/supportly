<?php

namespace App\Http\Controllers\Evolution;

use App\Http\Controllers\Controller;
use App\Services\EvolutionService;
use App\Models\WhatsAppInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $evolutionService;

    public function __construct(EvolutionService $service)
    {
        $this->evolutionService = $service;
    }

    /**
     * Handle incoming webhooks from Evolution API
     */
    public function handle(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('Evolution Webhook Received', [
                'event' => $data['event'] ?? 'unknown',
                'instance' => $data['instance'] ?? 'unknown',
                'data' => $data
            ]);

            // Get event type
            $event = $data['event'] ?? null;

            if (!$event) {
                return responseFormat('No event type provided', 400);
            }

            // Route to appropriate handler based on event type
            $method = $this->getHandlerMethod($event);

            if (method_exists($this, $method)) {
                $this->$method($data);
            } else {
                Log::warning('No handler for event type', ['event' => $event]);
            }

            return responseFormat('ok');
        } catch (\Exception $e) {
            Log::error('Webhook handling error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return responseFormat($e->getMessage(), 500);
        }
    }

    /**
     * Get handler method name from event type
     */
    protected function getHandlerMethod($event)
    {
        // Convert event name to method name
        // e.g., MESSAGES_UPSERT -> handleMessagesUpsert
        $parts = explode('_', strtolower($event));
        $method = 'handle';

        foreach ($parts as $part) {
            $method .= ucfirst($part);
        }

        return $method;
    }

    // ==================== APPLICATION EVENTS ====================

    /**
     * Handle application startup event
     */
    protected function handleApplicationStartup($data)
    {
        Log::info('Application started', [
            'instance' => $data['instance'] ?? null
        ]);
    }

    // ==================== QRCODE EVENTS ====================

    /**
     * Handle QR code update event
     */
    protected function handleQrcodeUpdated($data)
    {
        $instanceName = $data['instance'] ?? null;
        $qrcode = $data['data']['qrcode'] ?? null;

        if ($instanceName && $qrcode) {
            // Update instance with QR code
            WhatsAppInstance::where('instance_name', $instanceName)
                ->update([
                    'qr_code' => $qrcode['base64'] ?? null,
                    'status' => 'qr_code',
                ]);

            Log::info('QR Code updated', ['instance' => $instanceName]);
        }
    }

    // ==================== CONNECTION EVENTS ====================

    /**
     * Handle connection update event
     */
    protected function handleConnectionUpdate($data)
    {
        $instanceName = $data['instance'] ?? null;
        $state = $data['data']['state'] ?? null;

        if ($instanceName) {
            $status = match ($state) {
                'open' => 'connected',
                'close' => 'disconnected',
                'connecting' => 'connecting',
                default => 'unknown'
            };

            WhatsAppInstance::where('instance_name', $instanceName)
                ->update([
                    'status' => $status,
                    'connection_state' => $state,
                ]);

            Log::info('Connection state updated', [
                'instance' => $instanceName,
                'state' => $state
            ]);
        }
    }

    // ==================== MESSAGE EVENTS ====================

    /**
     * Handle messages set event (initial message load)
     */
    protected function handleMessagesSet($data)
    {
        Log::info('Messages set received', [
            'instance' => $data['instance'] ?? null,
            'count' => count($data['data']['messages'] ?? [])
        ]);

        // Process initial messages if needed
        $this->processMessages($data);
    }

    /**
     * Handle new message event
     */
    protected function handleMessagesUpsert($data)
    {
        Log::info('New message received', [
            'instance' => $data['instance'] ?? null,
            'message' => $data['data'] ?? null
        ]);

        // Process new message
        $this->processMessages($data);
    }

    /**
     * Handle message update event
     */
    protected function handleMessagesUpdate($data)
    {
        Log::info('Message updated', [
            'instance' => $data['instance'] ?? null,
            'message' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle message delete event
     */
    protected function handleMessagesDelete($data)
    {
        Log::info('Message deleted', [
            'instance' => $data['instance'] ?? null,
            'message' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle sent message event
     */
    protected function handleSendMessage($data)
    {
        Log::info('Message sent', [
            'instance' => $data['instance'] ?? null,
            'message' => $data['data'] ?? null
        ]);
    }

    /**
     * Process messages (common logic for message events)
     */
    protected function processMessages($data)
    {
        $instanceName = $data['instance'] ?? null;
        $messages = $data['data']['messages'] ?? [$data['data']];

        foreach ($messages as $message) {
            // Extract message details
            $key = $message['key'] ?? null;
            $messageContent = $message['message'] ?? null;

            if (!$key) continue;

            $remoteJid = $key['remoteJid'] ?? null;
            $fromMe = $key['fromMe'] ?? false;
            $messageId = $key['id'] ?? null;

            // Get message text
            $text = $this->extractMessageText($messageContent);

            // Store or process message as needed
            Log::info('Processing message', [
                'instance' => $instanceName,
                'from' => $remoteJid,
                'fromMe' => $fromMe,
                'messageId' => $messageId,
                'text' => $text
            ]);

            // TODO: Add your custom message processing logic here
            // For example: save to database, trigger bot responses, etc.
        }
    }

    /**
     * Extract text from message content
     */
    protected function extractMessageText($messageContent)
    {
        if (!$messageContent) return null;

        // Check different message types
        if (isset($messageContent['conversation'])) {
            return $messageContent['conversation'];
        }

        if (isset($messageContent['extendedTextMessage']['text'])) {
            return $messageContent['extendedTextMessage']['text'];
        }

        if (isset($messageContent['imageMessage']['caption'])) {
            return $messageContent['imageMessage']['caption'];
        }

        if (isset($messageContent['videoMessage']['caption'])) {
            return $messageContent['videoMessage']['caption'];
        }

        return null;
    }

    // ==================== CONTACT EVENTS ====================

    /**
     * Handle contacts set event
     */
    protected function handleContactsSet($data)
    {
        Log::info('Contacts set received', [
            'instance' => $data['instance'] ?? null,
            'count' => count($data['data']['contacts'] ?? [])
        ]);
    }

    /**
     * Handle contact upsert event
     */
    protected function handleContactsUpsert($data)
    {
        Log::info('Contact upserted', [
            'instance' => $data['instance'] ?? null,
            'contact' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle contact update event
     */
    protected function handleContactsUpdate($data)
    {
        Log::info('Contact updated', [
            'instance' => $data['instance'] ?? null,
            'contact' => $data['data'] ?? null
        ]);
    }

    // ==================== PRESENCE EVENTS ====================

    /**
     * Handle presence update event
     */
    protected function handlePresenceUpdate($data)
    {
        Log::info('Presence updated', [
            'instance' => $data['instance'] ?? null,
            'presence' => $data['data'] ?? null
        ]);
    }

    // ==================== CHAT EVENTS ====================

    /**
     * Handle chats set event
     */
    protected function handleChatsSet($data)
    {
        Log::info('Chats set received', [
            'instance' => $data['instance'] ?? null,
            'count' => count($data['data']['chats'] ?? [])
        ]);
    }

    /**
     * Handle chat upsert event
     */
    protected function handleChatsUpsert($data)
    {
        Log::info('Chat upserted', [
            'instance' => $data['instance'] ?? null,
            'chat' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle chat update event
     */
    protected function handleChatsUpdate($data)
    {
        Log::info('Chat updated', [
            'instance' => $data['instance'] ?? null,
            'chat' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle chat delete event
     */
    protected function handleChatsDelete($data)
    {
        Log::info('Chat deleted', [
            'instance' => $data['instance'] ?? null,
            'chat' => $data['data'] ?? null
        ]);
    }

    // ==================== GROUP EVENTS ====================

    /**
     * Handle groups upsert event
     */
    protected function handleGroupsUpsert($data)
    {
        Log::info('Group upserted', [
            'instance' => $data['instance'] ?? null,
            'group' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle group update event
     */
    protected function handleGroupUpdate($data)
    {
        Log::info('Group updated', [
            'instance' => $data['instance'] ?? null,
            'group' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle group participants update event
     */
    protected function handleGroupParticipantsUpdate($data)
    {
        Log::info('Group participants updated', [
            'instance' => $data['instance'] ?? null,
            'update' => $data['data'] ?? null
        ]);
    }

    // ==================== LABEL EVENTS ====================

    /**
     * Handle labels edit event
     */
    protected function handleLabelsEdit($data)
    {
        Log::info('Labels edited', [
            'instance' => $data['instance'] ?? null,
            'labels' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle labels association event
     */
    protected function handleLabelsAssociation($data)
    {
        Log::info('Labels associated', [
            'instance' => $data['instance'] ?? null,
            'association' => $data['data'] ?? null
        ]);
    }

    // ==================== CALL EVENTS ====================

    /**
     * Handle call event
     */
    protected function handleCall($data)
    {
        Log::info('Call received', [
            'instance' => $data['instance'] ?? null,
            'call' => $data['data'] ?? null
        ]);

        // TODO: Handle incoming calls (e.g., auto-reject, notify user, etc.)
    }

    // ==================== TYPEBOT EVENTS ====================

    /**
     * Handle Typebot start event
     */
    protected function handleTypebotStart($data)
    {
        Log::info('Typebot started', [
            'instance' => $data['instance'] ?? null,
            'typebot' => $data['data'] ?? null
        ]);
    }

    /**
     * Handle Typebot status change event
     */
    protected function handleTypebotChangeStatus($data)
    {
        Log::info('Typebot status changed', [
            'instance' => $data['instance'] ?? null,
            'status' => $data['data'] ?? null
        ]);
    }
}
