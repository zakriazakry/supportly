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

    $pageAccessToken = "EAAL6cDIxiFcBQIZAUN1w50CQE58MDjoTxFgaw5iIa0MRaAjC7jkxAPakcU3NxtDqpDolr38O2WhF6QkvbvYu1KRgCCpKcBn5mV79bLq5buLPRtZCHrqPj12wcZCZCNhscAepay7djIZAHPh9fwEwFnkLUKbM9Ec79Il5ZAusc0Mb2mu9nIR7EZC0FFl3ZBypnhnjXEPwW5QJdmlX3xD0q909UIEOmZBFbmvQlmwNFAtaVXjdeg5ynty8Dvw9TnIzU34DPMZB55VGbKnAWzD2eF"; // ← ضَع التوكن الخاص بك هنا

    if (!isset($request['entry'])) {
        return response("OK", 200);
    }

    foreach ($request['entry'] as $entry) {
        if (!isset($entry['changes'])) {
            continue;
        }

        foreach ($entry['changes'] as $change) {
            if ($change['field'] !== 'feed') {
                continue;
            }

            $commentRaw = $change['value'] ?? null;
            if (!$commentRaw) continue;

            $commentText = $commentRaw['message'] ?? '';
            $commentId   = $commentRaw['comment_id'] ?? null;
            $postId      = $commentRaw['post_id'] ?? null;
            $from        = $commentRaw['from']['id'] ?? null;

            if (!$commentId || !$postId) {
                Log::warning("Missing comment_id or post_id");
                continue;
            }

            Log::info("Comment detected: $commentId with message: $commentText");

            // فلترة كلمة "السعر"
            if (!str_contains($commentText, 'السعر')) {
                continue;
            }

            Log::info("Processing keyword 'السعر' for comment: $commentId");

            // 1️⃣ جلب التعليقات الحقيقية من المنشور
            $commentsList = Http::get("https://graph.facebook.com/v24.0/{$postId}/comments", [
                'fields' => 'id,message,from,created_time,can_reply_privately',
                'access_token' => $pageAccessToken
            ]);

            Log::info("Comments list:", $commentsList->json());

            if (!$commentsList->successful()) {
                Log::warning("Failed to fetch comments for post $postId");
                continue;
            }

            // 2️⃣ البحث عن الـ comment_id الحقيقي
            $realCommentId = null;
            $realCommentObj = null;

            foreach ($commentsList['data'] as $c) {
                if ($c['message'] === $commentText && ($c['from']['id'] ?? '') === $from) {
                    $realCommentId = $c['id'];
                    $realCommentObj = $c;
                    break;
                }
            }

            if (!$realCommentId) {
                Log::warning("Real comment ID NOT found for: $commentId");
                continue;
            }

            Log::info("Real comment ID resolved: $realCommentId");

            // 3️⃣ التحقق من إمكانية الرد الخاص
            if (!($realCommentObj['can_reply_privately'] ?? false)) {
                Log::warning("Private reply NOT allowed for this comment: $realCommentId");
                continue;
            }

            // 4️⃣ إرسال الرد الخاص
            $replyResponse = Http::post("https://graph.facebook.com/v24.0/{$realCommentId}/private_replies", [
                'message'      => 'مرحباً! السعر هو 15$',
                'access_token' => $pageAccessToken
            ]);

            Log::info("Private reply response:", $replyResponse->json());
        }
    }

    return response("OK", 200);
});
