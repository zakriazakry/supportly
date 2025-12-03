# 📱 نظام WhatsApp المتكامل - دليل الاستخدام

## 📋 المحتويات

-   [نظرة عامة](#نظرة-عامة)
-   [المتطلبات](#المتطلبات)
-   [التثبيت](#التثبيت)
-   [الإعدادات](#الإعدادات)
-   [الاستخدام](#الاستخدام)
-   [API Endpoints](#api-endpoints)
-   [Events & Listeners](#events--listeners)
-   [Jobs](#jobs)
-   [Models](#models)
-   [أمثلة الاستخدام](#أمثلة-الاستخدام)

## 🎯 نظرة عامة

نظام متكامل لإدارة WhatsApp Business API باستخدام Evolution API. يوفر النظام:

-   ✅ إدارة متعددة لـ WhatsApp Instances
-   ✅ إرسال واستقبال الرسائل (نصوص، صور، فيديو، مستندات)
-   ✅ إدارة جهات الاتصال والمحادثات
-   ✅ بوت رد آلي ذكي
-   ✅ تخزين الرسائل في قاعدة البيانات
-   ✅ Events & Listeners للتكامل السهل
-   ✅ Jobs للمهام الخلفية
-   ✅ Webhooks لاستقبال التحديثات الفورية

## 📦 المتطلبات

-   PHP 8.1+
-   Laravel 10+
-   Evolution API Server
-   MySQL/PostgreSQL
-   Redis (اختياري للـ Queue)

## 🚀 التثبيت

### 1. تشغيل Migrations

```bash
php artisan migrate
```

هذا سينشئ الجداول التالية:

-   `whats_app_instances` - لتخزين معلومات الـ Instances
-   `whatsapp_messages` - لتخزين الرسائل
-   `whatsapp_contacts` - لتخزين جهات الاتصال
-   `whatsapp_chats` - لتخزين المحادثات

### 2. تسجيل Events & Listeners

أضف في `app/Providers/EventServiceProvider.php`:

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

### 3. تسجيل Middleware

أضف في `bootstrap/app.php` أو `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ...
    'whatsapp.instance' => \App\Http\Middleware\CheckWhatsAppInstance::class,
];
```

## ⚙️ الإعدادات

### ملف `.env`

```env
EVOLUTION_API_KEY=your_api_key_here
EVOLUTION_BASE_URL=http://localhost:8080
```

### ملف `config/services.php`

```php
'evolution' => [
    'api_key' => env('EVOLUTION_API_KEY'),
    'base_url' => env('EVOLUTION_BASE_URL'),
],
```

## 📖 الاستخدام

### إنشاء Instance جديد

```php
use App\Models\WhatsAppInstance;

$instance = WhatsAppInstance::create([
    'user_id' => auth()->id(),
    'instance_name' => 'my_whatsapp_bot',
    'integration_type' => 'WHATSAPP-BAILEYS',
    'is_active' => true,
]);
```

### إرسال رسالة

```php
use App\Services\EvolutionService;

$evolutionService = app(EvolutionService::class);

$result = $evolutionService->sendText(
    'my_whatsapp_bot',
    '218912345678',
    'مرحباً! هذه رسالة تجريبية'
);
```

### استخدام Job لإرسال رسالة

```php
use App\Jobs\WhatsApp\SendWhatsAppMessage;

SendWhatsAppMessage::dispatch(
    $instance,
    '218912345678',
    'مرحباً من الـ Queue!'
);
```

## 🔌 API Endpoints

### Instance Management

#### إنشاء Instance

```http
POST /api/whatsapp/instances
Content-Type: application/json
Authorization: Bearer {token}

{
    "instance_name": "my_bot",
    "integration": "WHATSAPP-BAILEYS"
}
```

#### الحصول على QR Code

```http
GET /api/whatsapp/instances/{instanceName}/qrcode
Authorization: Bearer {token}
```

#### حالة الاتصال

```http
GET /api/whatsapp/instances/{instanceName}/status
Authorization: Bearer {token}
```

#### تسجيل الخروج

```http
POST /api/whatsapp/instances/{instanceName}/logout
Authorization: Bearer {token}
```

#### حذف Instance

```http
DELETE /api/whatsapp/instances/{instanceName}
Authorization: Bearer {token}
```

### Messages

#### إرسال رسالة نصية

```http
POST /api/whatsapp/instances/{instanceName}/messages/text
Content-Type: application/json
Authorization: Bearer {token}

{
    "number": "218912345678",
    "text": "مرحباً!"
}
```

#### إرسال وسائط

```http
POST /api/whatsapp/instances/{instanceName}/messages/media
Content-Type: application/json
Authorization: Bearer {token}

{
    "number": "218912345678",
    "media_type": "image",
    "media_url": "https://example.com/image.jpg",
    "caption": "صورة جميلة"
}
```

#### إرسال أزرار

```http
POST /api/whatsapp/instances/{instanceName}/messages/buttons
Content-Type: application/json
Authorization: Bearer {token}

{
    "number": "218912345678",
    "description": "اختر أحد الخيارات:",
    "buttons": [
        {"text": "نعم", "id": "yes"},
        {"text": "لا", "id": "no"}
    ]
}
```

#### إرسال قائمة

```http
POST /api/whatsapp/instances/{instanceName}/messages/list
Content-Type: application/json
Authorization: Bearer {token}

{
    "number": "218912345678",
    "title": "القائمة الرئيسية",
    "description": "اختر قسماً",
    "button_text": "عرض الخيارات",
    "sections": [
        {
            "title": "الخدمات",
            "rows": [
                {
                    "title": "خدمة 1",
                    "description": "وصف الخدمة",
                    "rowId": "service_1"
                }
            ]
        }
    ]
}
```

#### تحديد كمقروء

```http
POST /api/whatsapp/instances/{instanceName}/messages/mark-read
Content-Type: application/json
Authorization: Bearer {token}

{
    "messages": [
        {
            "remoteJid": "218912345678@s.whatsapp.net",
            "fromMe": false,
            "id": "message_id_here"
        }
    ]
}
```

#### الحصول على الرسائل

```http
GET /api/whatsapp/instances/{instanceName}/messages?remote_jid=218912345678@s.whatsapp.net
Authorization: Bearer {token}
```

### Groups

#### إنشاء مجموعة

```http
POST /api/whatsapp/instances/{instanceName}/groups
Content-Type: application/json
Authorization: Bearer {token}

{
    "subject": "مجموعة العمل",
    "participants": [
        "218912345678",
        "218987654321"
    ]
}
```

#### الحصول على المجموعات

```http
GET /api/whatsapp/instances/{instanceName}/groups
Authorization: Bearer {token}
```

### Contacts

#### الحصول على جهات الاتصال

```http
GET /api/whatsapp/instances/{instanceName}/contacts
Authorization: Bearer {token}
```

## 🎪 Events & Listeners

### InstanceConnected Event

يتم إطلاقه عند اتصال Instance بنجاح.

```php
event(new \App\Events\WhatsApp\InstanceConnected($instance, $data));
```

### InstanceDisconnected Event

يتم إطلاقه عند انقطاع اتصال Instance.

```php
event(new \App\Events\WhatsApp\InstanceDisconnected($instance));
```

### MessageReceived Event

يتم إطلاقه عند استقبال رسالة جديدة.

```php
event(new \App\Events\WhatsApp\MessageReceived($instance, $messageData));
```

### إنشاء Listener مخصص

```php
namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageReceived;

class CustomMessageHandler
{
    public function handle(MessageReceived $event): void
    {
        // معالجة مخصصة للرسالة
        $instance = $event->instance;
        $messageData = $event->messageData;

        // منطقك هنا...
    }
}
```

## 🔄 Jobs

### SendWhatsAppMessage

إرسال رسالة في الخلفية.

```php
use App\Jobs\WhatsApp\SendWhatsAppMessage;

SendWhatsAppMessage::dispatch($instance, $number, $message);
```

### SyncWhatsAppContacts

مزامنة جهات الاتصال.

```php
use App\Jobs\WhatsApp\SyncWhatsAppContacts;

SyncWhatsAppContacts::dispatch($instance);
```

## 📊 Models

### WhatsAppInstance

```php
// العلاقات
$instance->user;           // المستخدم
$instance->messages;       // الرسائل
$instance->contacts;       // جهات الاتصال
$instance->chats;          // المحادثات

// Accessors
$instance->is_connected;   // bool
$instance->status_label;   // string (بالعربية)

// Methods
$instance->getTodayMessagesCount();
$instance->getUnreadMessagesCount();
$instance->updateConnectionStatus('connected', $data);

// Scopes
WhatsAppInstance::active()->get();
WhatsAppInstance::connected()->get();
```

### WhatsAppMessage

```php
// العلاقات
$message->instance;

// Accessors
$message->is_sent;
$message->is_delivered;
$message->is_read;

// Methods
$message->updateStatus('read');

// Scopes
WhatsAppMessage::sent()->get();
WhatsAppMessage::received()->get();
WhatsAppMessage::unread()->get();
WhatsAppMessage::fromContact($jid)->get();
```

### WhatsAppContact

```php
// العلاقات
$contact->instance;

// Accessors
$contact->display_name;

// Methods
$contact->block();
$contact->unblock();

// Scopes
WhatsAppContact::blocked()->get();
WhatsAppContact::business()->get();
```

### WhatsAppChat

```php
// العلاقات
$chat->instance;

// Methods
$chat->archive();
$chat->unarchive();
$chat->updateUnreadCount(5);
$chat->markAsRead();

// Scopes
WhatsAppChat::groups()->get();
WhatsAppChat::individual()->get();
WhatsAppChat::archived()->get();
WhatsAppChat::unread()->get();
```

## 💡 أمثلة الاستخدام

### مثال 1: إرسال رسالة ترحيب للعملاء الجدد

```php
use App\Jobs\WhatsApp\SendWhatsAppMessage;
use App\Models\WhatsAppInstance;

// في Controller أو Event Listener
public function sendWelcomeMessage($customerPhone)
{
    $instance = WhatsAppInstance::where('user_id', auth()->id())
        ->connected()
        ->first();

    if ($instance) {
        $message = "مرحباً بك في متجرنا! 🎉\n\n";
        $message .= "نحن سعداء بانضمامك إلينا.\n";
        $message .= "كيف يمكننا مساعدتك اليوم؟";

        SendWhatsAppMessage::dispatch($instance, $customerPhone, $message);
    }
}
```

### مثال 2: معالجة الرسائل الواردة

```php
namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageReceived;
use App\Services\EvolutionService;

class AutoReplyHandler
{
    public function handle(MessageReceived $event): void
    {
        $instance = $event->instance;
        $messageData = $event->messageData;

        $text = $this->extractText($messageData);
        $from = $messageData['key']['remoteJid'];

        if (str_contains(strtolower($text), 'السعر')) {
            $evolutionService = app(EvolutionService::class);
            $evolutionService->sendText(
                $instance->instance_name,
                $from,
                'أسعارنا تبدأ من 50 دينار. للمزيد من التفاصيل، اكتب "الباقات"'
            );
        }
    }

    private function extractText($messageData): string
    {
        return $messageData['message']['conversation']
            ?? $messageData['message']['extendedTextMessage']['text']
            ?? '';
    }
}
```

### مثال 3: إحصائيات الرسائل

```php
use App\Models\WhatsAppInstance;
use App\Models\WhatsAppMessage;

public function getMessageStats()
{
    $instance = WhatsAppInstance::find(1);

    return [
        'today_sent' => $instance->getTodayMessagesCount(),
        'unread' => $instance->getUnreadMessagesCount(),
        'total_sent' => $instance->messages()->sent()->count(),
        'total_received' => $instance->messages()->received()->count(),
        'success_rate' => $this->calculateSuccessRate($instance),
    ];
}

private function calculateSuccessRate($instance)
{
    $total = $instance->messages()->sent()->count();
    $delivered = $instance->messages()->sent()
        ->whereIn('status', ['delivered', 'read'])
        ->count();

    return $total > 0 ? ($delivered / $total) * 100 : 0;
}
```

## 🔧 تخصيص البوت الآلي

يمكنك تخصيص البوت في `app/Services/AutoReplyBotService.php`:

```php
protected function processMessage($instance, $remoteJid, $messageText, $originalMessage)
{
    $messageText = trim(mb_strtolower($messageText));

    // أضف منطقك المخصص هنا
    if (str_contains($messageText, 'كلمة مفتاحية')) {
        $this->sendCustomResponse($instance, $remoteJid);
        return;
    }

    // الرد الافتراضي
    $this->sendDefaultResponse($instance, $remoteJid);
}
```

## 🐛 استكشاف الأخطاء

### تحقق من الـ Logs

```bash
tail -f storage/logs/laravel.log
```

### تشغيل Queue Worker

```bash
php artisan queue:work --tries=3
```

### اختبار الاتصال بـ Evolution API

```bash
curl -X GET http://localhost:8080/instance/fetchInstances \
  -H "apikey: your_api_key"
```

## 📝 ملاحظات مهمة

1. **الأمان**: تأكد من حماية API Keys في ملف `.env`
2. **Rate Limiting**: راعِ حدود WhatsApp لإرسال الرسائل
3. **Webhooks**: تأكد من أن URL الـ webhook متاح للوصول من Evolution API
4. **Queue**: استخدم Queue للرسائل الكثيرة لتحسين الأداء
5. **Backup**: احتفظ بنسخة احتياطية من قاعدة البيانات بانتظام

## 🤝 المساهمة

نرحب بمساهماتك! يرجى:

1. Fork المشروع
2. إنشاء branch جديد
3. Commit التغييرات
4. Push إلى Branch
5. فتح Pull Request

## 📄 الترخيص

هذا المشروع مرخص تحت MIT License.

## 📞 الدعم

للدعم والاستفسارات:

-   Email: support@example.com
-   WhatsApp: +218 91 234 5678

---

**تم التطوير بواسطة فريق Supportly** ❤️
