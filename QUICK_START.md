# 🚀 دليل البدء السريع - Evolution WhatsApp Bot

## الخطوة 1️⃣: الإعداد الأولي

### 1. تحديث ملف `.env`

```env
EVOLUTION_BASE_URL=https://your-evolution-api.com
EVOLUTION_API_KEY=your-api-key
APP_URL=https://your-domain.com
```

### 2. إضافة الإعدادات في `config/services.php`

```php
'evolution' => [
    'base_url' => env('EVOLUTION_BASE_URL'),
    'api_key' => env('EVOLUTION_API_KEY'),
],
```

---

## الخطوة 2️⃣: إنشاء Instance

### باستخدام API:

```bash
curl -X POST https://your-domain.com/api/whatsapp/instances \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_name": "my-support-bot",
    "integration": "WHATSAPP-BAILEYS"
  }'
```

### الرد المتوقع:

```json
{
    "message": "تم إنشاء الـ instance بنجاح",
    "instance": {
        "id": 1,
        "instance_name": "my-support-bot",
        "status": "qr_code"
    },
    "qr_code": "data:image/png;base64,..."
}
```

---

## الخطوة 3️⃣: مسح QR Code

### احصل على QR Code:

```bash
curl -X GET https://your-domain.com/api/whatsapp/instances/my-support-bot/qrcode \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### امسح الـ QR Code باستخدام WhatsApp:

1. افتح WhatsApp على هاتفك
2. اذهب إلى الإعدادات → الأجهزة المرتبطة
3. امسح الـ QR Code المعروض

---

## الخطوة 4️⃣: التحقق من الاتصال

```bash
curl -X GET https://your-domain.com/api/whatsapp/instances/my-support-bot/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### الرد عند الاتصال الناجح:

```json
{
    "state": "open",
    "statusReason": 200
}
```

---

## الخطوة 5️⃣: إرسال أول رسالة! 🎉

### رسالة نصية بسيطة:

```bash
curl -X POST https://your-domain.com/api/whatsapp/instances/my-support-bot/messages/text \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "218912345678",
    "text": "مرحباً! هذه أول رسالة من البوت 🤖"
  }'
```

### رسالة مع أزرار:

```bash
curl -X POST https://your-domain.com/api/whatsapp/instances/my-support-bot/messages/buttons \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "218912345678",
    "description": "اختر أحد الخيارات:",
    "buttons": [
      {"text": "الخيار 1", "id": "opt1"},
      {"text": "الخيار 2", "id": "opt2"},
      {"text": "الخيار 3", "id": "opt3"}
    ]
  }'
```

### رسالة قائمة:

```bash
curl -X POST https://your-domain.com/api/whatsapp/instances/my-support-bot/messages/list \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "218912345678",
    "title": "القائمة الرئيسية",
    "description": "اختر من القائمة:",
    "button_text": "اضغط هنا",
    "sections": [
      {
        "title": "الخدمات",
        "rows": [
          {
            "title": "خدمة 1",
            "description": "وصف الخدمة 1",
            "rowId": "service1"
          },
          {
            "title": "خدمة 2",
            "description": "وصف الخدمة 2",
            "rowId": "service2"
          }
        ]
      }
    ]
  }'
```

---

## الخطوة 6️⃣: تفعيل البوت الآلي

البوت الآلي يعمل تلقائياً! 🎊

عند استقبال أي رسالة، سيقوم `AutoReplyBotService` بمعالجتها والرد عليها.

### جرب إرسال:

-   `مرحبا` → سيرد برسالة ترحيب
-   `القائمة` → سيعرض القائمة الرئيسية
-   `الأسعار` → سيعرض الباقات
-   `تواصل` → سيعرض معلومات التواصل

---

## 🎯 أمثلة استخدام من الكود

### مثال 1: إرسال رسالة نصية

```php
use App\Services\EvolutionService;

$service = new EvolutionService();
$result = $service->sendText(
    'my-support-bot',
    '218912345678',
    'مرحباً من Laravel! 👋'
);

if ($result['success']) {
    echo "تم الإرسال بنجاح!";
}
```

### مثال 2: إرسال صورة

```php
$result = $service->sendMedia(
    'my-support-bot',
    '218912345678',
    'https://example.com/image.jpg',
    'image',
    ['caption' => 'شاهد هذه الصورة الرائعة!']
);
```

### مثال 3: إنشاء مجموعة

```php
$result = $service->createGroup(
    'my-support-bot',
    'مجموعة الدعم الفني',
    [
        '218912345678@s.whatsapp.net',
        '218987654321@s.whatsapp.net'
    ]
);
```

### مثال 4: إرسال أزرار سريعة

```php
$result = $service->sendQuickReply(
    'my-support-bot',
    '218912345678',
    'كيف يمكنني مساعدتك؟',
    [
        ['text' => 'استفسار عام', 'id' => 'general'],
        ['text' => 'دعم فني', 'id' => 'support'],
        ['text' => 'المبيعات', 'id' => 'sales']
    ]
);
```

---

## 🔧 تخصيص البوت

### تعديل الردود في `AutoReplyBotService.php`:

```php
protected function processMessage($instance, $remoteJid, $messageText, $originalMessage)
{
    $messageText = trim(mb_strtolower($messageText));

    // أضف كلماتك المفتاحية الخاصة
    if (stripos($messageText, 'حجز موعد') !== false) {
        $this->sendAppointmentOptions($instance, $remoteJid);
        return;
    }

    // رد افتراضي
    $this->sendDefaultResponse($instance, $remoteJid);
}

// أضف دالتك الخاصة
protected function sendAppointmentOptions($instance, $remoteJid)
{
    $this->evolutionService->sendList(
        $instance->instance_name,
        $remoteJid,
        'حجز موعد',
        'اختر الوقت المناسب:',
        'عرض المواعيد',
        [
            [
                'title' => 'المواعيد المتاحة',
                'rows' => [
                    [
                        'title' => 'الأحد 10 صباحاً',
                        'description' => 'موعد متاح',
                        'rowId' => 'book_sunday_10'
                    ],
                    // ... المزيد من المواعيد
                ]
            ]
        ]
    );
}
```

---

## 📊 مراقبة الأداء

### فحص السجلات:

```bash
tail -f storage/logs/laravel.log | grep "Evolution"
```

### السجلات المهمة:

-   `Evolution Webhook Received` - استقبال webhook
-   `Processing Message` - معالجة رسالة
-   `Bot received message` - البوت استقبل رسالة
-   `Evolution API Error` - خطأ في API

---

## ⚠️ استكشاف الأخطاء

### المشكلة: QR Code لا يعمل

**الحل:**

```bash
# أعد الاتصال
curl -X GET https://your-domain.com/api/whatsapp/instances/my-support-bot/qrcode
```

### المشكلة: البوت لا يرد

**الحل:**

1. تحقق من حالة الاتصال
2. راجع السجلات
3. تأكد من إعدادات webhook

### المشكلة: خطأ في الإرسال

**الحل:**

```php
// تحقق من الرد
if (!$result['success']) {
    Log::error('Send Error', $result);
}
```

---

## 🎉 تهانينا!

الآن لديك بوت WhatsApp يعمل بكامل طاقته! 🚀

### الخطوات التالية:

1. ✅ خصص الردود حسب احتياجاتك
2. ✅ أضف المزيد من الميزات
3. ✅ ادمج مع قاعدة البيانات
4. ✅ أضف تحليلات وإحصائيات
5. ✅ استمتع بالبوت! 🎊

---

## 📚 مراجع مفيدة

-   `SUMMARY.md` - ملخص شامل للمشروع
-   `EVOLUTION_SERVICE_README.md` - دليل الخدمة الكامل
-   `app/Services/AutoReplyBotService.php` - كود البوت
-   `app/Services/EvolutionService.php` - الخدمة الرئيسية

**بالتوفيق! 💪**
