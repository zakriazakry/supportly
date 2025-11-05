<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $update = $telegram->getWebhookUpdate();

        // سجلّ كامل للـ update القادم من Telegram
        Log::info('Telegram Update Received:', $update->toArray());

        try {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = trim($message->getText());

            Log::info("User Message:", ['chat_id' => $chatId, 'text' => $text]);

            switch ($text) {
                case '/start':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "👋 أهلاً بك في بوت Laravel التجريبي!\nجرب الأوامر التالية:\n
/start - بدء\n
/local - موقع\n
/animation - فيديو متحرك\n
/photo - إرسال صورة\n
/file - إرسال ملف\n
/audio - إرسال صوت\n
/sticker - إرسال ملصق\n
/video - إرسال فيديو\n
/help - قائمة المساعدة",
                    ]);
                    break;

                case '/local':
                    $telegram->sendLocation([
                        'chat_id' => $chatId,
                        'latitude' => 37.7749,
                        'longitude' => -122.4194,
                    ]);
                    Log::info("Sent location to user: $chatId");
                    break;

                case '/animation':
                    $telegram->sendAnimation([
                        'chat_id' => $chatId,
                        'animation' => 'https://file-examples.com/storage/fe340c6007655640a9a73a8/2017/04/file_example_MP4_480_1_5MG.mp4',
                        'caption' => '🎞 مثال لفيديو متحرك (Animation)',
                    ]);
                    Log::info("Sent animation to user: $chatId");
                    break;

                case '/photo':
                    $telegram->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => 'https://lightcyan-turtle-491856.hostingersite.com/img.png',
                        'caption' => '📷 هذه صورة تجريبية من Laravel Bot!',
                    ]);
                    Log::info("Sent photo to user: $chatId");
                    break;

                case '/file':
                    $telegram->sendDocument([
                        'chat_id' => $chatId,
                        'document' => 'https://file-examples.com/storage/fe340c6007655640a9a73a8/2017/10/file-example_PDF_500_kB.pdf',
                        'caption' => '📄 ملف PDF تجريبي',
                    ]);
                    Log::info("Sent document to user: $chatId");
                    break;

                case '/audio':
                    $telegram->sendAudio([
                        'chat_id' => $chatId,
                        'audio' => 'https://file-examples.com/storage/fe340c6007655640a9a73a8/2017/11/file_example_MP3_700KB.mp3',
                        'caption' => '🎵 ملف صوتي تجريبي',
                    ]);
                    Log::info("Sent audio to user: $chatId");
                    break;

                case '/sticker':
                    $telegram->sendSticker([
                        'chat_id' => $chatId,
                        'sticker' => 'CAACAgIAAxkBAAEF3Z9mZ3zHYa6j3ICazfJjWcR5mM7eHwACgAADVp29CqTHQX6p8bB4y8E', // مثال Sticker ID
                    ]);
                    Log::info("Sent sticker to user: $chatId");
                    break;

                case '/video':
                    $telegram->sendVideo([
                        'chat_id' => $chatId,
                        'video' => 'https://file-examples.com/storage/fe340c6007655640a9a73a8/2017/04/file_example_MP4_480_1_5MG.mp4',
                        'caption' => '🎬 فيديو تجريبي من Laravel Bot',
                    ]);
                    Log::info("Sent video to user: $chatId");
                    break;

                case '/help':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "🧭 قائمة الأوامر المتاحة:\n
/start - ترحيب\n
/local - موقع\n
/photo - صورة\n
/file - ملف\n
/audio - صوت\n
/sticker - ملصق\n
/video - فيديو\n
/animation - فيديو متحرك\n
/help - المساعدة",
                    ]);
                    break;

                default:
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "📨 رسالتك: $text",
                    ]);
                    Log::info("Echoed message back to user: $chatId");
            }
        } catch (\Exception $e) {
            Log::error('Telegram Bot Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return response('ok', 200);
    }
}
