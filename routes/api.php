<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// -------------------------
// Configuration
// -------------------------

$repliedCommentsFile = storage_path('replied_comments.json');
$repliedComments = file_exists($repliedCommentsFile)
    ? json_decode(file_get_contents($repliedCommentsFile), true)
    : [];

$pageId = '61579627401428';
$pageAccessToken = "EAAL6cDIxiFcBQDqT9FcbYL85ZBTwQutkHZBRPSTiAxuwbKQsNTMJSGO1dJZAPfoHiD2fpOPhp1F5DPnbZB5UyUuVZBuZCwGZBEOlz11h9yPo8HflY3ERYrgGYx6aaD0ZAbDRSSUEAAz5x8PLYACRVd1EPPTBBq9pVu5wPfZAqltlTYcg1ej5ZBZCXPjfdLNchQYWBxWJvcxeRzMWQljoS81V9O04FmJ6pxEADcIOwfnHQQZD";

// -------------------------
// Webhook VERIFY (GET)
// -------------------------

Route::get('facebook/webhook', function (Request $request) {
    if ($request->hub_verify_token === "test") {
        return response($request->hub_challenge, 200);
    }
    return response("Invalid token", 403);
});

// -------------------------
// Webhook Receiver (POST)
// -------------------------

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

            // 1. Ignore comments made by the page itself
            if ($fromId === $pageId) continue;

            // 2. Ignore comments already replied to
            if (in_array($commentId, $repliedComments)) continue;

            // 3. Filter only comments containing the keyword "السعر"
            if (!str_contains($commentText, 'السعر')) continue;

            Log::info("Processing comment: " . $commentId);


            // -------------------------
            // STEP 1 — LIKE the comment
            // -------------------------

            Http::post("https://graph.facebook.com/v24.0/{$commentId}/likes?access_token={$pageAccessToken}");


            // -------------------------
            // STEP 2 — BUILD POST URL
            // -------------------------

            $postUrl = $postId
                ? "https://www.facebook.com/{$pageId}/posts/{$postId}"
                : "https://www.facebook.com/{$pageId}";

            // السعر — تستطيع تغييره ديناميكيًا
            $price = "السعر هو 99 دينار فقط";


            // -------------------------
            // STEP 3 — SEND TEMPLATE PRIVATE REPLY
            // -------------------------

            $sendTemplate = Http::post("https://graph.facebook.com/v17.0/me/messages?access_token={$pageAccessToken}", [
                'recipient' => [
                    'comment_id' => $commentId
                ],
                'message' => [
                    'attachment' => [
                        'type' => 'template',
                        'payload' => [
                            'template_type' => 'generic',
                            'elements' => [
                                [
                                    'title' => "السعر",
                                    'subtitle' => $price,
                                    'buttons' => [
                                        [
                                            'type' => 'web_url',
                                            'url'  => $postUrl,
                                            'title' => 'مشاهدة المنشور'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'messaging_type' => 'RESPONSE'
            ]);


            // -------------------------
            // STEP 4 — SAVE comment_id
            // -------------------------

            $repliedComments[] = $commentId;
            file_put_contents($repliedCommentsFile, json_encode($repliedComments));
        }
    }

    return response("OK", 200);
});
