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

    // ==================== INSTANCE MANAGEMENT ====================

    /**
     * Create a new WhatsApp instance
     */
    public function createInstance($data)
    {
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

        $endpoint = '/instance/fetchInstances' . (count($query) > 0 ? '?' . http_build_query($query) : '');
        return $this->makeRequest('get', $endpoint);
    }

    /**
     * Connect instance and get QR code
     */
    public function connectInstance($instanceName, $number = null)
    {
        $endpoint = "/instance/connect/{$instanceName}";
        if ($number) {
            $endpoint .= "?number={$number}";
        }
        return $this->makeRequest('get', $endpoint);
    }

    /**
     * Restart instance
     */
    public function restartInstance($instanceName)
    {
        return $this->makeRequest('post', "/instance/restart/{$instanceName}");
    }

    /**
     * Set instance presence
     */
    public function setPresence($instanceName, $presence = 'available')
    {
        return $this->makeRequest('post', "/instance/setPresence/{$instanceName}", [
            'presence' => $presence // available, unavailable
        ]);
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

    // ==================== PROXY MANAGEMENT ====================

    /**
     * Set proxy for instance
     */
    public function setProxy($instanceName, $proxyData)
    {
        return $this->makeRequest('post', "/proxy/set/{$instanceName}", $proxyData);
    }

    /**
     * Find proxy settings
     */
    public function findProxy($instanceName)
    {
        return $this->makeRequest('get', "/proxy/find/{$instanceName}");
    }

    // ==================== SETTINGS MANAGEMENT ====================

    /**
     * Set instance settings
     */
    public function setSettings($instanceName, $settings)
    {
        return $this->makeRequest('post', "/settings/set/{$instanceName}", $settings);
    }

    /**
     * Find instance settings
     */
    public function findSettings($instanceName)
    {
        return $this->makeRequest('get', "/settings/find/{$instanceName}");
    }

    // ==================== SEND MESSAGES ====================

    /**
     * Send text message
     */
    public function sendText($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendText/{$instanceName}", $data);
    }

    /**
     * Send media (image, video, document)
     */
    public function sendMedia($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendMedia/{$instanceName}", $data);
    }

    /**
     * Send PTV (Picture-in-Picture Video)
     */
    public function sendPtv($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendPtv/{$instanceName}", $data);
    }

    /**
     * Send WhatsApp Audio (narrated audio)
     */
    public function sendWhatsAppAudio($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendWhatsAppAudio/{$instanceName}", $data);
    }

    /**
     * Send status/stories
     */
    public function sendStatus($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendStatus/{$instanceName}", $data);
    }

    /**
     * Send sticker
     */
    public function sendSticker($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendSticker/{$instanceName}", $data);
    }

    /**
     * Send location
     */
    public function sendLocation($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendLocation/{$instanceName}", $data);
    }

    /**
     * Send contact
     */
    public function sendContact($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendContact/{$instanceName}", $data);
    }

    /**
     * Send reaction
     */
    public function sendReaction($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendReaction/{$instanceName}", $data);
    }

    /**
     * Send poll
     */
    public function sendPoll($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendPoll/{$instanceName}", $data);
    }

    /**
     * Send list message
     */
    public function sendList($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendList/{$instanceName}", $data);
    }

    /**
     * Send buttons
     */
    public function sendButtons($instanceName, $data)
    {
        return $this->makeRequest('post', "/message/sendButtons/{$instanceName}", $data);
    }

    // ==================== CALL ====================

    /**
     * Make fake call
     */
    public function fakeCall($instanceName, $data)
    {
        return $this->makeRequest('post', "/call/offer/{$instanceName}", $data);
    }

    // ==================== CHAT MANAGEMENT ====================

    /**
     * Check if numbers are WhatsApp numbers
     */
    public function checkWhatsAppNumbers($instanceName, $numbers)
    {
        return $this->makeRequest('post', "/chat/whatsappNumbers/{$instanceName}", [
            'numbers' => $numbers
        ]);
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead($instanceName, $readMessages)
    {
        return $this->makeRequest('post', "/chat/markMessageAsRead/{$instanceName}", [
            'readMessages' => $readMessages
        ]);
    }

    /**
     * Archive/Unarchive chat
     */
    public function archiveChat($instanceName, $data)
    {
        return $this->makeRequest('post', "/chat/archiveChat/{$instanceName}", $data);
    }

    /**
     * Mark chat as unread
     */
    public function markChatUnread($instanceName, $data)
    {
        return $this->makeRequest('post', "/chat/markChatUnread/{$instanceName}", $data);
    }

    /**
     * Delete message for everyone
     */
    public function deleteMessage($instanceName, $data)
    {
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
    public function getBase64FromMedia($instanceName, $message, $convertToMp4 = false)
    {
        return $this->makeRequest('post', "/chat/getBase64FromMediaMessage/{$instanceName}", [
            'message' => $message,
            'convertToMp4' => $convertToMp4
        ]);
    }

    /**
     * Update message
     */
    public function updateMessage($instanceName, $data)
    {
        return $this->makeRequest('post', "/chat/updateMessage/{$instanceName}", $data);
    }

    /**
     * Send presence (typing, recording, etc.)
     */
    public function sendPresence($instanceName, $data)
    {
        return $this->makeRequest('post', "/chat/sendPresence/{$instanceName}", $data);
    }

    /**
     * Update block status
     */
    public function updateBlockStatus($instanceName, $number, $status)
    {
        return $this->makeRequest('post', "/message/updateBlockStatus/{$instanceName}", [
            'number' => $number,
            'status' => $status // block, unblock
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
    public function findMessages($instanceName, $where = [], $page = 1, $offset = 10)
    {
        return $this->makeRequest('post', "/chat/findMessages/{$instanceName}", [
            'where' => $where,
            'page' => $page,
            'offset' => $offset
        ]);
    }

    /**
     * Find status messages
     */
    public function findStatusMessages($instanceName, $where = [], $page = 1, $offset = 10)
    {
        return $this->makeRequest('post', "/chat/findStatusMessage/{$instanceName}", [
            'where' => $where,
            'page' => $page,
            'offset' => $offset
        ]);
    }

    /**
     * Find chats
     */
    public function findChats($instanceName)
    {
        return $this->makeRequest('post', "/chat/findChats/{$instanceName}");
    }

    // ==================== LABEL MANAGEMENT ====================

    /**
     * Find labels
     */
    public function findLabels($instanceName)
    {
        return $this->makeRequest('get', "/label/findLabels/{$instanceName}");
    }

    /**
     * Handle label (add/remove)
     */
    public function handleLabel($instanceName, $number, $labelId, $action)
    {
        return $this->makeRequest('post', "/label/handleLabel/{$instanceName}", [
            'number' => $number,
            'labelId' => $labelId,
            'action' => $action // add, remove
        ]);
    }

    // ==================== PROFILE SETTINGS ====================

    /**
     * Fetch business profile
     */
    public function fetchBusinessProfile($instanceName, $number)
    {
        return $this->makeRequest('post', "/chat/fetchBusinessProfile/{$instanceName}", [
            'number' => $number
        ]);
    }

    /**
     * Fetch profile
     */
    public function fetchProfile($instanceName, $number)
    {
        return $this->makeRequest('post', "/chat/fetchProfile/{$instanceName}", [
            'number' => $number
        ]);
    }

    /**
     * Update profile name
     */
    public function updateProfileName($instanceName, $name)
    {
        return $this->makeRequest('post', "/chat/updateProfileName/{$instanceName}", [
            'name' => $name
        ]);
    }

    /**
     * Update profile status
     */
    public function updateProfileStatus($instanceName, $status)
    {
        return $this->makeRequest('post', "/chat/updateProfileStatus/{$instanceName}", [
            'status' => $status
        ]);
    }

    /**
     * Update profile picture
     */
    public function updateProfilePicture($instanceName, $picture)
    {
        return $this->makeRequest('post', "/chat/updateProfilePicture/{$instanceName}", [
            'picture' => $picture
        ]);
    }

    /**
     * Remove profile picture
     */
    public function removeProfilePicture($instanceName)
    {
        return $this->makeRequest('delete', "/chat/removeProfilePicture/{$instanceName}");
    }

    /**
     * Fetch privacy settings
     */
    public function fetchPrivacySettings($instanceName)
    {
        return $this->makeRequest('get', "/chat/fetchPrivacySettings/{$instanceName}");
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacySettings($instanceName, $settings)
    {
        return $this->makeRequest('post', "/chat/updatePrivacySettings/{$instanceName}", $settings);
    }

    // ==================== GROUP MANAGEMENT ====================

    /**
     * Create group
     */
    public function createGroup($instanceName, $subject, $participants, $description = null)
    {
        $data = [
            'subject' => $subject,
            'participants' => $participants
        ];

        if ($description) {
            $data['description'] = $description;
        }

        return $this->makeRequest('post', "/group/create/{$instanceName}", $data);
    }

    /**
     * Update group picture
     */
    public function updateGroupPicture($instanceName, $groupJid, $image)
    {
        return $this->makeRequest('post', "/group/updateGroupPicture/{$instanceName}?groupJid={$groupJid}", [
            'image' => $image
        ]);
    }

    /**
     * Update group subject
     */
    public function updateGroupSubject($instanceName, $groupJid, $subject)
    {
        return $this->makeRequest('post', "/group/updateGroupSubject/{$instanceName}?groupJid={$groupJid}", [
            'subject' => $subject
        ]);
    }

    /**
     * Update group description
     */
    public function updateGroupDescription($instanceName, $groupJid, $description)
    {
        return $this->makeRequest('post', "/group/updateGroupDescription/{$instanceName}?groupJid={$groupJid}", [
            'description' => $description
        ]);
    }

    /**
     * Find group
     */
    public function findGroup($instanceName, $groupJid)
    {
        return $this->makeRequest('get', "/group/findGroupInfo/{$instanceName}?groupJid={$groupJid}");
    }

    /**
     * Fetch all groups
     */
    public function fetchAllGroups($instanceName, $getParticipants = false)
    {
        return $this->makeRequest('get', "/group/fetchAllGroups/{$instanceName}?getParticipants=" . ($getParticipants ? 'true' : 'false'));
    }

    /**
     * Find participants
     */
    public function findParticipants($instanceName, $groupJid)
    {
        return $this->makeRequest('get', "/group/findParticipants/{$instanceName}?groupJid={$groupJid}");
    }

    /**
     * Update participant (add/remove/promote/demote)
     */
    public function updateParticipant($instanceName, $groupJid, $action, $participants)
    {
        return $this->makeRequest('post', "/group/updateParticipant/{$instanceName}?groupJid={$groupJid}", [
            'action' => $action, // add, remove, promote, demote
            'participants' => $participants
        ]);
    }

    /**
     * Update group settings
     */
    public function updateGroupSettings($instanceName, $groupJid, $action)
    {
        return $this->makeRequest('post', "/group/updateSetting/{$instanceName}?groupJid={$groupJid}", [
            'action' => $action // announcement, not_announcement, locked, unlocked
        ]);
    }

    /**
     * Toggle ephemeral (disappearing messages)
     */
    public function toggleEphemeral($instanceName, $groupJid, $expiration)
    {
        return $this->makeRequest('post', "/group/toggleEphemeral/{$instanceName}?groupJid={$groupJid}", [
            'expiration' => $expiration // 0 (off), 86400 (1 day), 604800 (7 days), 7776000 (90 days)
        ]);
    }

    /**
     * Leave group
     */
    public function leaveGroup($instanceName, $groupJid)
    {
        return $this->makeRequest('delete', "/group/leaveGroup/{$instanceName}?groupJid={$groupJid}");
    }

    /**
     * Join group with invite code
     */
    public function joinGroupWithCode($instanceName, $inviteCode)
    {
        return $this->makeRequest('post', "/group/joinGroupWithCode/{$instanceName}", [
            'inviteCode' => $inviteCode
        ]);
    }

    /**
     * Get invite code
     */
    public function getInviteCode($instanceName, $groupJid)
    {
        return $this->makeRequest('get', "/group/inviteCode/{$instanceName}?groupJid={$groupJid}");
    }

    /**
     * Revoke invite code
     */
    public function revokeInviteCode($instanceName, $groupJid)
    {
        return $this->makeRequest('post', "/group/revokeInviteCode/{$instanceName}?groupJid={$groupJid}");
    }

    /**
     * Send invite URL
     */
    public function sendInviteUrl($instanceName, $groupJid, $numbers, $description = null)
    {
        $data = [
            'groupJid' => $groupJid,
            'numbers' => $numbers
        ];

        if ($description) {
            $data['description'] = $description;
        }

        return $this->makeRequest('post', "/group/sendInviteUrl/{$instanceName}", $data);
    }

    // ==================== WEBHOOK MANAGEMENT ====================

    /**
     * Set webhook
     */
    public function setWebhook($instanceName, $webhookData)
    {
        return $this->makeRequest('post', "/webhook/set/{$instanceName}", $webhookData);
    }

    /**
     * Find webhook
     */
    public function findWebhook($instanceName)
    {
        return $this->makeRequest('get', "/webhook/find/{$instanceName}");
    }

    // ==================== CHATWOOT INTEGRATION ====================

    /**
     * Set Chatwoot
     */
    public function setChatwoot($instanceName, $chatwootData)
    {
        return $this->makeRequest('post', "/chatwoot/set/{$instanceName}", $chatwootData);
    }

    /**
     * Find Chatwoot
     */
    public function findChatwoot($instanceName)
    {
        return $this->makeRequest('get', "/chatwoot/find/{$instanceName}");
    }

    // ==================== RABBITMQ INTEGRATION ====================

    /**
     * Set RabbitMQ
     */
    public function setRabbitmq($instanceName, $rabbitmqData)
    {
        return $this->makeRequest('post', "/rabbitmq/set/{$instanceName}", $rabbitmqData);
    }

    /**
     * Find RabbitMQ
     */
    public function findRabbitmq($instanceName)
    {
        return $this->makeRequest('get', "/rabbitmq/find/{$instanceName}");
    }

    // ==================== SQS INTEGRATION ====================

    /**
     * Set SQS
     */
    public function setSqs($instanceName, $sqsData)
    {
        return $this->makeRequest('post', "/sqs/set/{$instanceName}", $sqsData);
    }

    /**
     * Find SQS
     */
    public function findSqs($instanceName)
    {
        return $this->makeRequest('get', "/sqs/find/{$instanceName}");
    }

    // ==================== TYPEBOT INTEGRATION ====================

    /**
     * Set Typebot
     */
    public function setTypebot($instanceName, $typebotData)
    {
        return $this->makeRequest('post', "/typebot/set/{$instanceName}", $typebotData);
    }

    /**
     * Find Typebot
     */
    public function findTypebot($instanceName)
    {
        return $this->makeRequest('get', "/typebot/find/{$instanceName}");
    }

    /**
     * Start Typebot
     */
    public function startTypebot($instanceName, $data)
    {
        return $this->makeRequest('post', "/typebot/start/{$instanceName}", $data);
    }

    /**
     * Change Typebot status
     */
    public function changeTypebotStatus($instanceName, $remoteJid, $status)
    {
        return $this->makeRequest('post', "/typebot/changeStatus/{$instanceName}", [
            'remoteJid' => $remoteJid,
            'status' => $status // opened, closed, paused
        ]);
    }
}
