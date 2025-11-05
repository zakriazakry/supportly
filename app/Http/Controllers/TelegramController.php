<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ISPService;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    protected $telegram;
    protected $isp;

    public function __construct(ISPService $isp)
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->isp = $isp;
    }

    public function handle(Request $request)
    {
        $update = $this->telegram->getWebhookUpdate();
        Log::info('Telegram Update Received:', $update->toArray());

        try {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = trim($message->getText());

            $user = DB::table('telegram_users')->where('chat_id', $chatId)->first();

            // ---- التسجيل ----
            if (!$user) {
                $step = DB::table('telegram_sessions')->where('chat_id', $chatId)->value('step');

                // البداية: طلب اسم المستخدم
                if (!$step) {
                    DB::table('telegram_sessions')->updateOrInsert(
                        ['chat_id' => $chatId],
                        ['step' => 'awaiting_username']
                    );

                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "👋 أهلاً بك في نظام سبارك.\nمن فضلك أرسل اسم المستخدم الخاص بك:",
                    ]);
                    return response('ok', 200);
                }

                // بعد إدخال اسم المستخدم
                if ($step === 'awaiting_username') {
                    DB::table('telegram_sessions')->updateOrInsert(
                        ['chat_id' => $chatId],
                        ['step' => 'awaiting_password', 'username' => $text]
                    );
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '🔐 أدخل كلمة المرور الخاصة بك:'
                    ]);
                    return response('ok', 200);
                }

                // بعد إدخال كلمة المرور → تحقق من API عبر ISPService
                if ($step === 'awaiting_password') {
                    $session = DB::table('telegram_sessions')->where('chat_id', $chatId)->first();
                    $username = $session->username;
                    $password = $text;

                    $response = Http::post('https://restsp.sparktech.ly/api/auth/login', [
                        'username' => $username,
                        'password' => $password,
                        'firebase_token' => 'aaa',
                    ]);
                    Log::info($response);
                    if ($response->successful()) {
                        $data = $response->json()['data'];

                        DB::table('telegram_users')->insert([
                            'chat_id' => $chatId,
                            'username' => $username,
                            'token' => $data['api_key'],
                        ]);

                        DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();

                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "✅ تم تسجيل الدخول بنجاح!\nاختر من القائمة التالية:",
                            'reply_markup' => json_encode([
                                'keyboard' => [
                                    ["💰 تعبئة الرصيد", "🔄 تجديد الباقة"],
                                    ["📦 عرض الباقات", "🛠 الدعم الفني"],
                                    ["🚪 تسجيل الخروج"]
                                ],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => false
                            ])
                        ]);
                    } else {
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => '❌ اسم المستخدم أو كلمة المرور غير صحيحة، حاول مرة أخرى.'
                        ]);
                        DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();
                    }

                    return response('ok', 200);
                }
            }

            // ---- المستخدم مسجل الدخول ----
            switch ($text) {
                case '💰 تعبئة الرصيد':
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '🔢 أدخل رقم البطاقة أو رمز التعبئة:'
                    ]);
                    break;

                case '🔄 تجديد الباقة':
                    $user = DB::table('telegram_users')->where('chat_id', $chatId)->first();
                    $response = $this->isp->renew($user->token);

                    $msg = $response->successful()
                        ? "♻️ تم تجديد الباقة بنجاح!"
                        : "⚠️ تعذر تجديد الباقة، حاول لاحقًا.";

                    $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
                    break;

                case '📦 عرض الباقات':
                    $user = DB::table('telegram_users')->where('chat_id', $chatId)->first();
                    $response = $this->isp->getProfiles($user->token);

                    if ($response->successful()) {
                        $packages = $response->json();
                        $msg = "📦 الباقات المتاحة:\n";
                        foreach ($packages as $pkg) {
                            $msg .= "- {$pkg['name']} ({$pkg['price']} ريال)\n";
                        }
                    } else {
                        $msg = "⚠️ تعذر جلب الباقات من السيرفر.";
                    }

                    $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
                    break;

                case '🛠 الدعم الفني':
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "📞 مرحباً بك في الدعم الفني، اختر مشكلتك:\n- لماذا الإنترنت مقطوع؟\n- لماذا السرعة ضعيفة؟\n- مشكلة أخرى",
                    ]);
                    break;

                case '🚪 تسجيل الخروج':
                    DB::table('telegram_users')->where('chat_id', $chatId)->delete();
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '👋 تم تسجيل الخروج بنجاح. أرسل /start لتسجيل الدخول من جديد.'
                    ]);
                    break;

                default:
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '📨 استخدم الأزرار المتاحة في الأسفل لاختيار الخدمة.'
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Telegram Bot Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return response('ok', 200);
    }
}
