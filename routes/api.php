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

    $pageAccessToken = "EAAL6cDIxiFcBQCX2ZBCZCTinN0MMTkibGDyRXk0FqT0y1vjdI5K61QjBLkGmBJuL1CcCUB5ZBDZCz3vtrUcimwKL2EWW85dYQ0F7BaRZBiLZAeIqn9LB6RJoa0OIuyKDDvRKT5QMIfiaQWxd5bsZCSBLlhbOARzajl2Hqf1ORaA6u7EAJ78z1FcjOaZBOm9EhNVjx1woiWqm01ZB1ZC9zNB5Lexo4H41FJquyWl49Vxg36IVF3sB2OqCgn6abujcdX7UVWN437AcppuRg2tWGMGAZDZD";

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
