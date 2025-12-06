<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Whatsapp\AutoReplyController;
use App\Services\EvolutionService;
use App\Models\WhatsAppInstance;
use App\Services\AiManagerService;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\info;

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

            Log::info(' -----data -----', $data);

            // Get event type
            $event = $data['event'] ?? null;

            if (!$event) {
                Log::warning('Webhook received without event type', ['data' => $data]);
                return responseFormat('No event type provided', 400);
            }

            // Route to appropriate handler based on event type
            $method = $this->getHandlerMethod($event);

            if (method_exists($this, $method)) {
                $this->$method($data);
            } else {
                Log::warning('No handler for event type', [
                    'event' => $event,
                    'method_attempted' => $method
                ]);
            }

            return responseFormat('ok');
        } catch (\Exception $e) {
            Log::error('Webhook handling error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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
        // e.g., MESSAGES.UPSERT -> handleMessagesUpsert
        $parts = explode('.', strtolower($event));
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
        Log::info('📱 Application Started', [
            'instance' => $data['instance'] ?? null,
            'timestamp' => now()->toDateTimeString()
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

        Log::info('🔲 QR Code Updated', [
            'instance' => $instanceName,
            'has_qrcode' => !empty($qrcode),
            'timestamp' => now()->toDateTimeString()
        ]);

        if ($instanceName && $qrcode) {
            // Update instance with QR code
            WhatsAppInstance::where('instance_name', $instanceName)
                ->update([
                    'qr_code' => $qrcode['base64'] ?? null,
                    'status' => 'qr_code',
                ]);

            Log::info('✅ QR Code saved to database', ['instance' => $instanceName]);
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

        $status = match ($state) {
            'open' => 'connected',
            'close' => 'disconnected',
            'connecting' => 'connecting',
            default => 'unknown'
        };


        if ($instanceName) {
            WhatsAppInstance::where('instance_name', $instanceName)
                ->update([
                    'status' => $status,
                ]);

            Log::info('✅ Connection status updated in database', [
                'instance' => $instanceName,
                'status' => $status
            ]);
        }
    }

    // ==================== MESSAGE EVENTS ====================

    /**
     * Handle messages set event (initial message load)
     */
    protected function handleMessagesSet($data)
    {
        $messageCount = count($data['data']['messages'] ?? []);

        Log::info('📦 Messages Set Received', [
            'instance' => $data['instance'] ?? null,
            'message_count' => $messageCount,
            'timestamp' => now()->toDateTimeString()
        ]);

        // Process initial messages if needed
        $this->processMessages($data);
    }

    /**
     * Handle new message event
     */
    protected function handleMessagesUpsert($data)
    {

        $this->processMessages($data);
    }

    /**
     * Handle message update event
     */
    protected function handleMessagesUpdate($data)
    {
        Log::info('🔄 Message Updated', [
            'instance' => $data['instance'] ?? null,
            'update_data' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle message delete event
     */
    protected function handleMessagesDelete($data)
    {
        Log::info('🗑️ Message Deleted', [
            'instance' => $data['instance'] ?? null,
            'delete_data' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle sent message event
     */
    protected function handleSendMessage($data)
    {
        Log::info('📤 Message Sent', [
            'instance' => $data['instance'] ?? null,
            'message_data' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
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
            $key = $message['key'] ?? null;
            $messageContent = $message['message'] ?? null;
            $pushName = $message['pushName'] ?? 'Unknown';
            $messageTimestamp = $message['messageTimestamp'] ?? null;

            if (!$key) {
                Log::warning('⚠️ Message without key received', ['message' => $message]);
                continue;
            }

            $remoteJid = $key['remoteJid'] ?? null;
            $remoteJidAlt = $key['remoteJidAlt'] ?? null;
            $fromMe = $key['fromMe'] ?? false;
            $messageId = $key['id'] ?? null;

            $sender = $fromMe ? 'Me (Bot)' : $remoteJid;
            $receiver = $fromMe ? $remoteJid : 'Me (Bot)';

            $messageInfo = $this->extractMessageInfo($messageContent);
            $phone = extractPhone($remoteJid, $remoteJidAlt);
            switch ($messageInfo['type']) {
                case 'text':
                    $autoReplyController = new AutoReplyController(new EvolutionService(), new AiManagerService());
                    $autoReplyController->whenReceiveTextMessage([
                        'from' => $sender,
                        'form_number' => $phone,
                        'to' => $receiver,
                        'message' => $messageContent['conversation'],
                        'pushName' => $pushName,
                        'messageTimestamp' => $messageTimestamp,
                        'messageId' => $messageId,
                        'fromMe' => $fromMe,
                        'remoteJid' => $remoteJid,
                        'key' => $key,
                        'messageInfo' => $messageInfo,
                        'instanceName' => $instanceName,
                    ]);
                    break;

                case 'image':
                    Log::info('🖼️ Image Message', [
                        'from' => $sender,
                        'form_number' => explode('@', $sender)[0],
                        'to' => $receiver,
                        'image' => $data['message']['image'] ?? null,
                        'caption' => $messageInfo['content'],
                        'mimetype' => $messageInfo['media_info']['mimetype'] ?? null,
                        'file_size' => $messageInfo['media_info']['fileLength'] ?? null,
                    ]);
                    break;

                case 'video':
                    Log::info('🎥 Video Message', [
                        'from' => $sender,
                        'to' => $receiver,
                        'caption' => $messageInfo['content'],
                        'video' => $messageInfo['media_info']['url'] ?? null,
                        'mimetype' => $messageInfo['media_info']['mimetype'] ?? null,
                        'duration' => $messageInfo['media_info']['seconds'] ?? null,
                        'file_size' => $messageInfo['media_info']['fileLength'] ?? null,
                    ]);
                    break;

                case 'audio':
                    Log::info('🎵 Audio Message', [
                        'from' => $sender,
                        'to' => $receiver,
                        'duration' => $messageInfo['media_info']['seconds'] ?? null,
                        'audio' => $messageInfo['media_info']['url'] ?? null,
                        'is_ptt' => $messageInfo['media_info']['ptt'] ?? false,
                        'mimetype' => $messageInfo['media_info']['mimetype'] ?? null,
                    ]);
                    break;

                case 'document':
                    Log::info('📄 Document Message', [
                        'from' => $sender,
                        'to' => $receiver,
                        'filename' => $messageInfo['media_info']['fileName'] ?? null,
                        'mimetype' => $messageInfo['media_info']['mimetype'] ?? null,
                        'file_size' => $messageInfo['media_info']['fileLength'] ?? null,
                        'caption' => $messageInfo['content'],
                    ]);
                    break;

                case 'sticker':
                    Log::info('🎭 Sticker Message', [
                        'from' => $sender,
                        'to' => $receiver,
                        'mimetype' => $messageInfo['media_info']['mimetype'] ?? null,
                        'sticker' => $messageInfo['media_info']['url'] ?? null,
                        'caption' => $messageInfo['content'],
                    ]);
                    break;

                case 'location':
                    Log::info('📍 Location Message', [
                        'from' => $sender,
                        'to' => $receiver,
                        'latitude' => $messageInfo['media_info']['latitude'] ?? null,
                        'longitude' => $messageInfo['media_info']['longitude'] ?? null,
                        'address' => $messageInfo['content'],
                    ]);
                    break;

                case 'contact':
                    Log::info('👤 Contact Message', [
                        'from' => $sender,
                        'to' => $receiver,
                        'contact_info' => $messageInfo['content'],
                    ]);
                    break;

                default:
                    Log::info('❓ Unknown Message Type', [
                        'from' => $sender,
                        'to' => $receiver,
                        'type' => $messageInfo['type'],
                        'raw_content' => $messageContent,
                    ]);
                    break;
            }

            // TODO: Add your custom message processing logic here
            // For example: save to database, trigger bot responses, etc.
        }
    }

    /**
     * Extract message information including type and content
     */
    protected function extractMessageInfo($messageContent)
    {
        if (!$messageContent) {
            return [
                'type' => 'unknown',
                'content' => null,
            ];
        }

        // Text messages
        if (isset($messageContent['conversation'])) {
            return [
                'type' => 'text',
                'content' => $messageContent['conversation'],
            ];
        }

        // Extended text message (with mentions, links, etc.)
        if (isset($messageContent['extendedTextMessage'])) {
            return [
                'type' => 'text',
                'content' => $messageContent['extendedTextMessage']['text'] ?? null,
                'context_info' => $messageContent['extendedTextMessage']['contextInfo'] ?? null,
            ];
        }

        // Image message
        if (isset($messageContent['imageMessage'])) {
            return [
                'type' => 'image',
                'content' => $messageContent['imageMessage']['caption'] ?? null,
                'media_info' => [
                    'mimetype' => $messageContent['imageMessage']['mimetype'] ?? null,
                    'fileLength' => $messageContent['imageMessage']['fileLength'] ?? null,
                    'height' => $messageContent['imageMessage']['height'] ?? null,
                    'width' => $messageContent['imageMessage']['width'] ?? null,
                ],
            ];
        }

        // Video message
        if (isset($messageContent['videoMessage'])) {
            return [
                'type' => 'video',
                'content' => $messageContent['videoMessage']['caption'] ?? null,
                'media_info' => [
                    'mimetype' => $messageContent['videoMessage']['mimetype'] ?? null,
                    'fileLength' => $messageContent['videoMessage']['fileLength'] ?? null,
                    'seconds' => $messageContent['videoMessage']['seconds'] ?? null,
                    'height' => $messageContent['videoMessage']['height'] ?? null,
                    'width' => $messageContent['videoMessage']['width'] ?? null,
                ],
            ];
        }

        // Audio message (including voice notes)
        if (isset($messageContent['audioMessage'])) {
            return [
                'type' => 'audio',
                'content' => null,
                'media_info' => [
                    'mimetype' => $messageContent['audioMessage']['mimetype'] ?? null,
                    'fileLength' => $messageContent['audioMessage']['fileLength'] ?? null,
                    'seconds' => $messageContent['audioMessage']['seconds'] ?? null,
                    'ptt' => $messageContent['audioMessage']['ptt'] ?? false, // Push to talk (voice note)
                ],
            ];
        }

        // Document message
        if (isset($messageContent['documentMessage'])) {
            return [
                'type' => 'document',
                'content' => $messageContent['documentMessage']['caption'] ?? null,
                'media_info' => [
                    'fileName' => $messageContent['documentMessage']['fileName'] ?? null,
                    'mimetype' => $messageContent['documentMessage']['mimetype'] ?? null,
                    'fileLength' => $messageContent['documentMessage']['fileLength'] ?? null,
                ],
            ];
        }

        // Sticker message
        if (isset($messageContent['stickerMessage'])) {
            return [
                'type' => 'sticker',
                'content' => null,
                'media_info' => [
                    'mimetype' => $messageContent['stickerMessage']['mimetype'] ?? null,
                    'fileLength' => $messageContent['stickerMessage']['fileLength'] ?? null,
                ],
            ];
        }

        // Location message
        if (isset($messageContent['locationMessage'])) {
            return [
                'type' => 'location',
                'content' => $messageContent['locationMessage']['address'] ?? null,
                'media_info' => [
                    'latitude' => $messageContent['locationMessage']['degreesLatitude'] ?? null,
                    'longitude' => $messageContent['locationMessage']['degreesLongitude'] ?? null,
                ],
            ];
        }

        // Contact message
        if (isset($messageContent['contactMessage'])) {
            return [
                'type' => 'contact',
                'content' => $messageContent['contactMessage']['displayName'] ?? null,
                'media_info' => [
                    'vcard' => $messageContent['contactMessage']['vcard'] ?? null,
                ],
            ];
        }

        // Contact array message
        if (isset($messageContent['contactsArrayMessage'])) {
            return [
                'type' => 'contacts',
                'content' => 'Multiple contacts',
                'media_info' => [
                    'contacts' => $messageContent['contactsArrayMessage']['contacts'] ?? [],
                ],
            ];
        }

        // Live location message
        if (isset($messageContent['liveLocationMessage'])) {
            return [
                'type' => 'live_location',
                'content' => $messageContent['liveLocationMessage']['caption'] ?? null,
                'media_info' => [
                    'latitude' => $messageContent['liveLocationMessage']['degreesLatitude'] ?? null,
                    'longitude' => $messageContent['liveLocationMessage']['degreesLongitude'] ?? null,
                ],
            ];
        }

        // Reaction message
        if (isset($messageContent['reactionMessage'])) {
            return [
                'type' => 'reaction',
                'content' => $messageContent['reactionMessage']['text'] ?? null,
                'media_info' => [
                    'key' => $messageContent['reactionMessage']['key'] ?? null,
                ],
            ];
        }

        // Poll message
        if (isset($messageContent['pollCreationMessage'])) {
            return [
                'type' => 'poll',
                'content' => $messageContent['pollCreationMessage']['name'] ?? null,
                'media_info' => [
                    'options' => $messageContent['pollCreationMessage']['options'] ?? [],
                ],
            ];
        }

        return [
            'type' => 'unknown',
            'content' => json_encode($messageContent),
        ];
    }

    /**
     * Extract text from message content (legacy method for backward compatibility)
     */
    protected function extractMessageText($messageContent)
    {
        $info = $this->extractMessageInfo($messageContent);
        return $info['content'];
    }

    // ==================== CONTACT EVENTS ====================

    /**
     * Handle contacts set event
     */
    protected function handleContactsSet($data)
    {
        $contactCount = count($data['data']['contacts'] ?? []);

        Log::info('👥 Contacts Set Received', [
            'instance' => $data['instance'] ?? null,
            'contact_count' => $contactCount,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle contact upsert event
     */
    protected function handleContactsUpsert($data)
    {
        Log::info('👤 Contact Upserted', [
            'instance' => $data['instance'] ?? null,
            'contact' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle contact update event
     */
    protected function handleContactsUpdate($data)
    {
        Log::info('👤 Contact Updated', [
            'instance' => $data['instance'] ?? null,
            'contact' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    // ==================== PRESENCE EVENTS ====================

    /**
     * Handle presence update event
     */
    protected function handlePresenceUpdate($data)
    {
        Log::info('👁️ Presence Updated', [
            'instance' => $data['instance'] ?? null,
            'presence' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    // ==================== CHAT EVENTS ====================

    /**
     * Handle chats set event
     */
    protected function handleChatsSet($data)
    {
        $chatCount = count($data['data']['chats'] ?? []);

        Log::info('💬 Chats Set Received', [
            'instance' => $data['instance'] ?? null,
            'chat_count' => $chatCount,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle chat upsert event
     */
    protected function handleChatsUpsert($data)
    {
        Log::info('💬 Chat Upserted', [
            'instance' => $data['instance'] ?? null,
            'chat' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle chat update event
     */
    protected function handleChatsUpdate($data)
    {
        Log::info('💬 Chat Updated', [
            'instance' => $data['instance'] ?? null,
            'chat' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle chat delete event
     */
    protected function handleChatsDelete($data)
    {
        Log::info('🗑️ Chat Deleted', [
            'instance' => $data['instance'] ?? null,
            'chat' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    // ==================== GROUP EVENTS ====================

    /**
     * Handle groups upsert event
     */
    protected function handleGroupsUpsert($data)
    {
        Log::info('👥 Group Upserted', [
            'instance' => $data['instance'] ?? null,
            'group' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle group update event
     */
    protected function handleGroupUpdate($data)
    {
        Log::info('👥 Group Updated', [
            'instance' => $data['instance'] ?? null,
            'group' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle group participants update event
     */
    protected function handleGroupParticipantsUpdate($data)
    {
        Log::info('👥 Group Participants Updated', [
            'instance' => $data['instance'] ?? null,
            'update' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    // ==================== LABEL EVENTS ====================

    /**
     * Handle labels edit event
     */
    protected function handleLabelsEdit($data)
    {
        Log::info('🏷️ Labels Edited', [
            'instance' => $data['instance'] ?? null,
            'labels' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle labels association event
     */
    protected function handleLabelsAssociation($data)
    {
        Log::info('🏷️ Labels Associated', [
            'instance' => $data['instance'] ?? null,
            'association' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    // ==================== CALL EVENTS ====================

    /**
     * Handle call event
     */
    protected function handleCall($data)
    {
        Log::info('📞 Call Received', [
            'instance' => $data['instance'] ?? null,
            'call' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);

        // TODO: Handle incoming calls (e.g., auto-reject, notify user, etc.)
    }

    // ==================== TYPEBOT EVENTS ====================

    /**
     * Handle Typebot start event
     */
    protected function handleTypebotStart($data)
    {
        Log::info('🤖 Typebot Started', [
            'instance' => $data['instance'] ?? null,
            'typebot' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Handle Typebot status change event
     */
    protected function handleTypebotChangeStatus($data)
    {
        Log::info('🤖 Typebot Status Changed', [
            'instance' => $data['instance'] ?? null,
            'status' => $data['data'] ?? null,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
