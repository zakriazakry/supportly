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

    $pageAccessToken = "EAAL6cDIxiFcBQOgKKLSpZCC9qQGEdDmXZAMsx7UDKkSmG5jdd4lJAMUzh7CAHABajZCZAiCMTc2YIUwbjXqAijuZCZCCPxAR48vYTXLzSVoVPNYRzYDhT4JqzKg4W50YSduiEaWav0R7ZCmMiyzxUioqRtsOF3RqMqC0MBkijF7ar4C5y3ZAEPNK02tMabZCp6Xfsun0ZBZCRPnas7ZAjCV6H0ZCQlASyAZCOHMgXU5msEMga6HZAIZD"; // ← ضع التوكن الخاص بك هنا

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

            // 1️⃣ جلب جميع التعليقات للمنشور
            $commentsList = Http::get("https://graph.facebook.com/v24.0/{$postId}/comments", [
                'fields' => 'id,message,from,created_time,can_reply_privately',
                'access_token' => $pageAccessToken
            ]);

            if (!$commentsList->successful()) {
                Log::warning("Failed to fetch comments for post $postId");
                continue;
            }

            $commentsListData = $commentsList->json()['data'] ?? [];
            Log::info("Comments list fetched", $commentsListData);

            // 2️⃣ اختيار أحدث تعليق مطابق
            $realCommentObj = null;
            foreach ($commentsListData as $c) {
                if (($c['message'] ?? '') === $commentText && ($c['from']['id'] ?? '') === $from) {
                    if (!$realCommentObj) {
                        $realCommentObj = $c;
                    } else {
                        // مقارنة الوقت لاختيار الأحدث
                        if (strtotime($c['created_time']) > strtotime($realCommentObj['created_time'])) {
                            $realCommentObj = $c;
                        }
                    }
                }
            }

            if (!$realCommentObj) {
                Log::warning("Real comment ID NOT found for: $commentId");
                continue;
            }

            $realCommentId = $realCommentObj['id'];
            Log::info("Real comment ID resolved: $realCommentId");

            // 3️⃣ التحقق من إمكانية الرد الخاص
            if (!($realCommentObj['can_reply_privately'] ?? false)) {
                Log::warning("Private reply NOT allowed for this comment: $realCommentId. Sending public reply instead.");

                // إرسال رد عام كـ fallback
                $publicReply = Http::post("https://graph.facebook.com/v24.0/{$realCommentId}/comments", [
                    'message' => 'مرحباً! السعر هو 15$',
                    'access_token' => $pageAccessToken
                ]);

                Log::info("Public reply response:", $publicReply->json());
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
