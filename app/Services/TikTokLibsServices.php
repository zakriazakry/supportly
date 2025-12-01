<?php

namespace App\Services;

class TikTokLibsServices
{
    protected $accessToken;
    protected $openId;

    public function __construct($accessToken, $openId)
    {
        $this->accessToken = $accessToken;
        $this->openId = $openId;
    }

    private function call($url, $method = 'POST', $data = [], $isJson = true)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($isJson) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json;charset=UTF-8'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    public function initVideoUpload($videoSize, $title = '', $privacy = 'PUBLIC')
    {
        $endpoint = 'https://open.tiktokapis.com/v2/post/publish/video/init/';
        return $this->call($endpoint, 'POST', [
            'post_info' => [
                'title' => $title,
                'privacy_level' => $privacy
            ],
            'source_info' => [
                'source' => 'FILE_UPLOAD',
                'video_size' => $videoSize
            ]
        ]);
    }

    public function uploadVideoFile($uploadUrl, $filePath)
    {
        // تحميل الفيديو على uploadUrl — غالبًا باستخدام multipart/form-data
        // ثم استجابة: محتوى upload + body مع file
    }

    public function publishVideo($creationId)
    {
        $endpoint = 'https://open.tiktokapis.com/v2/post/publish/video/';
        return $this->call($endpoint, 'POST', [
            'open_id' => $this->openId,
            'creation_id' => $creationId
        ]);
    }

    public function publishImageFromUrl($imageUrl, $caption = '', $privacy = 'PUBLIC')
    {
        $endpoint = 'https://open.tiktokapis.com/v2/post/publish/image/';
        return $this->call($endpoint, 'POST', [
            'open_id' => $this->openId,
            'image_url' => $imageUrl,
            'caption' => $caption,
            'privacy_level' => $privacy
        ]);
    }
}
