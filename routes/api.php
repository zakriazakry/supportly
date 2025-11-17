<?

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

    $pageAccessToken = "EAAL6cDIxiFcBP53oHueU0GpfB76OIEVrPyQZBrWwG6svDetpZCaKZBou9LeUTnT8tSIYkpi8slFNqNLhf6PZAmX9bxmaL5ZBTTfXFiKzv3Xn6qd9xaRTLI8f0Eebx2qWKS08cFYHdGHusUkiJWzot8OXRDRlHyZABxOLRoJq5cOZCn37WGULoNkkUO21m5h1cZANLooF9VD86KSouw0KT2YtuCzMZBmTi3ZADLmt1njseQ";

    if (!isset($request['entry'])) {
        Log::info("No 'entry' found in Webhook payload.");
        return response("OK", 200);
    }

    foreach ($request['entry'] as $entryIndex => $entry) {
        Log::info("Processing entry index: $entryIndex", $entry);

        if (!isset($entry['changes'])) {
            Log::info("No 'changes' in entry index: $entryIndex");
            continue;
        }

        foreach ($entry['changes'] as $changeIndex => $change) {
            Log::info("Processing change index: $changeIndex", $change);

            if ($change['field'] !== 'feed') {
                Log::info("Skipping non-feed change field: {$change['field']}");
                continue;
            }

            $comment = $change['value'] ?? null;
            if (!$comment) {
                Log::info("No 'value' in feed change at index: $changeIndex");
                continue;
            }

            $commentText = $comment['message'] ?? '';
            $commentId   = $comment['comment_id'] ?? null;

            if (!$commentId) {
                Log::info("No comment_id found in comment", $comment);
                continue;
            }

            Log::info("Comment detected: $commentId with message: $commentText");

            // فلترة التعليقات على كلمة "السعر"
            if (str_contains($commentText, 'السعر')) {
                Log::info("Processing comment with keyword 'السعر': $commentId");

                // 1️⃣ وضع لايك على التعليق
                $likeResponse = Http::post("https://graph.facebook.com/v24.0/{$commentId}/likes", [
                    'access_token' => $pageAccessToken
                ]);
                Log::info("Like response for comment $commentId", $likeResponse->json());

                // 2️⃣ إرسال رسالة خاصة (Private Reply)
                $replyResponse = Http::post("https://graph.facebook.com/v24.0/{$commentId}/private_replies", [
                    'message'      => 'مرحباً! السعر هو 15$',
                    'access_token' => $pageAccessToken
                ]);
                Log::info("Private reply response for comment $commentId", $replyResponse->json());
            } else {
                Log::info("Comment does not contain keyword 'السعر': $commentId");
            }
        }
    }

    return response("OK", 200);
});
