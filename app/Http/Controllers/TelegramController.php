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

    // خطوات الجلسة لحالة المستخدم غير المسجل (التسجيل)
    private const STEP_AWAITING_USERNAME = 'awaiting_username';
    private const STEP_AWAITING_PASSWORD = 'awaiting_password';

    // خطوات الجلسة لحالة المستخدم المسجل (الخدمات)
    private const STEP_MAIN_MENU = 'main_menu';
    private const STEP_AWAITING_RECHARGE_CARD = 'awaiting_recharge_card';
    private const STEP_AWAITING_SUPPORT_REPLY = 'awaiting_support_reply';

    protected $telegram;
    protected $isp;

    public function __construct(ISPService $isp)
    {
        // تهيئة واجهة تيليجرام
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        // تهيئة خدمة مزود الإنترنت (ISPService)
        $this->isp = $isp;
    }

    /**
     * معالج الـ Webhook الخاص بتيليجرام.
     */
    public function handle(Request $request)
    {
        $update = $this->telegram->getWebhookUpdate();

        // // يجب أن تحتوي التحديثات على رسالة لكي يتم معالجتها
        // if (!$update->hasMessage()) {
        //     return response('ok', 200);
        // }

        try {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = trim($message->getText());

            // البحث عن المستخدم المسجل
            $user = DB::table('telegram_users')->where('chat_id', $chatId)->first();
            // البحث عن جلسة جارية (لتتبع خطوات التسجيل أو الخدمات)
            $session = DB::table('telegram_sessions')->where('chat_id', $chatId)->first();
            $step = $session ? $session->step : null;

            // إذا كان المستخدم غير مسجل، يتم الدخول في تدفق التسجيل
            if (!$user) {
                $this->handleRegistrationFlow($chatId, $text, $step);
            }
            // إذا كان المستخدم مسجلاً، يتم معالجة أوامر الخدمات
            else {
                $this->handleLoggedInFlow($chatId, $user, $text, $step);
            }
        } catch (\Exception $e) {
            Log::error('Telegram Bot Global Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // إرسال رسالة خطأ عامة للمستخدم
            if (isset($chatId)) {
                $this->sendMessage($chatId, '❌ حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.');
            }
        }

        return response('ok', 200);
    }

    /**
     * يدير تدفق تسجيل الدخول للمستخدمين الجدد.
     */
    protected function handleRegistrationFlow(int $chatId, string $text, ?string $step)
    {
        // معالجة الأمر /start لإعادة تشغيل التدفق
        if ($text === '/start' || $text === '🚪 تسجيل الخروج') {
            DB::table('telegram_sessions')->updateOrInsert(
                ['chat_id' => $chatId],
                ['step' => self::STEP_AWAITING_USERNAME]
            );

            // إرسال ستيكر ترحيب
            $this->sendMessage($chatId, "👋 أهلاً بك في نظام سبارك الذكي.\nمن فضلك أرسل اسم المستخدم الخاص بك:");
            return;
        }

        // البداية: طلب اسم المستخدم
        if (!$step || $step === self::STEP_MAIN_MENU) { // إذا لم يكن هناك خطوة، نبدأ
            DB::table('telegram_sessions')->updateOrInsert(
                ['chat_id' => $chatId],
                ['step' => self::STEP_AWAITING_USERNAME]
            );
            $this->sendMessage($chatId, "👋 أهلاً بك في نظام سبارك الذكي.\nمن فضلك أرسل اسم المستخدم الخاص بك:");
            return;
        }

        // 1. بعد إدخال اسم المستخدم
        if ($step === self::STEP_AWAITING_USERNAME) {
            // تخزين اسم المستخدم والانتقال لخطوة كلمة المرور
            DB::table('telegram_sessions')->updateOrInsert(
                ['chat_id' => $chatId],
                ['step' => self::STEP_AWAITING_PASSWORD, 'username' => $text]
            );
            $this->sendMessage($chatId, '🔐 أدخل كلمة المرور الخاصة بك:');
            return;
        }

        // 2. بعد إدخال كلمة المرور → التحقق من API
        if ($step === self::STEP_AWAITING_PASSWORD) {
            $session = DB::table('telegram_sessions')->where('chat_id', $chatId)->first();
            $username = $session->username ?? '';
            $password = $text;

            // إرسال رسالة "جاري التحقق..."
            $this->telegram->sendChatAction(['chat_id' => $chatId, 'action' => 'typing']);
            $this->sendMessage($chatId, '⌛️ جاري التحقق من بيانات الدخول...');

            // محاكاة الاتصال بواجهة برمجة التطبيقات
            $response = Http::post('https://restsp.sparktech.ly/api/auth/login', [
                'username' => $username,
                'password' => $password,
                'firebase_token' => 'telegram-bot-auth', // قيمة ثابتة للتيليجرام
            ]);

            if ($response->successful() && isset($response->json()['data']['api_key'])) {
                $data = $response->json()['data'];

                // تسجيل المستخدم وإلغاء الجلسة
                DB::table('telegram_users')->insert([
                    'chat_id' => $chatId,
                    'username' => $username,
                    'token' => $data['api_key'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();

                // إرسال ستيكر نجاح ورسالة الترحيب مع لوحة المفاتيح
                $this->sendMenu($chatId, "✅ تم تسجيل الدخول بنجاح! تفضل باختيار الخدمة:");
            } else {
                // فشل تسجيل الدخول
                $this->sendMessage($chatId, '❌ اسم المستخدم أو كلمة المرور غير صحيحة، يرجى إرسال /start والمحاولة مرة أخرى.');
                // حذف الجلسة لإعادة البداية
                DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();
            }
            return;
        }
    }

    /**
     * يدير تدفق المستخدمين المسجلين (قائمة الخدمات).
     */
    protected function handleLoggedInFlow(int $chatId, object $user, string $text, ?string $step)
    {
        // 1. إذا كان هناك خطوة جلسة نشطة تتطلب إدخالاً (مثل تعبئة الرصيد)
        if ($step && $step !== self::STEP_MAIN_MENU) {
            $this->handleSessionReply($chatId, $user, $text, $step);
            return;
        }

        // 2. معالجة أوامر القائمة الرئيسية (إذا لم تكن هناك جلسة نشطة)
        switch ($text) {
            case '/start':
                // إعادة عرض القائمة الرئيسية
                $this->sendMenu($chatId, 'مرحباً بعودتك، تفضل باختيار الخدمة:');
                break;

            case '💰 تعبئة الرصيد':
                // تعيين خطوة الجلسة لانتظار إدخال رقم البطاقة
                DB::table('telegram_sessions')->updateOrInsert(
                    ['chat_id' => $chatId],
                    ['step' => self::STEP_AWAITING_RECHARGE_CARD]
                );
                $this->sendMessage($chatId, '🔢 أدخل رقم البطاقة أو رمز التعبئة الخاص بك:');
                break;

            case '🔄 تجديد الباقة':
                $this->handlePackageRenewal($chatId, $user);
                break;

            case '📦 عرض الباقات':
                $this->handleViewPackages($chatId, $user);
                break;

            case '🛠 الدعم الفني':
                // توجيه المستخدم لمحادثة الدعم
                $this->sendMessage($chatId, "📞 مرحباً بك في الدعم الفني. يرجى إرسال رسالتك أو شرح المشكلة التي تواجهها بالتفصيل.");
                // تعيين خطوة الجلسة لانتظار رسالة الدعم
                DB::table('telegram_sessions')->updateOrInsert(
                    ['chat_id' => $chatId],
                    ['step' => self::STEP_AWAITING_SUPPORT_REPLY]
                );
                break;

            case '🚪 تسجيل الخروج':
                DB::table('telegram_users')->where('chat_id', $chatId)->delete();
                DB::table('telegram_sessions')->where('chat_id', $chatId)->delete(); // تأكيد الحذف
                $this->sendMessage($chatId, '👋 تم تسجيل الخروج بنجاح. أرسل /start لتسجيل الدخول من جديد.');
                break;

            default:
                // رسالة عامة للرد على أي نص غير متوقع
                $this->sendMessage($chatId, '📨 يرجى استخدام الأزرار المتاحة في الأسفل لاختيار الخدمة. إذا لم تظهر، أرسل /start.');
        }
    }

    /**
     * يدير الردود عندما يكون المستخدم في خطوة جلسة معينة (بعد تسجيل الدخول).
     */
    protected function handleSessionReply(int $chatId, object $user, string $text, string $step)
    {
        switch ($step) {
            case self::STEP_AWAITING_RECHARGE_CARD:
                $this->handleRechargeCard($chatId, $user, $text);
                break;

            case self::STEP_AWAITING_SUPPORT_REPLY:
                $this->handleSupportReply($chatId, $user, $text);
                break;

            default:
                // في حالة وجود خطوة غير معروفة، نعود إلى القائمة الرئيسية
                DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();
                $this->sendMessage($chatId, '❗️ تم إنهاء العملية السابقة. يرجى اختيار خدمة جديدة من القائمة.');
                $this->sendMenu($chatId, 'القائمة الرئيسية:');
        }
    }

    /**
     * معالجة إدخال رقم بطاقة تعبئة الرصيد.
     */
    protected function handleRechargeCard(int $chatId, object $user, string $cardCode)
    {
        // إرسال رسالة جاري المعالجة
        $this->telegram->sendChatAction(['chat_id' => $chatId, 'action' => 'typing']);
        $this->sendMessage($chatId, '⏳ جاري تعبئة الرصيد باستخدام الرمز ' . $cardCode . '...');

        // استخدام ISPService لتعبئة الرصيد
        // نفترض أن الدالة تأخذ الرمز وتوكن المستخدم
        $response = $this->isp->setProfile($user->token, $cardCode);

        if ($response->successful()) {
            $this->sendMessage($chatId, "💰 تهانينا! تم تعبئة رصيدك بنجاح.\nالرصيد الحالي: " . ($response->json()['balance'] ?? 'غير معروف'));
        } else {
            $this->sendMessage($chatId, "⚠️ فشلت عملية التعبئة.\nيرجى التأكد من صحة رقم البطاقة وإعادة المحاولة.");
        }

        // العودة إلى القائمة الرئيسية وحذف الجلسة
        DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();
        $this->sendMenu($chatId, 'تفضل باختيار خدمة أخرى:');
    }

    /**
     * معالجة طلب تجديد الباقة.
     */
    protected function handlePackageRenewal(int $chatId, object $user)
    {
        $this->telegram->sendChatAction(['chat_id' => $chatId, 'action' => 'typing']);
        $this->sendMessage($chatId, '🔄 جاري محاولة تجديد باقتك الحالية...');

        // استخدام ISPService للتجديد
        $response = $this->isp->renew($user->token);

        if ($response->successful()) {
            $msg = "♻️ تم تجديد الباقة بنجاح!\nتاريخ الانتهاء الجديد: " . ($response->json()['expiry_date'] ?? 'غير معروف');
        } else {
            $msg = "⚠️ تعذر تجديد الباقة. قد يكون رصيدك غير كافٍ أو أن هناك خطأ في النظام.\nالرجاء المحاولة لاحقاً أو مراجعة الرصيد.";
        }

        $this->sendMessage($chatId, $msg);
    }

    /**
     * معالجة طلب عرض الباقات.
     */
    protected function handleViewPackages(int $chatId, object $user)
    {
        $this->telegram->sendChatAction(['chat_id' => $chatId, 'action' => 'typing']);
        $this->sendMessage($chatId, '⏳ جاري جلب قائمة الباقات المتاحة...');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $user->token,
        ])->get('https://restsp.sparktech.ly/api/user/packages');

        if ($response->successful() && isset($response->json()['data'])) {
            $packages = $response->json()['data'];
            $msg = "📦 الباقات المتاحة:\n\n";
            if (empty($packages)) {
                $msg = "❗️ لا توجد باقات متاحة حالياً.";
            } else {
                foreach ($packages as $pkg) {
                    $msg .= "• {$pkg['name']} | السعر: {$pkg['price']} ريال | المدة: {$pkg['duration']}\n";
                }
            }
        } else {
            $msg = "⚠️ تعذر جلب الباقات من السيرفر. يرجى التأكد من اتصالك.";
        }

        $this->sendMessage($chatId, $msg);
    }

    /**
     * معالجة رسالة الدعم الفني.
     */
    protected function handleSupportReply(int $chatId, object $user, string $text)
    {
        // هنا يمكنك إرسال الرسالة إلى نظام تذاكر الدعم الخاص بك
        Log::info('New Support Ticket:', [
            'username' => $user->username,
            'chat_id' => $chatId,
            'message' => $text,
        ]);

        // إرسال تأكيد للمستخدم
        $this->sendMessage($chatId, "✅ تم إرسال رسالتك إلى فريق الدعم الفني.\nسيتم التواصل معك في أقرب وقت.");

        // العودة إلى القائمة الرئيسية وحذف الجلسة
        DB::table('telegram_sessions')->where('chat_id', $chatId)->delete();
        $this->sendMenu($chatId, 'تفضل باختيار خدمة أخرى:');
    }

    /**
     * إرسال رسالة نصية بسيطة.
     */
    protected function sendMessage(int $chatId, string $text)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    /**
     * إرسال القائمة الرئيسية ولوحة المفاتيح المخصصة.
     */
    protected function sendMenu(int $chatId, string $text)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
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
    }
}
