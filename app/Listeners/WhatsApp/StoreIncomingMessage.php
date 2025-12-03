<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageReceived;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppChat;
use Illuminate\Support\Facades\Log;

class StoreIncomingMessage
{
    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        try {
            $instance = $event->instance;
            $messageData = $event->messageData;

            // استخراج بيانات الرسالة
            $messageId = $messageData['key']['id'] ?? null;
            $remoteJid = $messageData['key']['remoteJid'] ?? null;
            $fromMe = $messageData['key']['fromMe'] ?? false;

            if (!$messageId || !$remoteJid) {
                return;
            }

            // استخراج نص الرسالة ونوعها
            $messageContent = $this->extractMessageContent($messageData);
            $messageType = $this->extractMessageType($messageData);

            // حفظ الرسالة
            $message = WhatsAppMessage::updateOrCreate(
                [
                    'instance_id' => $instance->id,
                    'message_id' => $messageId,
                ],
                [
                    'remote_jid' => $remoteJid,
                    'from_me' => $fromMe,
                    'message_type' => $messageType,
                    'message_content' => $messageContent,
                    'message_data' => $messageData,
                    'status' => 'received',
                ]
            );

            // تحديث أو إنشاء جهة الاتصال
            $this->updateOrCreateContact($instance, $messageData);

            // تحديث أو إنشاء المحادثة
            $this->updateOrCreateChat($instance, $remoteJid, $messageContent);

            Log::info('Incoming message stored', [
                'instance_id' => $instance->id,
                'message_id' => $messageId,
                'from' => $remoteJid,
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing incoming message', [
                'error' => $e->getMessage(),
                'instance_id' => $event->instance->id ?? null,
            ]);
        }
    }

    /**
     * استخراج محتوى الرسالة
     */
    protected function extractMessageContent(array $messageData): ?string
    {
        $msg = $messageData['message'] ?? [];

        if (isset($msg['conversation'])) {
            return $msg['conversation'];
        }

        if (isset($msg['extendedTextMessage']['text'])) {
            return $msg['extendedTextMessage']['text'];
        }

        if (isset($msg['imageMessage']['caption'])) {
            return $msg['imageMessage']['caption'];
        }

        if (isset($msg['videoMessage']['caption'])) {
            return $msg['videoMessage']['caption'];
        }

        return null;
    }

    /**
     * استخراج نوع الرسالة
     */
    protected function extractMessageType(array $messageData): string
    {
        $msg = $messageData['message'] ?? [];

        if (isset($msg['conversation']) || isset($msg['extendedTextMessage'])) {
            return 'text';
        }

        if (isset($msg['imageMessage'])) {
            return 'image';
        }

        if (isset($msg['videoMessage'])) {
            return 'video';
        }

        if (isset($msg['audioMessage'])) {
            return 'audio';
        }

        if (isset($msg['documentMessage'])) {
            return 'document';
        }

        if (isset($msg['stickerMessage'])) {
            return 'sticker';
        }

        if (isset($msg['locationMessage'])) {
            return 'location';
        }

        if (isset($msg['contactMessage'])) {
            return 'contact';
        }

        return 'text';
    }

    /**
     * تحديث أو إنشاء جهة الاتصال
     */
    protected function updateOrCreateContact($instance, array $messageData): void
    {
        $remoteJid = $messageData['key']['remoteJid'] ?? null;
        $pushName = $messageData['pushName'] ?? null;

        if (!$remoteJid) {
            return;
        }

        WhatsAppContact::updateOrCreate(
            [
                'instance_id' => $instance->id,
                'jid' => $remoteJid,
            ],
            [
                'push_name' => $pushName,
                'last_message_at' => now(),
            ]
        );
    }

    /**
     * تحديث أو إنشاء المحادثة
     */
    protected function updateOrCreateChat($instance, string $remoteJid, ?string $lastMessage): void
    {
        $chat = WhatsAppChat::firstOrCreate(
            [
                'instance_id' => $instance->id,
                'jid' => $remoteJid,
            ],
            [
                'is_group' => str_contains($remoteJid, '@g.us'),
            ]
        );

        $chat->update([
            'last_message' => $lastMessage,
            'last_message_at' => now(),
            'unread_count' => $chat->unread_count + 1,
        ]);
    }
}
