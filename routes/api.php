<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/bot/webhook', [TelegramController::class, 'handle']);
Route::get('facebook/webhook', function (Request $request) {
    // التحقق من token
    if ($request->hub_verify_token === "test") {
        return response($request->hub_challenge, 200);
    }
    return response("Invalid token", 403);
});

Route::post('facebook/webhook', function (Request $request) {
    Log::info("Facebook Webhook Received:", $request->all());

    $pageAccessToken = "EAAL6cDIxiFcBP2HKE26H0urVMN95OyTCdyh8aXBp80QxeS6lkS23Pu1kxnMfJrGCv9rZCAveZAcdlQJ9OukyCmeoLTDy3MsZAIg9URsqZBgZAVfZBn8ILZCIKozDWnZADThShBPb4g2ZC1CTrxZAoqvC0x9AcfeOGCWtMRx5f7guVoVEZAsdYnCjOGquHZAgx1sGZCETMYdOvKM435Vo5VZCDMB2OFF3FPr5dRkXzoKSO54QZDZD"; // ضع هنا توكن الصفحة

    if (!isset($request['entry'])) {
        return response("OK", 200);
    }

    foreach ($request['entry'] as $entry) {
        if (!isset($entry['changes'])) continue;

        foreach ($entry['changes'] as $change) {
            if ($change['field'] !== 'feed') continue;

            $comment = $change['value'] ?? null;
            if (!$comment) continue;

            $commentText = $comment['message'] ?? '';
            $commentId   = $comment['comment_id'] ?? null;

            if (!$commentId) continue;

            // فلترة التعليقات على كلمة "السعر"
            if (str_contains($commentText, 'السعر')) {

                // 1️⃣ وضع لايك على التعليق
                Http::post("https://graph.facebook.com/v24.0/{$commentId}/likes", [
                    'access_token' => $pageAccessToken
                ]);

                // 2️⃣ إرسال رسالة خاصة (Private Reply)
                Http::post("https://graph.facebook.com/v24.0/{$commentId}/private_replies", [
                    'message'      => 'مرحباً! السعر هو 15$',
                    'access_token' => $pageAccessToken
                ]);

                Log::info("Comment processed: $commentId");
            }
        }
    }

    return response("OK", 200);
});
