<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $update = $telegram->getWebhookUpdate();

        Log::info('Telegram Update Received:', $update->toArray());

        try {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = trim($message->getText());

            // التحقق إن كان المستخدم موجودًا في قاعدة البيانات
            $user = DB::table('telegram_users')->where('chat_id', $chatId)->first();

            if (!$user) {
                // إذا لم يكن مسجلاً، نبدأ عملية التسجيل
                $step = DB::table('telegram_sessions')->where('chat_id', $chatId)->value('step');

                if (!$step) {
                    DB::table('telegram_sessions')->updateOrInsert(
                        ['chat_id' => $chatId],
                        ['step' => 'awaiting_username']
                    );

                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "👋 أهلاً بك في نظام سبارك.\nمن فضلك أرسل اسم المستخدم الخاص بك:",
                    ]);
                    return response('ok', 200);
                }

                if ($step === 'awaiting_username') {
                    DB::table('telegram_sessions')->updateOrInsert(
                        ['chat_id' => $chatId],
                        ['step' => 'awaiting_password', 'username' => $text]
                    );
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '🔐 أدخل كلمة المرور الخاصة بك:'
                    ]);
                    return response('ok', 200);
                }

                if ($step === 'awaiting_password') {
                    $session = DB::table('telegram_sessions')->where('chat_id', $chatId)->first();
                    $username = $session->username;
                    $password = $text;

                    // تحقق من المستخدم في API الشركة
                    $response = Http::post(env('SPARK_API_URL') . '/auth/login', [
                        'username' => $username,
                        'password' => $password,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();

                        // حفظ المستخدم في قاعدة البيانات المحلية
                        DB::table('telegram_users')->insert([
                            'chat_id' => $chatId,
                            'username' => $username,
                            'token' => $data['token'],
                        ]);

                        // حذف الجلسة
                        DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();

                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "✅ تم تسجيل الدخول بنجاح!\nاختر من القائمة التالية:",
                            'reply_markup' => json_encode([
                                'keyboard' => [["💰 تعبئة الرصيد", "🔄 تجديد الباقة"], ["📦 عرض الباقات", "🛠 الدعم الفني"], ["🚪 تسجيل الخروج"]],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => false
                            ])
                        ]);
                    } else {
                        $telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => '❌ اسم المستخدم أو كلمة المرور غير صحيحة، حاول مرة أخرى.'
                        ]);
                        DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();
                    }
                    return response('ok', 200);
                }
            }

            // المستخدم موجود بالفعل → عرض القائمة
            switch ($text) {
                case '💰 تعبئة الرصيد':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '🔢 أدخل رقم البطاقة أو رمز التعبئة:'
                    ]);
                    break;

                case '🔄 تجديد الباقة':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '♻️ جارٍ تجديد الباقة الخاصة بك...'
                    ]);
                    break;

                case '📦 عرض الباقات':
                    $user = DB::table('telegram_users')->where('chat_id', $chatId)->first();
                    $response = Http::withToken($user->token)->get(env('SPARK_API_URL') . '/packages');

                    if ($response->successful()) {
                        $packages = $response->json();
                        $msg = "📦 الباقات المتاحة:\n";
                        foreach ($packages as $pkg) {
                            $msg .= "- {$pkg['name']} ({$pkg['price']} ريال)\n";
                        }
                        $telegram->sendMessage(['chat_id' => $chatId, 'text' => $msg]);
                    } else {
                        $telegram->sendMessage(['chat_id' => $chatId, 'text' => '⚠️ تعذر جلب الباقات من السيرفر.']);
                    }
                    break;

                case '🛠 الدعم الفني':
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "📞 مرحباً بك في الدعم الفني، اختر مشكلتك:\n- لماذا الإنترنت مقطوع؟\n- لماذا السرعة ضعيفة؟\n- مشكلة أخرى",
                    ]);
                    break;

                case '🚪 تسجيل الخروج':
                    DB::table('telegram_users')->where('chat_id', $chatId)->delete();
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '👋 تم تسجيل الخروج بنجاح. أرسل /start لتسجيل الدخول من جديد.'
                    ]);
                    break;

                default:
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '📨 استخدم الأزرار المتاحة في الأسفل لاختيار الخدمة.'
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Telegram Bot Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return response('ok', 200);
    }
}
