<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use App\Models\WebhookEvent;

class WebhookHelper
{
    /**
     * Send webhook HTTP request
     */
    static function sendWebhook($webhook, $payload)
    {
        $startTime = microtime(true);
        $success = false;
        $responseStatus = null;
        $errorMessage = null;

        try {
            // Add signature to headers
            $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret);

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'Content-Type' => 'application/json'
                ])
                ->retry(3, 100) // Retry 3 times with 100ms delay
                ->post($webhook->url, $payload);

            $responseStatus = $response->status();
            $success = $response->successful();
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $success = false;
        }

        $responseTime = (int)((microtime(true) - $startTime) * 1000);

        // Log the event
        WebhookEvent::create([
            'webhook_id' => $webhook->id,
            'event_type' => $payload['event'] ?? 'unknown',
            'payload' => $payload,
            'response_status' => $responseStatus,
            'response_time' => $responseTime,
            'success' => $success,
            'error_message' => $errorMessage
        ]);

        // Update webhook statistics
        $webhook->increment('total_calls');
        if ($success) {
            $webhook->last_triggered = now();
        }

        // Update success rate
        self::updateSuccessRate($webhook);

        return [
            'success' => $success,
            'response_status' => $responseStatus,
            'response_time' => $responseTime,
            'error' => $errorMessage
        ];
    }

    /**
     * Update webhook success rate
     */
    function updateSuccessRate($webhook)
    {
        $totalEvents = WebhookEvent::where('webhook_id', $webhook->id)->count();

        if ($totalEvents > 0) {
            $successfulEvents = WebhookEvent::where('webhook_id', $webhook->id)
                ->where('success', true)
                ->count();

            $successRate = ($successfulEvents / $totalEvents) * 100;
            $webhook->success_rate = round($successRate, 2);
            $webhook->save();
        }
    }
}
