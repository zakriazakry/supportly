<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('facebook/webhook', function (Request $request) {
    Log::info("GET Webhook Check:", $request->all());

    if ($request->hub_verify_token === "test") {
        Log::info("Webhook Verified Successfully.");
        return response($request->hub_challenge, 200);
    }

    Log::warning("Invalid Webhook Token:", $request->all());
    return response("Invalid token", 403);
});

Route::post('facebook/webhook', function (Request $request) {
    Log::info("POST Webhook Received:", $request->all());

    $pageAccessToken = "YOUR_PAGE_ACCESS_TOKEN";

    if (!isset($request['entry'])) {
        Log::info("No 'entry' found in Webhook payload.");
        return response("OK", 200);
    }

    foreach ($request['entry'] as $entryIndex => $entry) {
        if (!isset($entry['changes'])) {
            continue;
        }

        foreach ($entry['changes'] as $changeIndex => $change) {
            if ($change['field'] !== 'feed') {
                continue;
            }

            $comment = $change['value'] ?? null;
            if (!$comment) continue;

            $commentText = $comment['message'] ?? '';
            $commentId   = $comment['comment_id'] ?? null;

            if (!$commentId) continue;

            Log::info("Comment detected: $commentId with message: $commentText");

            // فلترة التعليقات على كلمة السعر
            if (str_contains($commentText, 'السعر')) {

                Log::info("Processing 'السعر' keyword in comment: $commentId");

                // ❌ حذف جزء الـ Like لأنه ممنوع في NPE
                // ✔ فقط Private Reply

                $replyResponse = Http::post("https://graph.facebook.com/v24.0/{$commentId}/private_replies", [
                    'message'      => 'مرحباً! السعر هو 15$',
                    'access_token' => $pageAccessToken
                ]);

                Log::info("Private reply response for comment $commentId", $replyResponse->json());
            }
        }
    }

    return response("OK", 200);
});
