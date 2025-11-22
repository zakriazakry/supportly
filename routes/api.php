<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// ملف لتخزين التعليقات التي تم الرد عليها
$repliedCommentsFile = storage_path('replied_comments.json');
$repliedComments = file_exists($repliedCommentsFile)
    ? json_decode(file_get_contents($repliedCommentsFile), true)
    : [];

// ضع هنا معرف الصفحة الخاصة بك
$pageId = '61579627401428';

Route::get('facebook/webhook', function (Request $request) {
    if ($request->hub_verify_token === "test") {
        return response($request->hub_challenge, 200);
    }
    return response("Invalid token", 403);
});

Route::post('facebook/webhook', function (Request $request) use ($pageId, $repliedCommentsFile, &$repliedComments) {

    $pageAccessToken = "EAAL6cDIxiFcBQOgKKLSpZCC9qQGEdDmXZAMsx7UDKkSmG5jdd4lJAMUzh7CAHABajZCZAiCMTc2YIUwbjXqAijuZCZCCPxAR48vYTXLzSVoVPNYRzYDhT4JqzKg4W50YSduiEaWav0R7ZCmMiyzxUioqRtsOF3RqMqC0MBkijF7ar4C5y3ZAEPNK02tMabZCp6Xfsun0ZBZCRPnas7ZAjCV6H0ZCQlASyAZCOHMgXU5msEMga6HZAIZD";

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

            if (!$commentId) continue;

            // تجاهل التعليقات التي أنشأتها الصفحة نفسها
            if ($fromId === $pageId) continue;

            // تجاهل التعليقات التي تم الرد عليها مسبقًا
            if (in_array($commentId, $repliedComments)) continue;

            // فلترة كلمة "السعر"
            if (!str_contains($commentText, 'السعر')) continue;

            Log::info("Replying to comment: $commentId");

            // إرسال رد على التعليق مباشرة
            $reply = Http::post("https://graph.facebook.com/v24.0/{$commentId}/comments", [
                'message' => 'مرحباً! السعر هو 15$',
                'access_token' => $pageAccessToken
            ]);

            Log::info("Reply response:", $reply->json());

            // تخزين التعليق كمعلق عليه لتجنب التكرار
            $repliedComments[] = $commentId;
            file_put_contents($repliedCommentsFile, json_encode($repliedComments));
        }
    }

    return response("OK", 200);
});
