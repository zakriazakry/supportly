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

    $pageAccessToken = "YOUR_PAGE_ACCESS_TOKEN";  // 🔥 ضع التوكن الخاص بك هنا

    if (!isset($request['entry'])) {
        Log::info("No 'entry' found in Webhook payload.");
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

            $comment = $change['value'] ?? null;
            if (!$comment) continue;

            $commentText = $comment['message'] ?? '';
            $commentId   = $comment['comment_id'] ?? null;

            if (!$commentId) continue;

            Log::info("Comment detected: $commentId with message: $commentText");

            // فلترة التعليقات
            if (!str_contains($commentText, 'السعر')) {
                continue;
            }

            Log::info("Processing keyword 'السعر' for comment: $commentId");

            // 1️⃣ Lookup للحصول على الـ ID الحقيقي
            $lookup = Http::get("https://graph.facebook.com/v24.0/{$commentId}", [
                'fields' => 'id,message,from,can_reply_privately,parent',
                'access_token' => $pageAccessToken
            ]);

            Log::info("Comment lookup:", $lookup->json());

            if (!$lookup->successful() || !isset($lookup['id'])) {
                Log::warning("Failed to lookup comment or inaccessible comment.");
                continue;
            }

            // استخراج ID الحقيقي
            $realId = $lookup['id'];

            // 2️⃣ تحقق من إمكانية الرد الخاص
            if (!($lookup['can_reply_privately'] ?? false)) {
                Log::warning("Private reply not allowed for this comment: $realId");
                continue;
            }

            // 3️⃣ إرسال رسالة خاصة
            $replyResponse = Http::post("https://graph.facebook.com/v24.0/{$realId}/private_replies", [
                'message'      => 'مرحباً! السعر هو 15$',
                'access_token' => $pageAccessToken
            ]);

            Log::info("Private reply response:", $replyResponse->json());
        }
    }

    return response("OK", 200);
});
