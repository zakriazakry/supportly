<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

$repliedCommentsFile = storage_path('replied_comments.json');
$repliedComments = file_exists($repliedCommentsFile)
    ? json_decode(file_get_contents($repliedCommentsFile), true)
    : [];

Route::get('facebook/webhook', function (Request $request) {
    if ($request->hub_verify_token === "test") {
        return response($request->hub_challenge, 200);
    }
    return response("Invalid token", 403);
});

Route::post('facebook/webhook', function (Request $request) use (&$repliedComments, $repliedCommentsFile) {
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

            // تجاهل التعليقات التي تم الرد عليها مسبقًا
            if (in_array($commentId, $repliedComments)) continue;

            // فلترة كلمة "السعر"
            if (!str_contains($commentText, 'السعر')) continue;

            Log::info("Replying to comment: $commentId");

            // إرسال الرد
            $reply = Http::post("https://graph.facebook.com/v24.0/{$commentId}/comments", [
                'message' => 'مرحباً! السعر هو 15$',
                'access_token' => $pageAccessToken
            ]);

            $responseJson = $reply->json();
            Log::info("Reply response:", $responseJson);

            // تخزين التعليق الأصلي الذي تم الرد عليه
            $repliedComments[] = $commentId;

            // تخزين أي تعليقات جديدة تم إنشاؤها بواسطة الرد لتجنب التكرار لاحقًا
            if (isset($responseJson['id'])) {
                $repliedComments[] = $responseJson['id'];
            }

            file_put_contents($repliedCommentsFile, json_encode($repliedComments));
        }
    }

    return response("OK", 200);
});
