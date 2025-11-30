<?php

namespace App\Services;

class FacebookLibsServices
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

        // POST request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);

        if ($isJson) {
            // For JSON data (like messages)
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
    // 1) تحويل Short إلى Long Token
    // -----------------------------
    public function exchangeLongLivedUserToken($shortToken)
    {
        return $this->call(
            'oauth/access_token',
            'GET',
            [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'fb_exchange_token' => $shortToken
            ]
        );
    }

    // -----------------------------
    // 2) جلب معلومات المستخدم
    // -----------------------------
    public function getProfile($accessToken)
    {
        return $this->call('me', 'GET', [
            'fields' => 'id,name,picture.type(large)',
            'access_token' => $accessToken
        ]);
    }

    // -----------------------------
    // 3) جلب الصفحات التي يديرها المستخدم
    // -----------------------------
    public function getPages($userAccessToken)
    {
        $pages = $this->call('me/accounts', 'GET', [
            'access_token' => $userAccessToken,
            'fields' => 'id,name,category,access_token,picture.type(large),tasks'
        ]);

        return $pages;
    }


    // -----------------------------
    // 4) البحث عن Page Access Token
    // -----------------------------
    public function getPageAccessToken($pageId, $userAccessToken)
    {
        $pages = $this->getPages($userAccessToken);

        if (!isset($pages['data'])) return null;

        foreach ($pages['data'] as $page) {
            if ($page['id'] == $pageId) {
                return $page['access_token'];
            }
        }
        return null;
    }

    // -----------------------------
    // 5) جلب منشورات الصفحة
    // -----------------------------
    public function getPagePosts($pageId, $pageAccessToken)
    {
        return $this->call("$pageId/posts", 'GET', [
            'access_token' => $pageAccessToken,
            'fields' => 'id,message,created_time,full_picture,attachments{media_type,media,url,subattachments},likes.summary(true),comments.summary(true),from'
        ]);
    }

    // -----------------------------
    // 6) نشر منشور على الصفحة
    // -----------------------------
    public function publishPost($pageId, $pageAccessToken, $message)
    {
        return $this->call("$pageId/feed", 'POST', [
            'message' => $message,
            'access_token' => $pageAccessToken
        ]);
    }

    // -----------------------------
    // 7) جلب تعليقات منشور معيّن
    // -----------------------------
    public function getPostComments($postId, $pageAccessToken)
    {
        return $this->call("$postId/comments", 'GET', [
            'access_token' => $pageAccessToken,
            'fields' => 'id,from,message,created_time'
        ]);
    }

    // -----------------------------
    // 8) الرد على تعليق
    // -----------------------------
    public function replyToComment($commentId, $pageAccessToken, $reply)
    {
        return $this->call("$commentId/comments", 'POST', [
            'message' => $reply,
            'access_token' => $pageAccessToken
        ]);
    }

    // -----------------------------
    // 9) عمل إعجاب على تعليق
    // -----------------------------
    public function likeComment($commentId, $pageAccessToken)
    {
        return $this->call("$commentId/likes", "POST", [
            'access_token' => $pageAccessToken
        ]);
    }

    // -----------------------------
    // 10) حذف تعليق أو منشور
    // -----------------------------
    public function deleteObject($objectId, $pageAccessToken)
    {
        return $this->call($objectId, 'POST', [
            'access_token' => $pageAccessToken,
            'method' => 'delete'
        ]);
    }

    // -----------------------------
    // 11) إرسال رسالة خاصة من خلال comment_id
    // -----------------------------
    public function sendPrivateMessage($commentId, $pageAccessToken, $message, $pageId = null)
    {
        // إذا لم يتم تمرير pageId، نستخرجه من الـ token أو نستخدم me
        $endpoint = $pageId ? "$pageId/messages" : "me/messages";

        return $this->call($endpoint, 'POST', [
            'recipient' => ['comment_id' => $commentId],
            'message' => ['text' => $message],
            'messaging_type' => 'RESPONSE',
            'access_token' => $pageAccessToken
        ], true);
    }

    // -----------------------------
    // 12) إرسال رسالة خاصة مع صورة
    // -----------------------------
    public function sendPrivateMessageWithImage($commentId, $pageAccessToken, $imageUrl, $pageId = null)
    {
        // إذا لم يتم تمرير pageId، نستخرجه من الـ token أو نستخدم me
        $endpoint = $pageId ? "$pageId/messages" : "me/messages";

        return $this->call($endpoint, 'POST', [
            'recipient' => [
                'comment_id' => $commentId
            ],
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
            'access_token' => $pageAccessToken
        ], true);
    }
}
