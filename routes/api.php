<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// --- Configuration and Initialization ---

$repliedCommentsFile = storage_path('replied_comments.json');
$repliedComments = file_exists($repliedCommentsFile)
    ? json_decode(file_get_contents($repliedCommentsFile), true)
    : [];

$pageId = '61579627401428'; // معرف الصفحة
$pageAccessToken = "EAAL6cDIxiFcBQKDqkuzcyVhl44RhDyIIab86LJeVYfloLSAUXi3GhX3S3hGKck8vnHIpUTcoi4CmIOlsZAZCbGCQKWg1mnKZBZC7olMHPTBiccA4f10bNSYENlt3AFL6I8RYMQgYvFX9OKsBhbIB2cYVYyQsXlAmaWLM4B1m33ZBPWkHQfPAxIrm00vwZBd6iXhUpZASe4cd0tQaXOQd87u40QI5ZCGKDejWGLoS1GPAZAa1BDfGKLxDQNaASlpU083n0AQeLS2Mxd6eZBFs3L";

// --- Webhook Verification Route (GET) ---

Route::get('facebook/webhook', function (Request $request) {
    if ($request->hub_verify_token === "test") {
        return response($request->hub_challenge, 200);
    }
    return response("Invalid token", 403);
});

// --- Webhook Event Handling Route (POST) ---

Route::post('facebook/webhook', function (Request $request) use (&$repliedComments, $repliedCommentsFile, $pageId, $pageAccessToken) {
    // 1. Basic validation and structure check
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

            // 2. Filtration Logic

            // Ignore comments made by the page itself
            if ($fromId === $pageId) continue;

            // Ignore comments that have been replied to already
            if (in_array($commentId, $repliedComments)) continue;

            // Filter for the keyword "السعر" (Price)
            if (!str_contains($commentText, 'السعر')) continue;

            Log::info("Processing reply for comment: $commentId");

            // --- 3. Corrected Private Reply Attempt (Preferred) ---

            // استخدام المسار الصحيح لإرسال الرد الخاص: /COMMENT-ID/private_replies
            $privateReply = Http::post("https://graph.facebook.com/v24.0/{$commentId}/private_replies", [
                'message'   => 'مرحباً! السعر هو 15$', // The message text directly
                'access_token' => $pageAccessToken
            ]);

            $responseJson = $privateReply->json();
            Log::info("Private reply response:", $responseJson);

            // --- 4. Public Reply Fallback (If Private Fails) ---

            // إذا فشل الرد الخاص، أرسل رد عام كـ fallback
            if (isset($responseJson['error'])) {
                Log::warning("Private reply failed, sending public reply for comment: $commentId");

                // إرسال رد عام (Public Reply)
                $publicReply = Http::post("https://graph.facebook.com/v24.0/{$commentId}/comments", [
                    'message' => 'مرحباً! السعر هو 15$',
                    'access_token' => $pageAccessToken
                ]);

                $responseJson = $publicReply->json();
                Log::info("Public reply response:", $responseJson);
            }

            // --- 5. State Storage ---

            // تخزين التعليق الأصلي كمعلق عليه لتجنب التكرار في المستقبل
            $repliedComments[] = $commentId;

            // حفظ التغييرات على ملف التخزين
            file_put_contents($repliedCommentsFile, json_encode($repliedComments));
        }
    }

    return response("OK", 200);
});
