<?php

/**
 * أمثلة عملية لاستخدام Evolution Service
 * 
 * هذا الملف يحتوي على أمثلة جاهزة للاستخدام
 * يمكنك نسخها واستخدامها مباشرة في مشروعك
 */

namespace App\Examples;

use App\Services\EvolutionService;
use Illuminate\Support\Facades\Log;

class EvolutionExamples
{
    protected $service;
    protected $instanceName = 'my-bot';

    public function __construct(EvolutionService $service)
    {
        $this->service = $service;
    }

    /**
     * مثال 1: إرسال رسالة ترحيب للعميل الجديد
     */
    public function sendWelcomeToNewCustomer($phoneNumber, $customerName)
    {
        $message = "🌟 مرحباً *{$customerName}*!\n\n" .
            "نشكرك على انضمامك إلينا.\n" .
            "نحن هنا لخدمتك على مدار الساعة.\n\n" .
            "كيف يمكننا مساعدتك اليوم؟";

        return $this->service->sendQuickReply(
            $this->instanceName,
            $phoneNumber,
            $message,
            [
                ['text' => '📋 عرض الخدمات', 'id' => 'services'],
                ['text' => '💰 الأسعار', 'id' => 'pricing'],
                ['text' => '📞 تواصل معنا', 'id' => 'contact'],
            ]
        );
    }

    /**
     * مثال 2: إرسال تأكيد طلب
     */
    public function sendOrderConfirmation($phoneNumber, $orderDetails)
    {
        $message = "✅ *تم استلام طلبك بنجاح!*\n\n" .
            "📦 رقم الطلب: #{$orderDetails['order_id']}\n" .
            "💰 المبلغ الإجمالي: {$orderDetails['total']} دينار\n" .
            "📅 تاريخ التسليم المتوقع: {$orderDetails['delivery_date']}\n\n" .
            "سنقوم بإشعارك عند شحن الطلب.";

        return $this->service->sendText(
            $this->instanceName,
            $phoneNumber,
            $message
        );
    }

    /**
     * مثال 3: إرسال فاتورة مع صورة
     */
    public function sendInvoice($phoneNumber, $invoiceUrl, $invoiceNumber, $amount)
    {
        return $this->service->sendMedia(
            $this->instanceName,
            $phoneNumber,
            $invoiceUrl,
            'document',
            [
                'caption' => "🧾 *فاتورة رقم {$invoiceNumber}*\n\n" .
                    "المبلغ: {$amount} دينار\n" .
                    "شكراً لتعاملك معنا!",
                'fileName' => "invoice_{$invoiceNumber}.pdf"
            ]
        );
    }

    /**
     * مثال 4: إرسال استطلاع رضا العملاء
     */
    public function sendSatisfactionSurvey($phoneNumber)
    {
        return $this->service->sendPoll(
            $this->instanceName,
            $phoneNumber,
            'ما مدى رضاك عن خدماتنا؟',
            [
                '⭐⭐⭐⭐⭐ ممتاز',
                '⭐⭐⭐⭐ جيد جداً',
                '⭐⭐⭐ جيد',
                '⭐⭐ مقبول',
                '⭐ يحتاج تحسين'
            ],
            1
        );
    }

    /**
     * مثال 5: إرسال عرض خاص مع صورة
     */
    public function sendSpecialOffer($phoneNumber, $offerImageUrl)
    {
        // إرسال الصورة أولاً
        $this->service->sendMedia(
            $this->instanceName,
            $phoneNumber,
            $offerImageUrl,
            'image',
            ['caption' => '🎉 *عرض خاص لفترة محدودة!*']
        );

        // ثم إرسال الأزرار
        sleep(1); // انتظار ثانية واحدة

        return $this->service->sendQuickReply(
            $this->instanceName,
            $phoneNumber,
            'هل تريد الاستفادة من هذا العرض؟',
            [
                ['text' => '✅ نعم، أريد الطلب', 'id' => 'order_offer'],
                ['text' => '📋 المزيد من التفاصيل', 'id' => 'offer_details'],
                ['text' => '⏰ تذكيرني لاحقاً', 'id' => 'remind_later'],
            ]
        );
    }

    /**
     * مثال 6: إرسال موقع الفرع
     */
    public function sendBranchLocation($phoneNumber)
    {
        return $this->service->sendLocation(
            $this->instanceName,
            $phoneNumber,
            32.8872, // Latitude
            13.1913, // Longitude (Tripoli, Libya)
            'فرعنا الرئيسي',
            'طرابلس، ليبيا'
        );
    }

    /**
     * مثال 7: إرسال معلومات التواصل
     */
    public function sendContactInfo($phoneNumber)
    {
        return $this->service->sendContact(
            $this->instanceName,
            $phoneNumber,
            [
                [
                    'fullName' => 'خدمة العملاء',
                    'wuid' => '218912345678',
                    'phoneNumber' => '+218 91 234 5678',
                    'organization' => 'شركتنا',
                    'email' => 'support@example.com'
                ]
            ]
        );
    }

    /**
     * مثال 8: إرسال قائمة المنتجات
     */
    public function sendProductCatalog($phoneNumber)
    {
        return $this->service->sendList(
            $this->instanceName,
            $phoneNumber,
            '🛍️ كتالوج المنتجات',
            'اختر الفئة التي تريد تصفحها:',
            'عرض المنتجات',
            [
                [
                    'title' => '📱 الإلكترونيات',
                    'rows' => [
                        [
                            'title' => 'هواتف ذكية',
                            'description' => 'أحدث الهواتف الذكية',
                            'rowId' => 'cat_phones'
                        ],
                        [
                            'title' => 'أجهزة لوحية',
                            'description' => 'تابلت وآيباد',
                            'rowId' => 'cat_tablets'
                        ],
                        [
                            'title' => 'لابتوب',
                            'description' => 'أجهزة كمبيوتر محمولة',
                            'rowId' => 'cat_laptops'
                        ]
                    ]
                ],
                [
                    'title' => '👕 الملابس',
                    'rows' => [
                        [
                            'title' => 'ملابس رجالية',
                            'description' => 'أزياء رجالية عصرية',
                            'rowId' => 'cat_men_clothes'
                        ],
                        [
                            'title' => 'ملابس نسائية',
                            'description' => 'أزياء نسائية راقية',
                            'rowId' => 'cat_women_clothes'
                        ]
                    ]
                ],
                [
                    'title' => '🏠 المنزل والمطبخ',
                    'rows' => [
                        [
                            'title' => 'أدوات المطبخ',
                            'description' => 'أجهزة ومعدات المطبخ',
                            'rowId' => 'cat_kitchen'
                        ],
                        [
                            'title' => 'ديكور المنزل',
                            'description' => 'قطع ديكور مميزة',
                            'rowId' => 'cat_decor'
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * مثال 9: إرسال تذكير بموعد
     */
    public function sendAppointmentReminder($phoneNumber, $appointmentDetails)
    {
        $message = "⏰ *تذكير بموعدك*\n\n" .
            "📅 التاريخ: {$appointmentDetails['date']}\n" .
            "🕐 الوقت: {$appointmentDetails['time']}\n" .
            "📍 المكان: {$appointmentDetails['location']}\n" .
            "👤 مع: {$appointmentDetails['with']}\n\n" .
            "يرجى الحضور قبل الموعد بـ 10 دقائق.";

        return $this->service->sendQuickReply(
            $this->instanceName,
            $phoneNumber,
            $message,
            [
                ['text' => '✅ تأكيد الحضور', 'id' => 'confirm_appointment'],
                ['text' => '📅 إعادة الجدولة', 'id' => 'reschedule'],
                ['text' => '❌ إلغاء الموعد', 'id' => 'cancel_appointment'],
            ]
        );
    }

    /**
     * مثال 10: إرسال حالة الشحن
     */
    public function sendShippingStatus($phoneNumber, $trackingNumber, $status)
    {
        $statusEmoji = [
            'processing' => '⏳',
            'shipped' => '📦',
            'in_transit' => '🚚',
            'out_for_delivery' => '🏃',
            'delivered' => '✅'
        ];

        $statusText = [
            'processing' => 'قيد المعالجة',
            'shipped' => 'تم الشحن',
            'in_transit' => 'في الطريق',
            'out_for_delivery' => 'خارج للتوصيل',
            'delivered' => 'تم التسليم'
        ];

        $message = "{$statusEmoji[$status]} *حالة الشحنة*\n\n" .
            "📋 رقم التتبع: {$trackingNumber}\n" .
            "📍 الحالة: {$statusText[$status]}\n\n";

        if ($status === 'delivered') {
            $message .= "شكراً لتسوقك معنا! 🎉";
        } else {
            $message .= "سنقوم بإشعارك عند أي تحديث.";
        }

        return $this->service->sendText(
            $this->instanceName,
            $phoneNumber,
            $message
        );
    }

    /**
     * مثال 11: إرسال رسالة جماعية (Broadcast)
     */
    public function sendBroadcastMessage($phoneNumbers, $message)
    {
        $results = [];

        foreach ($phoneNumbers as $number) {
            $result = $this->service->sendText(
                $this->instanceName,
                $number,
                $message
            );

            $results[] = [
                'number' => $number,
                'success' => $result['success']
            ];

            // تأخير بسيط لتجنب الحظر
            usleep(500000); // 0.5 ثانية
        }

        return $results;
    }

    /**
     * مثال 12: إرسال كود التحقق OTP
     */
    public function sendOTPCode($phoneNumber, $code)
    {
        $message = "🔐 *كود التحقق*\n\n" .
            "الكود الخاص بك هو: *{$code}*\n\n" .
            "⚠️ لا تشارك هذا الكود مع أي شخص.\n" .
            "صالح لمدة 5 دقائق فقط.";

        return $this->service->sendText(
            $this->instanceName,
            $phoneNumber,
            $message
        );
    }

    /**
     * مثال 13: إرسال إشعار بعرض سعر
     */
    public function sendQuotation($phoneNumber, $items, $total)
    {
        $message = "💼 *عرض السعر*\n\n";

        foreach ($items as $item) {
            $message .= "• {$item['name']}: {$item['price']} دينار\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━\n";
        $message .= "💰 *الإجمالي: {$total} دينار*\n\n";
        $message .= "العرض صالح لمدة 48 ساعة.";

        return $this->service->sendQuickReply(
            $this->instanceName,
            $phoneNumber,
            $message,
            [
                ['text' => '✅ قبول العرض', 'id' => 'accept_quote'],
                ['text' => '💬 التفاوض', 'id' => 'negotiate'],
                ['text' => '❌ رفض العرض', 'id' => 'reject_quote'],
            ]
        );
    }

    /**
     * مثال 14: إرسال تحديث حالة التذكرة
     */
    public function sendTicketUpdate($phoneNumber, $ticketId, $status, $message)
    {
        $statusEmoji = [
            'open' => '🆕',
            'in_progress' => '⏳',
            'resolved' => '✅',
            'closed' => '🔒'
        ];

        $text = "{$statusEmoji[$status]} *تحديث التذكرة #{$ticketId}*\n\n" .
            "الحالة: *{$status}*\n\n" .
            "التفاصيل:\n{$message}";

        return $this->service->sendText(
            $this->instanceName,
            $phoneNumber,
            $text
        );
    }

    /**
     * مثال 15: إرسال قسيمة خصم
     */
    public function sendDiscountCoupon($phoneNumber, $couponCode, $discount, $expiryDate)
    {
        $message = "🎁 *قسيمة خصم خاصة لك!*\n\n" .
            "🏷️ الكود: *{$couponCode}*\n" .
            "💰 الخصم: {$discount}%\n" .
            "📅 صالح حتى: {$expiryDate}\n\n" .
            "استخدم هذا الكود عند الطلب للحصول على الخصم!";

        return $this->service->sendText(
            $this->instanceName,
            $phoneNumber,
            $message
        );
    }

    /**
     * مثال 16: معالجة رد العميل على الأزرار
     */
    public function handleButtonResponse($phoneNumber, $buttonId)
    {
        switch ($buttonId) {
            case 'order_offer':
                return $this->processOrder($phoneNumber);

            case 'offer_details':
                return $this->sendOfferDetails($phoneNumber);

            case 'remind_later':
                return $this->scheduleReminder($phoneNumber);

            default:
                return $this->service->sendText(
                    $this->instanceName,
                    $phoneNumber,
                    'عذراً، لم أفهم اختيارك. يرجى المحاولة مرة أخرى.'
                );
        }
    }

    /**
     * مثال مساعد: معالجة الطلب
     */
    protected function processOrder($phoneNumber)
    {
        return $this->service->sendText(
            $this->instanceName,
            $phoneNumber,
            "✅ رائع! سنبدأ بمعالجة طلبك.\n\n" .
                "يرجى إرسال:\n" .
                "1️⃣ الكمية المطلوبة\n" .
                "2️⃣ عنوان التوصيل"
        );
    }

    /**
     * مثال مساعد: إرسال تفاصيل العرض
     */
    protected function sendOfferDetails($phoneNumber)
    {
        return $this->service->sendText(
            $this->instanceName,
            $phoneNumber,
            "📋 *تفاصيل العرض:*\n\n" .
                "• خصم 30% على جميع المنتجات\n" .
                "• توصيل مجاني\n" .
                "• ضمان سنة\n" .
                "• العرض ساري حتى نهاية الشهر"
        );
    }

    /**
     * مثال مساعد: جدولة تذكير
     */
    protected function scheduleReminder($phoneNumber)
    {
        // هنا يمكنك استخدام Laravel Scheduler أو Queue
        // لإرسال تذكير لاحقاً

        return $this->service->sendText(
            $this->instanceName,
            $phoneNumber,
            "⏰ تم! سنذكرك بالعرض غداً في نفس الوقت."
        );
    }
}
