<?php

namespace App\Services;

use App\Models\WhatsAppInstance;
use Illuminate\Support\Facades\Log;

/**
 * مثال على خدمة بوت رد آلي بسيط
 * 
 * يمكنك توسيع هذه الخدمة لإضافة المزيد من الميزات:
 * - الرد على الأسئلة الشائعة
 * - نظام القوائم التفاعلية
 * - حجز المواعيد
 * - معالجة الطلبات
 * - إلخ...
 */
class AutoReplyBotService
{
    protected $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
    }

    /**
     * معالجة الرسالة الواردة
     */
    public function handleIncomingMessage($instance, $message)
    {
        try {
            // استخراج بيانات الرسالة
            $remoteJid = $message['key']['remoteJid'] ?? null;
            $fromMe = $message['key']['fromMe'] ?? false;

            // تجاهل الرسائل المرسلة من البوت نفسه
            if ($fromMe) {
                return;
            }

            // استخراج نص الرسالة
            $messageText = $this->extractMessageText($message);

            if (!$remoteJid || !$messageText) {
                return;
            }

            // تسجيل الرسالة
            Log::info('Bot received message', [
                'instance' => $instance->instance_name,
                'from' => $remoteJid,
                'text' => $messageText
            ]);

            // إرسال حالة "يكتب..."
            $this->evolutionService->sendChatPresence(
                $instance->instance_name,
                $remoteJid,
                'composing',
                1000
            );

            // معالجة الرسالة بناءً على المحتوى
            $this->processMessage($instance, $remoteJid, $messageText, $message);
        } catch (\Exception $e) {
            Log::error('Error in AutoReplyBot', [
                'error' => $e->getMessage(),
                'instance' => $instance->instance_name ?? 'unknown'
            ]);
        }
    }

    /**
     * استخراج نص الرسالة من أنواع مختلفة
     */
    protected function extractMessageText($message)
    {
        $msg = $message['message'] ?? [];

        // رسالة نصية عادية
        if (isset($msg['conversation'])) {
            return $msg['conversation'];
        }

        // رسالة نصية موسعة
        if (isset($msg['extendedTextMessage']['text'])) {
            return $msg['extendedTextMessage']['text'];
        }

        // رسالة من زر
        if (isset($msg['buttonsResponseMessage']['selectedButtonId'])) {
            return $msg['buttonsResponseMessage']['selectedButtonId'];
        }

        // رسالة من قائمة
        if (isset($msg['listResponseMessage']['singleSelectReply']['selectedRowId'])) {
            return $msg['listResponseMessage']['singleSelectReply']['selectedRowId'];
        }

        return null;
    }

    /**
     * معالجة الرسالة وإرسال الرد المناسب
     */
    protected function processMessage($instance, $remoteJid, $messageText, $originalMessage)
    {
        $messageText = trim(mb_strtolower($messageText));

        // رسالة الترحيب
        if (in_array($messageText, ['مرحبا', 'السلام عليكم', 'هلا', 'hi', 'hello', 'start'])) {
            $this->sendWelcomeMessage($instance, $remoteJid);
            return;
        }

        // القائمة الرئيسية
        if (in_array($messageText, ['القائمة', 'menu', 'main_menu'])) {
            $this->sendMainMenu($instance, $remoteJid);
            return;
        }

        // معلومات عن الخدمات
        if (in_array($messageText, ['الخدمات', 'services', 'service_info'])) {
            $this->sendServicesInfo($instance, $remoteJid);
            return;
        }

        // الأسعار
        if (in_array($messageText, ['الأسعار', 'prices', 'pricing'])) {
            $this->sendPricingInfo($instance, $remoteJid);
            return;
        }

        // التواصل
        if (in_array($messageText, ['تواصل', 'contact', 'support'])) {
            $this->sendContactInfo($instance, $remoteJid);
            return;
        }

        // معالجة اختيارات الباقات
        if (in_array($messageText, ['basic_package', 'advanced_package', 'pro_package'])) {
            $this->handlePackageSelection($instance, $remoteJid, $messageText);
            return;
        }

        // رد افتراضي
        $this->sendDefaultResponse($instance, $remoteJid);
    }

    /**
     * إرسال رسالة الترحيب
     */
    protected function sendWelcomeMessage($instance, $remoteJid)
    {
        $this->evolutionService->sendQuickReply(
            $instance->instance_name,
            $remoteJid,
            "🌟 *مرحباً بك في بوت الدعم الآلي!*\n\n" .
                "نحن هنا لمساعدتك على مدار الساعة.\n" .
                "اختر أحد الخيارات التالية:",
            [
                ['text' => '📋 القائمة الرئيسية', 'id' => 'main_menu'],
                ['text' => '💼 خدماتنا', 'id' => 'service_info'],
                ['text' => '💰 الأسعار', 'id' => 'pricing'],
                ['text' => '📞 تواصل معنا', 'id' => 'support'],
            ]
        );
    }

    /**
     * إرسال القائمة الرئيسية
     */
    protected function sendMainMenu($instance, $remoteJid)
    {
        $this->evolutionService->sendList(
            $instance->instance_name,
            $remoteJid,
            '📋 القائمة الرئيسية',
            'اختر القسم الذي تريد الاستفسار عنه:',
            'عرض الخيارات',
            [
                [
                    'title' => '📌 معلومات عامة',
                    'rows' => [
                        [
                            'title' => 'عن الشركة',
                            'description' => 'معلومات عن شركتنا وخدماتنا',
                            'rowId' => 'about_us'
                        ],
                        [
                            'title' => 'ساعات العمل',
                            'description' => 'أوقات عمل فريق الدعم',
                            'rowId' => 'working_hours'
                        ],
                    ]
                ],
                [
                    'title' => '💼 الخدمات',
                    'rows' => [
                        [
                            'title' => 'خدماتنا',
                            'description' => 'تعرف على جميع خدماتنا',
                            'rowId' => 'service_info'
                        ],
                        [
                            'title' => 'الأسعار',
                            'description' => 'باقات الأسعار المتاحة',
                            'rowId' => 'pricing'
                        ],
                    ]
                ],
                [
                    'title' => '📞 الدعم',
                    'rows' => [
                        [
                            'title' => 'تواصل معنا',
                            'description' => 'طرق التواصل مع فريق الدعم',
                            'rowId' => 'support'
                        ],
                        [
                            'title' => 'الأسئلة الشائعة',
                            'description' => 'إجابات على الأسئلة الشائعة',
                            'rowId' => 'faq'
                        ],
                    ]
                ],
            ]
        );
    }

    /**
     * إرسال معلومات الخدمات
     */
    protected function sendServicesInfo($instance, $remoteJid)
    {
        $message = "💼 *خدماتنا المتميزة*\n\n" .
            "نقدم مجموعة متنوعة من الخدمات:\n\n" .
            "✅ *تطوير تطبيقات الويب*\n" .
            "   - تطبيقات حديثة وسريعة\n" .
            "   - تصميم متجاوب\n\n" .
            "✅ *بوتات WhatsApp*\n" .
            "   - ردود آلية ذكية\n" .
            "   - تكامل مع الأنظمة\n\n" .
            "✅ *الدعم الفني*\n" .
            "   - دعم على مدار الساعة\n" .
            "   - صيانة دورية\n\n" .
            "للمزيد من التفاصيل، اختر:";

        $this->evolutionService->sendQuickReply(
            $instance->instance_name,
            $remoteJid,
            $message,
            [
                ['text' => '💰 عرض الأسعار', 'id' => 'pricing'],
                ['text' => '📞 تواصل معنا', 'id' => 'support'],
                ['text' => '🔙 القائمة الرئيسية', 'id' => 'main_menu'],
            ]
        );
    }

    /**
     * إرسال معلومات الأسعار
     */
    protected function sendPricingInfo($instance, $remoteJid)
    {
        $this->evolutionService->sendList(
            $instance->instance_name,
            $remoteJid,
            '💰 باقات الأسعار',
            'اختر الباقة المناسبة لك:',
            'عرض الباقات',
            [
                [
                    'title' => '📦 الباقات المتاحة',
                    'rows' => [
                        [
                            'title' => '⭐ الباقة الأساسية',
                            'description' => '50 دينار/شهر - مناسبة للشركات الصغيرة',
                            'rowId' => 'basic_package'
                        ],
                        [
                            'title' => '⭐⭐ الباقة المتقدمة',
                            'description' => '100 دينار/شهر - مناسبة للشركات المتوسطة',
                            'rowId' => 'advanced_package'
                        ],
                        [
                            'title' => '⭐⭐⭐ الباقة الاحترافية',
                            'description' => '200 دينار/شهر - مناسبة للشركات الكبيرة',
                            'rowId' => 'pro_package'
                        ],
                    ]
                ],
            ]
        );
    }

    /**
     * معالجة اختيار الباقة
     */
    protected function handlePackageSelection($instance, $remoteJid, $packageId)
    {
        $packages = [
            'basic_package' => [
                'name' => 'الباقة الأساسية',
                'price' => '50 دينار/شهر',
                'features' => [
                    '✅ 1000 رسالة شهرياً',
                    '✅ دعم فني أساسي',
                    '✅ تقارير شهرية',
                ]
            ],
            'advanced_package' => [
                'name' => 'الباقة المتقدمة',
                'price' => '100 دينار/شهر',
                'features' => [
                    '✅ 5000 رسالة شهرياً',
                    '✅ دعم فني متقدم',
                    '✅ تقارير أسبوعية',
                    '✅ تكامل مع API',
                ]
            ],
            'pro_package' => [
                'name' => 'الباقة الاحترافية',
                'price' => '200 دينار/شهر',
                'features' => [
                    '✅ رسائل غير محدودة',
                    '✅ دعم فني على مدار الساعة',
                    '✅ تقارير يومية',
                    '✅ تكامل كامل مع الأنظمة',
                    '✅ مدير حساب مخصص',
                ]
            ],
        ];

        $package = $packages[$packageId] ?? null;

        if (!$package) {
            $this->sendDefaultResponse($instance, $remoteJid);
            return;
        }

        $message = "📦 *{$package['name']}*\n\n" .
            "💰 السعر: {$package['price']}\n\n" .
            "*المميزات:*\n" .
            implode("\n", $package['features']) . "\n\n" .
            "هل تريد الاشتراك في هذه الباقة؟";

        $this->evolutionService->sendQuickReply(
            $instance->instance_name,
            $remoteJid,
            $message,
            [
                ['text' => '✅ نعم، أريد الاشتراك', 'id' => 'subscribe_' . $packageId],
                ['text' => '📋 عرض باقات أخرى', 'id' => 'pricing'],
                ['text' => '📞 تواصل مع المبيعات', 'id' => 'support'],
            ]
        );
    }

    /**
     * إرسال معلومات التواصل
     */
    protected function sendContactInfo($instance, $remoteJid)
    {
        $message = "📞 *تواصل معنا*\n\n" .
            "يسعدنا التواصل معك عبر:\n\n" .
            "📧 البريد الإلكتروني:\n" .
            "support@example.com\n\n" .
            "📱 الهاتف:\n" .
            "+218 91 234 5678\n\n" .
            "⏰ ساعات العمل:\n" .
            "السبت - الخميس: 9 صباحاً - 5 مساءً\n" .
            "الجمعة: مغلق\n\n" .
            "سيتم تحويلك إلى أحد ممثلي خدمة العملاء...";

        $this->evolutionService->sendText(
            $instance->instance_name,
            $remoteJid,
            $message
        );

        // TODO: يمكنك هنا إضافة منطق لتحويل المحادثة إلى موظف حقيقي
        // أو إرسال إشعار للفريق
    }

    /**
     * إرسال رد افتراضي
     */
    protected function sendDefaultResponse($instance, $remoteJid)
    {
        $this->evolutionService->sendQuickReply(
            $instance->instance_name,
            $remoteJid,
            "عذراً، لم أفهم طلبك. 🤔\n\n" .
                "يمكنك اختيار أحد الخيارات التالية:",
            [
                ['text' => '📋 القائمة الرئيسية', 'id' => 'main_menu'],
                ['text' => '📞 تواصل مع الدعم', 'id' => 'support'],
            ]
        );
    }
}
