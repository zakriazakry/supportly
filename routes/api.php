<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

$repliedCommentsFile = storage_path('replied_comments.json');
$repliedComments = file_exists($repliedCommentsFile)
    ? json_decode(file_get_contents($repliedCommentsFile), true)
    : [];

$pageId = '61579627401428';
$pageAccessToken = "EAAL6cDIxiFcBQDqT9FcbYL85ZBTwQutkHZBRPSTiAxuwbKQsNTMJSGO1dJZAPfoHiD2fpOPhp1F5DPnbZB5UyUuVZBuZCwGZBEOlz11h9yPo8HflY3ERYrgGYx6aaD0ZAbDRSSUEAAz5x8PLYACRVd1EPPTBBq9pVu5wPfZAqltlTYcg1ej5ZBZCXPjfdLNchQYWBxWJvcxeRzMWQljoS81V9O04FmJ6pxEADcIOwfnHQQZD";

// Webhook Verification
Route::get('facebook/webhook', function (Request $request) {
    if ($request->hub_verify_token === "test") {
        return response($request->hub_challenge, 200);
    }
    return response("Invalid token", 403);
});

// Webhook Receiver
Route::post('facebook/webhook', function (Request $request) use (&$repliedComments, $repliedCommentsFile, $pageId, $pageAccessToken) {

    if (!isset($request['entry'])) return response("OK", 200);

    foreach ($request['entry'] as $entry) {
        if (!isset($entry['changes'])) continue;

        foreach ($entry['changes'] as $change) {
            if ($change['field'] !== 'feed') continue;

            $commentRaw = $change['value'] ?? null;
            if (!$commentRaw) continue;

            $commentText = $commentRaw['message'] ?? '';
            $commentId   = $commentRaw['comment_id'] ?? null;
            $fromId      = $commentRaw['from']['id'] ?? '';
            $postId      = $commentRaw['post_id'] ?? null;

            if (!$commentId) continue;

            // Ignore page's own comments
            if ($fromId === $pageId) continue;

            // Ignore already replied comments
            if (in_array($commentId, $repliedComments)) continue;

            // Filter keyword
            if (!str_contains($commentText, 'السعر')) continue;

            Log::info("Processing comment: " . $commentId);

            // Like the comment
            Http::post("https://graph.facebook.com/v24.0/{$commentId}/likes?access_token={$pageAccessToken}");

            $replyText = "تم الرد في الخاص ✅";
            $replyComment = Http::post("https://graph.facebook.com/v24.0/{$commentId}/comments", [
                'message' => $replyText,
                'access_token' => $pageAccessToken
            ]);
            Log::info("Replied on comment {$commentId} with message: '{$replyText}'");


            // Build Post URL
            $postUrl = $postId
                ? "https://www.facebook.com/{$pageId}/posts/{$postId}"
                : "https://www.facebook.com/{$pageId}";

            // السعر أو العرض
            $price = "🎯 العرض الخاص: 99 دينار فقط!";

            // صورة احترافية عالية الجودة
            $imageUrl = "https://store-images.s-microsoft.com/image/apps.3117.14492969036550054.5a1d40f5-fe0d-427a-bd14-9a9ed15a423c.f601beb2-973f-47de-ad1a-ccec296ee4d1"; // رابط الصورة

            // ارسال القالب الاحترافي
            $sendTemplate = Http::post("https://graph.facebook.com/v17.0/me/messages?access_token={$pageAccessToken}", [
                'recipient' => [
                    'comment_id' => $commentId
                ],
                'message' =>  [
                    'text' => $price
                ],
                'messaging_type' => 'RESPONSE'
            ]);

            // حفظ comment_id لمنع التكرار
            $repliedComments[] = $commentId;
            file_put_contents($repliedCommentsFile, json_encode($repliedComments));
        }
    }

    return response("OK", 200);
});
