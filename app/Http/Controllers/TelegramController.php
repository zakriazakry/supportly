<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $update = $telegram->getWebhookUpdate();

        $chatId = $update->getMessage()->getChat()->getId();
        $text = $update->getMessage()->getText();

        if ($text === '/start') {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "أهلاً بك في بوت Laravel 🚀",
            ]);
        } else if ($text === '/local') {
            $telegram->sendLocation([
                'chat_id' => $chatId,
                'latitude' => 37.7749, // Example latitude
                'longitude' => -122.4194, // Example longitude
                'live_period' => 60, // Optional: for live locations
            ]);
        } else {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "رسالتك: $text",
            ]);
        }

        return response('ok', 200);
    }
}
