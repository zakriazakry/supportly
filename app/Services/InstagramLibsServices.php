<?php

namespace App\Services;

class InstagramLibsServices
{
    protected $appId;
    protected $appSecret;

    public function __construct()
    {
        $this->appId = env('FACEBOOK_CLIENT_ID');
        $this->appSecret = env('FACEBOOK_CLIENT_SECRET');
    }

    private function call($endpoint, $method = 'GET', $params = [], $isJson = false)
    {
        $url = "https://graph.facebook.com/v24.0/" . $endpoint;

        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
            return json_decode(file_get_contents($url), true);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);

        if ($isJson) {
            $accessToken = $params['access_token'] ?? '';
            unset($params['access_token']);

            $url .= '?access_token=' . $accessToken;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    // -----------------------------
    // 1) جلب معلومات الحساب
    // -----------------------------
    public function getProfile($accessToken, $instagramAccountId)
    {
        return $this->call("$instagramAccountId", 'GET', [
            'fields' => 'id,username,account_type,media_count',
            'access_token' => $accessToken
        ]);
    }

    // -----------------------------
    // 2) جلب المنشورات
    // -----------------------------
    public function getMedia($instagramAccountId, $accessToken)
    {
        return $this->call("$instagramAccountId/media", 'GET', [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,timestamp,permalink,comments_count,like_count',
            'access_token' => $accessToken
        ]);
    }

    // -----------------------------
    // 3) إنشاء منشور جديد (صورة/فيديو)
    // -----------------------------
    public function createMedia($instagramAccountId, $accessToken, $mediaUrl, $caption = '', $mediaType = 'IMAGE')
    {
        $params = [
            'caption' => $caption,
            'access_token' => $accessToken
        ];

        if ($mediaType === 'IMAGE') {
            $params['image_url'] = $mediaUrl;
        } elseif ($mediaType === 'VIDEO') {
            $params['video_url'] = $mediaUrl;
        } else {
            throw new \Exception('Invalid media type. Use IMAGE or VIDEO.');
        }

        return $this->call("$instagramAccountId/media", 'POST', $params);
    }

    // -----------------------------
    // 4) نشر المنشور بعد إنشائه
    // -----------------------------
    public function publishMedia($instagramAccountId, $accessToken, $creationId)
    {
        return $this->call("$instagramAccountId/media_publish", 'POST', [
            'creation_id' => $creationId,
            'access_token' => $accessToken
        ]);
    }

    // -----------------------------
    // 5) جلب التعليقات
    // -----------------------------
    public function getComments($mediaId, $accessToken)
    {
        return $this->call("$mediaId/comments", 'GET', [
            'access_token' => $accessToken,
            'fields' => 'id,text,username,timestamp'
        ]);
    }

    // -----------------------------
    // 6) الرد على تعليق
    // -----------------------------
    public function replyToComment($commentId, $accessToken, $message)
    {
        return $this->call("$commentId/replies", 'POST', [
            'message' => $message,
            'access_token' => $accessToken
        ]);
    }

    // -----------------------------
    // 7) حذف تعليق
    // -----------------------------
    public function deleteComment($commentId, $accessToken)
    {
        return $this->call($commentId, 'POST', [
            'access_token' => $accessToken,
            'method' => 'delete'
        ]);
    }

    // -----------------------------
    // 8) إرسال رسالة مباشرة (نص)
    // -----------------------------
    public function sendDirectMessage($recipientId, $accessToken, $message)
    {
        return $this->call("$recipientId/messages", 'POST', [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
            'messaging_type' => 'RESPONSE',
            'access_token' => $accessToken
        ], true);
    }

    // -----------------------------
    // 9) إرسال رسالة مباشرة مع صورة
    // -----------------------------
    public function sendDirectMessageWithImage($recipientId, $accessToken, $imageUrl)
    {
        return $this->call("$recipientId/messages", 'POST', [
            'recipient' => ['id' => $recipientId],
            'message' => [
                'attachment' => [
                    'type' => 'image',
                    'payload' => [
                        'url' => $imageUrl,
                        'is_reusable' => true
                    ]
                ]
            ],
            'messaging_type' => 'RESPONSE',
            'access_token' => $accessToken
        ], true);
    }
}
