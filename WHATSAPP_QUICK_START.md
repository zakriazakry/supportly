# 🚀 البدء السريع - نظام WhatsApp

## ⚡ 5 دقائق للبدء!

### الخطوة 1️⃣: تشغيل Migrations

```bash
php artisan migrate
```

### الخطوة 2️⃣: إعداد Environment Variables

أضف في ملف `.env`:

```env
EVOLUTION_API_KEY=your_api_key_here
EVOLUTION_BASE_URL=http://localhost:8080
```

### الخطوة 3️⃣: تسجيل Events

في `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \App\Events\WhatsApp\InstanceConnected::class => [
        \App\Listeners\WhatsApp\LogInstanceConnection::class,
    ],
    \App\Events\WhatsApp\MessageReceived::class => [
        \App\Listeners\WhatsApp\StoreIncomingMessage::class,
    ],
];
```

### الخطوة 4️⃣: إنشاء Instance

استخدم Postman أو أي HTTP client:

```http
POST http://localhost:8000/api/whatsapp/instances
Content-Type: application/json
Authorization: Bearer YOUR_AUTH_TOKEN

{
    "instance_name": "my_first_bot",
    "integration": "WHATSAPP-BAILEYS"
}
```

### الخطوة 5️⃣: مسح QR Code

```http
GET http://localhost:8000/api/whatsapp/instances/my_first_bot/qrcode
Authorization: Bearer YOUR_AUTH_TOKEN
```

ستحصل على QR Code بصيغة Base64. افتحه في المتصفح وامسحه بتطبيق WhatsApp.

### الخطوة 6️⃣: إرسال أول رسالة!

```http
POST http://localhost:8000/api/whatsapp/instances/my_first_bot/messages/text
Content-Type: application/json
Authorization: Bearer YOUR_AUTH_TOKEN

{
    "number": "218912345678",
    "text": "مرحباً! هذه أول رسالة من البوت 🎉"
}
```

## 🎯 الخطوات التالية

### تخصيص البوت الآلي

عدّل `app/Services/AutoReplyBotService.php`:

```php
protected function processMessage($instance, $remoteJid, $messageText, $originalMessage)
{
    $messageText = trim(mb_strtolower($messageText));

    // أضف ردودك المخصصة هنا
    if ($messageText === 'السلام عليكم') {
        $this->evolutionService->sendText(
            $instance->instance_name,
            $remoteJid,
            'وعليكم السلام ورحمة الله وبركاته! 👋'
        );
        return;
    }

    // الرد الافتراضي
    $this->sendDefaultResponse($instance, $remoteJid);
}
```

### استخدام Queue للأداء الأفضل

```bash
# تشغيل Queue Worker
php artisan queue:work
```

```php
// في الكود
use App\Jobs\WhatsApp\SendWhatsAppMessage;

SendWhatsAppMessage::dispatch($instance, $number, $message);
```

### إضافة Listener مخصص

```php
namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageReceived;

class MyCustomListener
{
    public function handle(MessageReceived $event): void
    {
        // منطقك المخصص هنا
        $instance = $event->instance;
        $messageData = $event->messageData;

        // مثال: إرسال إشعار للمدير
        // مثال: حفظ في نظام CRM
        // إلخ...
    }
}
```

سجّله في `EventServiceProvider`:

```php
\App\Events\WhatsApp\MessageReceived::class => [
    \App\Listeners\WhatsApp\StoreIncomingMessage::class,
    \App\Listeners\WhatsApp\MyCustomListener::class, // الجديد
],
```

## 📚 موارد إضافية

-   📖 [الدليل الكامل](WHATSAPP_GUIDE.md)
-   📝 [سجل التغييرات](WHATSAPP_CHANGELOG.md)
-   🔧 [Evolution API Docs](EVOLUTION_API_GUIDE.md)

## ❓ مشاكل شائعة

### المشكلة: QR Code لا يظهر

**الحل**: تأكد من:

-   Evolution API يعمل بشكل صحيح
-   الـ API Key صحيح
-   الـ Instance تم إنشاؤه بنجاح

### المشكلة: الرسائل لا ترسل

**الحل**: تأكد من:

-   Instance متصل (status = 'connected')
-   رقم الهاتف بالصيغة الصحيحة
-   تحقق من الـ logs: `tail -f storage/logs/laravel.log`

### المشكلة: Webhook لا يعمل

**الحل**: تأكد من:

-   URL الـ webhook متاح للوصول من Evolution API
-   تم تسجيل الـ webhook بشكل صحيح
-   تحقق من الـ logs

## 🎉 مبروك!

الآن لديك نظام WhatsApp متكامل وجاهز للاستخدام!

للدعم: support@example.com
