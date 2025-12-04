<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\WhatsAppInstance;
use Exception;

class EvolutionService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.evolution.api_key', env('EVOLUTION_API_KEY'));
        $this->baseUrl = rtrim(config('services.evolution.base_url', env('EVOLUTION_BASE_URL')), '/');
    }

    /**
     * Make HTTP request to Evolution API
     */
    protected function makeRequest($method, $endpoint, $data = [], $instanceKey = null)
    {
        try {
            $url = $this->baseUrl . $endpoint;

            $headers = [
                'Content-Type' => 'application/json',
                'apikey' => $instanceKey ?? $this->apiKey,
            ];

            $response = Http::withHeaders($headers)
                ->$method($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Evolution API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Unknown error',
                'status' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('Evolution API Exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ==================== Instance Management ====================

    /**
     * Create a new WhatsApp instance
     */
    public function createInstance($instanceName, $options = [])
    {
        $data = array_merge([
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ], $options);

        return $this->makeRequest('post', '/instance/create', $data);
    }

    /**
     * Fetch all instances or specific instance
     */
    public function fetchInstances($instanceName = null, $instanceId = null)
    {
        $query = [];
        if ($instanceName) $query['instanceName'] = $instanceName;
        if ($instanceId) $query['instanceId'] = $instanceId;

        $endpoint = '/instance/fetchInstances' . (count($query) ? '?' . http_build_query($query) : '');
        return $this->makeRequest('get', $endpoint);
    }

    /**
     * Connect instance and get QR code
     */
    public function connectInstance($instanceName, $number = null)
    {
        $query = $number ? '?number=' . $number : '';
        return $this->makeRequest('get', "/instance/connect/{$instanceName}{$query}");
    }

    /**
     * Restart instance
     */
    public function restartInstance($instanceName)
    {
        return $this->makeRequest('post', "/instance/restart/{$instanceName}");
    }

    /**
     * Get connection status
     */
    public function getConnectionStatus($instanceName)
    {
        return $this->makeRequest('get', "/instance/connectionState/{$instanceName}");
    }

    /**
     * Logout instance
     */
    public function logoutInstance($instanceName)
    {
        return $this->makeRequest('delete', "/instance/logout/{$instanceName}");
    }

    /**
     * Delete instance
     */
    public function deleteInstance($instanceName)
    {
        return $this->makeRequest('delete', "/instance/delete/{$instanceName}");
    }

    /**
     * Set instance presence (available/unavailable)
     */
    public function setPresence($instanceName, $presence = 'available')
    {
        return $this->makeRequest('post', "/instance/setPresence/{$instanceName}", [
            'presence' => $presence
        ]);
    }

    // ==================== Settings Management ====================

    /**
     * Set instance settings
     */
    public function setSettings($instanceName, $settings = [])
    {
        $defaultSettings = [
            'rejectCall' => false,
            'msgCall' => '',
            'groupsIgnore' => false,
            'alwaysOnline' => false,
            'readMessages' => false,
            'syncFullHistory' => false,
            'readStatus' => false,
        ];

        $data = array_merge($defaultSettings, $settings);
        return $this->makeRequest('post', "/settings/set/{$instanceName}", $data);
    }

    /**
     * Find instance settings
     */
    public function findSettings($instanceName)
    {
        return $this->makeRequest('get', "/settings/find/{$instanceName}");
    }

    // ==================== Send Messages ====================

    /**
     * Send text message
     */
    public function sendText($instanceName, $number, $text, $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'text' => $text,
        ], $options);

        return $this->makeRequest('post', "/message/sendText/{$instanceName}", $data);
    }

    /**
     * Send media (image, video, document)
     */
    public function sendMedia($instanceName, $number, $mediaUrl, $mediaType = 'image', $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'mediatype' => $mediaType,
            'media' => $mediaUrl,
        ], $options);

        return $this->makeRequest('post', "/message/sendMedia/{$instanceName}", $data);
    }

    /**
     * Send audio message
     */
    public function sendAudio($instanceName, $number, $audioUrl, $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'audio' => $audioUrl,
        ], $options);

        return $this->makeRequest('post', "/message/sendWhatsAppAudio/{$instanceName}", $data);
    }

    /**
     * Send sticker
     */
    public function sendSticker($instanceName, $number, $stickerUrl, $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'sticker' => $stickerUrl,
        ], $options);

        return $this->makeRequest('post', "/message/sendSticker/{$instanceName}", $data);
    }

    /**
     * Send location
     */
    public function sendLocation($instanceName, $number, $latitude, $longitude, $name = '', $address = '', $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'name' => $name,
            'address' => $address,
        ], $options);

        return $this->makeRequest('post', "/message/sendLocation/{$instanceName}", $data);
    }

    /**
     * Send contact
     */
    public function sendContact($instanceName, $number, $contacts = [], $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'contact' => $contacts,
        ], $options);

        return $this->makeRequest('post', "/message/sendContact/{$instanceName}", $data);
    }

    /**
     * Send reaction to message
     */
    public function sendReaction($instanceName, $key, $reaction)
    {
        $data = [
            'key' => $key,
            'reaction' => $reaction,
        ];

        return $this->makeRequest('post', "/message/sendReaction/{$instanceName}", $data);
    }

    /**
     * Send poll
     */
    public function sendPoll($instanceName, $number, $name, $values, $selectableCount = 1, $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'name' => $name,
            'selectableCount' => $selectableCount,
            'values' => $values,
        ], $options);

        return $this->makeRequest('post', "/message/sendPoll/{$instanceName}", $data);
    }

    /**
     * Send list message
     */
    public function sendList($instanceName, $number, $title, $description, $buttonText, $sections, $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'title' => $title,
            'description' => $description,
            'buttonText' => $buttonText,
            'sections' => $sections,
        ], $options);

        return $this->makeRequest('post', "/message/sendList/{$instanceName}", $data);
    }

    /**
     * Send buttons message
     */
    // "type": "reply",
    // "displayText": "Resposta",
    // "id": "123"
    public function sendButtons($instanceName, $number, $title, $description, $buttons, $options = [])
    {
        $data = array_merge([
            'number' => $number,
            'title' => $title,
            'description' => $description,
            'footer' => 'Footer Button',
            'buttons' => $buttons,
        ], $options);

        return $this->makeRequest('post', "/message/sendButtons/{$instanceName}", $data);
    }

    /**
     * Send status/stories
     */
    public function sendStatus($instanceName, $type, $content, $options = [])
    {
        $data = array_merge([
            'type' => $type,
            'content' => $content,
        ], $options);

        return $this->makeRequest('post', "/message/sendStatus/{$instanceName}", $data);
    }

    // ==================== Chat Management ====================

    /**
     * Check if numbers are WhatsApp numbers
     */
    public function checkWhatsAppNumbers($instanceName, $numbers = [])
    {
        return $this->makeRequest('post', "/chat/whatsappNumbers/{$instanceName}", [
            'numbers' => $numbers
        ]);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead($instanceName, $messages = [])
    {
        return $this->makeRequest('post', "/chat/markMessageAsRead/{$instanceName}", [
            'readMessages' => $messages
        ]);
    }

    /**
     * Archive/Unarchive chat
     */
    public function archiveChat($instanceName, $chat, $lastMessage, $archive = true)
    {
        return $this->makeRequest('post', "/chat/archiveChat/{$instanceName}", [
            'chat' => $chat,
            'lastMessage' => $lastMessage,
            'archive' => $archive,
        ]);
    }

    /**
     * Mark chat as unread
     */
    public function markChatUnread($instanceName, $chat, $lastMessage)
    {
        return $this->makeRequest('post', "/chat/markChatUnread/{$instanceName}", [
            'chat' => $chat,
            'lastMessage' => $lastMessage,
        ]);
    }

    /**
     * Delete message for everyone
     */
    public function deleteMessage($instanceName, $messageId, $remoteJid, $fromMe = true, $participant = null)
    {
        $data = [
            'id' => $messageId,
            'remoteJid' => $remoteJid,
            'fromMe' => $fromMe,
        ];

        if ($participant) {
            $data['participant'] = $participant;
        }

        return $this->makeRequest('delete', "/chat/deleteMessageForEveryone/{$instanceName}", $data);
    }

    /**
     * Fetch profile picture URL
     */
    public function fetchProfilePicture($instanceName, $number)
    {
        return $this->makeRequest('post', "/chat/fetchProfilePictureUrl/{$instanceName}", [
            'number' => $number
        ]);
    }

    /**
     * Get base64 from media message
     */
    public function getBase64FromMedia($instanceName, $messageKey, $convertToMp4 = false)
    {
        return $this->makeRequest('post', "/chat/getBase64FromMediaMessage/{$instanceName}", [
            'message' => ['key' => $messageKey],
            'convertToMp4' => $convertToMp4,
        ]);
    }

    /**
     * Update message
     */
    public function updateMessage($instanceName, $number, $key, $text)
    {
        return $this->makeRequest('post', "/chat/updateMessage/{$instanceName}", [
            'number' => $number,
            'key' => $key,
            'text' => $text,
        ]);
    }

    /**
     * Send presence (typing, recording, etc.)
     */
    public function sendChatPresence($instanceName, $number, $presence = 'composing', $delay = 1200)
    {
        return $this->makeRequest('post', "/chat/sendPresence/{$instanceName}", [
            'number' => $number,
            'presence' => $presence,
            'delay' => $delay,
        ]);
    }

    /**
     * Block/Unblock contact
     */
    public function updateBlockStatus($instanceName, $number, $status = 'block')
    {
        return $this->makeRequest('post', "/message/updateBlockStatus/{$instanceName}", [
            'number' => $number,
            'status' => $status,
        ]);
    }

    /**
     * Find contacts
     */
    public function findContacts($instanceName, $where = [])
    {
        return $this->makeRequest('post', "/chat/findContacts/{$instanceName}", [
            'where' => $where
        ]);
    }

    /**
     * Find messages
     */
    public function findMessages($instanceName, $where = [])
    {
        return $this->makeRequest('post', "/chat/findMessages/{$instanceName}", [
            'where' => $where
        ]);
    }

    /**
     * Find chats
     */
    public function findChats($instanceName, $where = [])
    {
        return $this->makeRequest('post', "/chat/findChats/{$instanceName}", [
            'where' => $where
        ]);
    }

    // ==================== Group Management ====================

    /**
     * Create group
     */
    public function createGroup($instanceName, $subject, $participants = [])
    {
        return $this->makeRequest('post', "/group/create/{$instanceName}", [
            'subject' => $subject,
            'participants' => $participants,
        ]);
    }

    /**
     * Update group name
     */
    public function updateGroupName($instanceName, $groupJid, $subject)
    {
        return $this->makeRequest('post', "/group/updateGroupSubject/{$instanceName}", [
            'groupJid' => $groupJid,
            'subject' => $subject,
        ]);
    }

    /**
     * Update group description
     */
    public function updateGroupDescription($instanceName, $groupJid, $description)
    {
        return $this->makeRequest('post', "/group/updateGroupDescription/{$instanceName}", [
            'groupJid' => $groupJid,
            'description' => $description,
        ]);
    }

    /**
     * Update group picture
     */
    public function updateGroupPicture($instanceName, $groupJid, $image)
    {
        return $this->makeRequest('post', "/group/updateGroupPicture/{$instanceName}", [
            'groupJid' => $groupJid,
            'image' => $image,
        ]);
    }

    /**
     * Add participants to group
     */
    public function addGroupParticipants($instanceName, $groupJid, $participants = [])
    {
        return $this->makeRequest('post', "/group/updateParticipant/{$instanceName}", [
            'groupJid' => $groupJid,
            'action' => 'add',
            'participants' => $participants,
        ]);
    }

    /**
     * Remove participants from group
     */
    public function removeGroupParticipants($instanceName, $groupJid, $participants = [])
    {
        return $this->makeRequest('post', "/group/updateParticipant/{$instanceName}", [
            'groupJid' => $groupJid,
            'action' => 'remove',
            'participants' => $participants,
        ]);
    }

    /**
     * Promote participants to admin
     */
    public function promoteGroupParticipants($instanceName, $groupJid, $participants = [])
    {
        return $this->makeRequest('post', "/group/updateParticipant/{$instanceName}", [
            'groupJid' => $groupJid,
            'action' => 'promote',
            'participants' => $participants,
        ]);
    }

    /**
     * Demote participants from admin
     */
    public function demoteGroupParticipants($instanceName, $groupJid, $participants = [])
    {
        return $this->makeRequest('post', "/group/updateParticipant/{$instanceName}", [
            'groupJid' => $groupJid,
            'action' => 'demote',
            'participants' => $participants,
        ]);
    }

    /**
     * Update group settings
     */
    public function updateGroupSettings($instanceName, $groupJid, $action)
    {
        return $this->makeRequest('post', "/group/updateSetting/{$instanceName}", [
            'groupJid' => $groupJid,
            'action' => $action,
        ]);
    }

    /**
     * Leave group
     */
    public function leaveGroup($instanceName, $groupJid)
    {
        return $this->makeRequest('post', "/group/leaveGroup/{$instanceName}", [
            'groupJid' => $groupJid,
        ]);
    }

    /**
     * Fetch all groups
     */
    public function fetchAllGroups($instanceName, $getParticipants = true)
    {
        $query = $getParticipants ? '?getParticipants=true' : '';
        return $this->makeRequest('get', "/group/fetchAllGroups/{$instanceName}{$query}");
    }

    /**
     * Find group by JID
     */
    public function findGroup($instanceName, $groupJid)
    {
        return $this->makeRequest('post', "/group/findGroupInfos/{$instanceName}", [
            'groupJid' => $groupJid,
        ]);
    }

    /**
     * Fetch group participants
     */
    public function fetchGroupParticipants($instanceName, $groupJid)
    {
        return $this->makeRequest('post', "/group/participants/{$instanceName}", [
            'groupJid' => $groupJid,
        ]);
    }

    /**
     * Get group invite code
     */
    public function getGroupInviteCode($instanceName, $groupJid)
    {
        return $this->makeRequest('post', "/group/inviteCode/{$instanceName}", [
            'groupJid' => $groupJid,
        ]);
    }

    /**
     * Revoke group invite code
     */
    public function revokeGroupInviteCode($instanceName, $groupJid)
    {
        return $this->makeRequest('post', "/group/revokeInviteCode/{$instanceName}", [
            'groupJid' => $groupJid,
        ]);
    }

    /**
     * Accept group invite
     */
    public function acceptGroupInvite($instanceName, $inviteCode)
    {
        return $this->makeRequest('post', "/group/acceptInviteCode/{$instanceName}", [
            'inviteCode' => $inviteCode,
        ]);
    }

    // ==================== Webhook Management ====================

    /**
     * Set webhook
     */
    public function setWebhook($instanceName, $webhookUrl, $events = [], $options = [])
    {
        $data = array_merge([
            'url' => $webhookUrl,
            'events' => $events,
        ], $options);

        return $this->makeRequest('post', "/webhook/set/{$instanceName}", [
            'webhook' => $data
        ]);
    }

    /**
     * Find webhook
     */
    public function findWebhook($instanceName)
    {
        return $this->makeRequest('get', "/webhook/find/{$instanceName}");
    }

    // ==================== Proxy Management ====================

    /**
     * Set proxy
     */
    public function setProxy($instanceName, $host, $port, $protocol = 'http', $username = null, $password = null)
    {
        $data = [
            'enabled' => true,
            'host' => $host,
            'port' => $port,
            'protocol' => $protocol,
        ];

        if ($username) $data['username'] = $username;
        if ($password) $data['password'] = $password;

        return $this->makeRequest('post', "/proxy/set/{$instanceName}", $data);
    }

    /**
     * Find proxy
     */
    public function findProxy($instanceName)
    {
        return $this->makeRequest('get', "/proxy/find/{$instanceName}");
    }

    // ==================== Helper Methods ====================

    /**
     * Format phone number to WhatsApp format
     */
    public function formatPhoneNumber($number)
    {
        // Remove all non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);

        // Add @s.whatsapp.net if not already present
        if (!str_contains($number, '@')) {
            $number .= '@s.whatsapp.net';
        }

        return $number;
    }

    /**
     * Format group JID
     */
    public function formatGroupJid($groupId)
    {
        // Add @g.us if not already present
        if (!str_contains($groupId, '@')) {
            $groupId .= '@g.us';
        }

        return $groupId;
    }

    /**
     * Send quick reply message (helper method)
     */
    public function sendQuickReply($instanceName, $number, $text, $buttons = [])
    {
        $formattedButtons = [];
        foreach ($buttons as $button) {
            $formattedButtons[] = [
                'type' => 'reply',
                'displayText' => $button['text'] ?? $button,
                'id' => $button['id'] ?? uniqid(),
            ];
        }

        return $this->sendButtons($instanceName, $number, '', $text, $formattedButtons);
    }

    /**
     * Send template message with image (helper method)
     */
    public function sendTemplateWithImage($instanceName, $number, $imageUrl, $caption, $buttons = [])
    {
        // First send the image
        $mediaResult = $this->sendMedia($instanceName, $number, $imageUrl, 'image', [
            'caption' => $caption
        ]);

        // Then send buttons if provided
        if (!empty($buttons) && $mediaResult['success']) {
            return $this->sendQuickReply($instanceName, $number, '', $buttons);
        }

        return $mediaResult;
    }
}
