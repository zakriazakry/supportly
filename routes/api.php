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

    $pageAccessToken = "EAAL6cDIxiFcBP53oHueU0GpfB76OIEVrPyQZBrWwG6svDetpZCaKZBou9LeUTnT8tSIYkpi8slFNqNLhf6PZAmX9bxmaL5ZBTTfXFiKzv3Xn6qd9xaRTLI8f0Eebx2qWKS08cFYHdGHusUkiJWzot8OXRDRlHyZABxOLRoJq5cOZCn37WGULoNkkUO21m5h1cZANLooF9VD86KSouw0KT2YtuCzMZBmTi3ZADLmt1njseQ"; // ضع هنا توكن الصفحة

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
