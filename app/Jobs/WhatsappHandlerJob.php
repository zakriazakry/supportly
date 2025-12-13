<?php

namespace App\Jobs;

use App\Helpers\WebhookHelper;
use App\Http\Controllers\Whatsapp\AutoReplyController;
use App\Models\WhatsAppInstance;
use App\Services\AiManagerService;
use App\Services\EvolutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WhatsappHandlerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $data;
    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {

            $event = $this->data['event'] ?? null;

            if (!$event) {
                return;
            }
            $instance = WhatsAppInstance::where('instance_name', $this->data['instance'])->first();
            if (!$instance) {
                return;
            }
            $user = $instance->user;
            if (!$user) {
                return;
            }
            $feature = 'whatsapp';
            if (!$user->hasFeature($feature)) {
                Log::info('Feature not found', ['feature' => $feature]);
                return;
            }


            $method = $this->getHandlerMethod($event);
            if (method_exists($this, $method)) {
                $this->$method($this->data);
            }
            if ($user->hasFeature('whatsapp_webhook')) {
                foreach ($instance->webhooks as $webhook) {
                    WebhookHelper::sendWebhook($webhook, $this->data);
                }
            }
        } catch (\Exception $e) {

            Log::info($e->getMessage());
        }
    }

    protected function getHandlerMethod($event)
    {
        $parts = explode('.', strtolower($event));
        $method = 'handle';

        foreach ($parts as $part) {
            $method .= ucfirst($part);
        }

        return $method;
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

// =======================================================================================================
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

            $phone = $this->extractPhone($remoteJid, $remoteJidAlt);
            if (!$phone) {
                return;
            }

            $messageInfo = $this->extractMessageInfo($messageContent);

            if ($fromMe == true) {
                $instance = WhatsAppInstance::where('instance_name', $instanceName)->first();
                if ($instance && $messageInfo['type'] === 'text') {
                    $instance->messages()->create([
                        'instance_id' => $instance->id,
                        'message_id' => $messageId ?? 'owner_' . time() . '_' . rand(1000, 9999),
                        'remote_jid' => $phone,
                        'from_me' => true,
                        'message_type' => $messageInfo['type'],
                        'message_content' => $messageInfo['content'],
                        'sent_at' => now(),
                        'status' => 'delivered',
                    ]);
                }
            }

            $instance = WhatsAppInstance::where('instance_name', $instanceName)->first();
            if (!$instance) {
                return responseFormat('Instance not found', 404);
            }
            $user = $instance->user;

            switch ($messageInfo['type']) {
                case 'text':
                    $autoReplyController = new AutoReplyController(new EvolutionService(), new AiManagerService());
                    $autoReplyController->whenReceiveTextMessage([
                        'from' => $sender,
                        'form_number' => $phone,
                        'to' => $receiver,
                        'message' => $messageInfo['content'],
                        'pushName' => $pushName,
                        'messageTimestamp' => $messageTimestamp,
                        'messageId' => $messageId,
                        'fromMe' => $fromMe,
                        'remoteJid' => $remoteJid,
                        'remote_jid' => $remoteJid,
                        'key' => $key,
                        'messageInfo' => $messageInfo,
                        'instanceName' => $instanceName,
                        'whatsapp_auto_reply' => $user->hasFeature('whatsapp_auto_reply'),
                        'whatsapp_ai_reply' => $user->hasFeature('whatsapp_ai_reply'),
                        'whatsapp_openai_support' => $user->hasFeature('whatsapp_openai_support'),
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

    // ------------------Helper Functions------------------
    protected  function extractPhone($remoteJid, $remoteJidAlt)
    {
        $jidList = [$remoteJid, $remoteJidAlt];
        foreach ($jidList as $jid) {
            if (!$jid) continue;
            if (preg_match('/^(\d+)@s\.whatsapp\.net$/', $jid, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
