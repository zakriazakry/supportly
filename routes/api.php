<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('facebook/webhook', function (Request $request) {
    Log::info("GET Webhook Check:", $request->all());

    if ($request->hub_verify_token === "test") {
        return response($request->hub_challenge, 200);
    }

    return response("Invalid token", 403);
});

Route::post('facebook/webhook', function (Request $request) {
    Log::info("POST Webhook Received:", $request->all());

    $pageAccessToken = "EAAL6cDIxiFcBQOgKKLSpZCC9qQGEdDmXZAMsx7UDKkSmG5jdd4lJAMUzh7CAHABajZCZAiCMTc2YIUwbjXqAijuZCZCCPxAR48vYTXLzSVoVPNYRzYDhT4JqzKg4W50YSduiEaWav0R7ZCmMiyzxUioqRtsOF3RqMqC0MBkijF7ar4C5y3ZAEPNK02tMabZCp6Xfsun0ZBZCRPnas7ZAjCV6H0ZCQlASyAZCOHMgXU5msEMga6HZAIZD";

    if (!isset($request['entry'])) {
        return response("OK", 200);
    }

    foreach ($request['entry'] as $entry) {
        if (!isset($entry['changes'])) continue;

        foreach ($entry['changes'] as $change) {
            if ($change['field'] !== 'feed') continue;

            $commentRaw = $change['value'] ?? null;
            if (!$commentRaw) continue;

            $commentText = $commentRaw['message'] ?? '';
            $commentId   = $commentRaw['comment_id'] ?? null;
            $postId      = $commentRaw['post_id'] ?? null;

            if (!$commentId || !$postId) continue;

            Log::info("Comment detected: $commentId with message: $commentText");

            // فلترة كلمة "السعر"
            if (!str_contains($commentText, 'السعر')) continue;

            Log::info("Replying to comment: $commentId");

            // إرسال رد على التعليق مباشرة
            $reply = Http::post("https://graph.facebook.com/v24.0/{$commentId}/comments", [
                'message' => 'مرحباً! السعر هو 15$',
                'access_token' => $pageAccessToken
            ]);

            Log::info("Reply response:", $reply->json());
        }
    }

    return response("OK", 200);
});
