# Evolution WhatsApp Service - دليل الاستخدام

## نظرة عامة

خدمة متكاملة للتعامل مع Evolution API لإنشاء بوتات WhatsApp مع إمكانيات متقدمة.

## المميزات الرئيسية

### 1. إدارة الـ Instances

-   إنشاء instance جديد
-   الحصول على QR Code للاتصال
-   فحص حالة الاتصال
-   تسجيل الخروج وحذف الـ instance

### 2. إرسال الرسائل

-   **نصوص**: رسائل نصية بسيطة
-   **وسائط**: صور، فيديوهات، مستندات
-   **أزرار تفاعلية**: Quick Reply Buttons
-   **قوائم**: List Messages
-   **ملصقات**: Stickers
-   **موقع**: Location
-   **جهات اتصال**: Contacts
-   **استطلاعات**: Polls

### 3. إدارة المحادثات

-   قراءة الرسائل
-   أرشفة المحادثات
-   حذف الرسائل
-   تحديث الرسائل
-   إرسال حالة الكتابة (typing indicator)
-   حظر/إلغاء حظر جهات الاتصال

### 4. إدارة المجموعات

-   إنشاء مجموعات
-   تحديث اسم ووصف المجموعة
-   إضافة/إزالة أعضاء
-   ترقية/تخفيض رتبة المشرفين
-   الحصول على رابط الدعوة

## التثبيت والإعداد

### 1. إعداد ملف `.env`

```env
EVOLUTION_BASE_URL=https://your-evolution-api-url.com
EVOLUTION_API_KEY=your-global-api-key
```

### 2. إعداد ملف `config/services.php`

```php
'evolution' => [
    'base_url' => env('EVOLUTION_BASE_URL'),
    'api_key' => env('EVOLUTION_API_KEY'),
],
```

## أمثلة الاستخدام

### إنشاء Instance جديد

```php
use App\Services\EvolutionService;

$evolutionService = new EvolutionService();

$result = $evolutionService->createInstance('my-instance', [
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS',
    'webhook' => [
        'url' => 'https://your-domain.com/api/evolution/webhook',
        'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE']
    ]
]);

if ($result['success']) {
    $qrCode = $result['data']['qrcode']['base64'];
    // عرض QR Code للمستخدم
}
```

### إرسال رسالة نصية

```php
$result = $evolutionService->sendText(
    'my-instance',
    '218912345678',
    'مرحباً! هذه رسالة تجريبية'
);
```

### إرسال رسالة مع أزرار

```php
$result = $evolutionService->sendQuickReply(
    'my-instance',
    '218912345678',
    'اختر أحد الخيارات:',
    [
        ['text' => 'الخيار 1', 'id' => 'option_1'],
        ['text' => 'الخيار 2', 'id' => 'option_2'],
        ['text' => 'الخيار 3', 'id' => 'option_3'],
    ]
);
```

### إرسال قائمة

```php
$result = $evolutionService->sendList(
    'my-instance',
    '218912345678',
    'عنوان القائمة',
    'وصف القائمة',
    'اضغط هنا',
    [
        [
            'title' => 'القسم الأول',
            'rows' => [
                [
                    'title' => 'العنصر 1',
                    'description' => 'وصف العنصر 1',
                    'rowId' => 'item_1'
                ],
                [
                    'title' => 'العنصر 2',
                    'description' => 'وصف العنصر 2',
                    'rowId' => 'item_2'
                ]
            ]
        ]
    ]
);
```

### إرسال صورة

```php
$result = $evolutionService->sendMedia(
    'my-instance',
    '218912345678',
    'https://example.com/image.jpg',
    'image',
    [
        'caption' => 'وصف الصورة',
        'fileName' => 'image.jpg'
    ]
);
```

### إنشاء مجموعة

```php
$result = $evolutionService->createGroup(
    'my-instance',
    'اسم المجموعة',
    ['218912345678@s.whatsapp.net', '218987654321@s.whatsapp.net']
);
```

## استخدام API Endpoints

### إنشاء Instance

```http
POST /api/whatsapp/instances
Authorization: Bearer {token}
Content-Type: application/json

{
    "instance_name": "my-bot-instance",
    "integration": "WHATSAPP-BAILEYS"
}
```

### إرسال رسالة نصية

```http
POST /api/whatsapp/instances/{instanceName}/messages/text
Authorization: Bearer {token}
Content-Type: application/json

{
    "number": "218912345678",
    "text": "مرحباً!"
}
```

### إرسال أزرار

```http
POST /api/whatsapp/instances/{instanceName}/messages/buttons
Authorization: Bearer {token}
Content-Type: application/json

{
    "number": "218912345678",
    "description": "اختر أحد الخيارات:",
    "buttons": [
        {"text": "الخيار 1", "id": "opt1"},
        {"text": "الخيار 2", "id": "opt2"}
    ]
}
```

### إرسال قائمة

```http
POST /api/whatsapp/instances/{instanceName}/messages/list
Authorization: Bearer {token}
Content-Type: application/json

{
    "number": "218912345678",
    "title": "العنوان",
    "description": "الوصف",
    "button_text": "اضغط هنا",
    "sections": [
        {
            "title": "القسم الأول",
            "rows": [
                {
                    "title": "العنصر 1",
                    "description": "وصف",
                    "rowId": "item1"
                }
            ]
        }
    ]
}
```

## معالجة Webhooks

يتم استقبال الأحداث من Evolution API عبر webhook في:

```
POST /api/evolution/webhook
```

### الأحداث المدعومة:

-   `qrcode.updated`: تحديث QR Code
-   `connection.update`: تحديث حالة الاتصال
-   `messages.upsert`: رسائل واردة جديدة
-   `messages.update`: تحديثات على الرسائل

### مثال على معالجة الرسائل الواردة

في `WhatsAppController.php`، يمكنك تخصيص دالة `processIncomingMessage`:

```php
protected function processIncomingMessage($instance, $message)
{
    $remoteJid = $message['key']['remoteJid'];
    $messageText = $message['message']['conversation']
        ?? $message['message']['extendedTextMessage']['text']
        ?? null;

    // مثال: رد تلقائي بناءً على الكلمات المفتاحية
    if (stripos($messageText, 'مرحبا') !== false) {
        $this->evolutionService->sendText(
            $instance->instance_name,
            $remoteJid,
            'مرحباً بك! كيف يمكنني مساعدتك؟'
        );
    } elseif (stripos($messageText, 'الأسعار') !== false) {
        $this->evolutionService->sendQuickReply(
            $instance->instance_name,
            $remoteJid,
            'اختر الباقة المناسبة:',
            [
                ['text' => 'الباقة الأساسية - 50 دينار', 'id' => 'basic'],
                ['text' => 'الباقة المتقدمة - 100 دينار', 'id' => 'advanced'],
                ['text' => 'الباقة الاحترافية - 200 دينار', 'id' => 'pro'],
            ]
        );
    }

    // حفظ الرسالة في قاعدة البيانات
    // Message::create([...]);
}
```

## الدوال المساعدة

### تنسيق رقم الهاتف

```php
$formattedNumber = $evolutionService->formatPhoneNumber('218912345678');
// النتيجة: 218912345678@s.whatsapp.net
```

### تنسيق معرف المجموعة

```php
$formattedGroupId = $evolutionService->formatGroupJid('120363123456789012');
// النتيجة: 120363123456789012@g.us
```

## نصائح وأفضل الممارسات

### 1. معالجة الأخطاء

```php
$result = $evolutionService->sendText($instance, $number, $text);

if (!$result['success']) {
    Log::error('Failed to send message', [
        'error' => $result['error'],
        'instance' => $instance,
        'number' => $number
    ]);

    // معالجة الخطأ
}
```

### 2. استخدام Queues للرسائل الكثيرة

```php
use Illuminate\Support\Facades\Queue;

Queue::push(function() use ($evolutionService, $instance, $number, $text) {
    $evolutionService->sendText($instance, $number, $text);
});
```

### 3. التحقق من حالة الاتصال قبل الإرسال

```php
$status = $evolutionService->getConnectionStatus($instanceName);

if ($status['success'] && $status['data']['state'] === 'open') {
    // الاتصال نشط، يمكن الإرسال
    $evolutionService->sendText(...);
}
```

## الأحداث المتاحة للـ Webhook

يمكنك الاشتراك في الأحداث التالية:

-   `APPLICATION_STARTUP`
-   `QRCODE_UPDATED`
-   `MESSAGES_SET`
-   `MESSAGES_UPSERT`
-   `MESSAGES_UPDATE`
-   `MESSAGES_DELETE`
-   `SEND_MESSAGE`
-   `CONTACTS_SET`
-   `CONTACTS_UPSERT`
-   `CONTACTS_UPDATE`
-   `PRESENCE_UPDATE`
-   `CHATS_SET`
-   `CHATS_UPSERT`
-   `CHATS_UPDATE`
-   `CHATS_DELETE`
-   `GROUPS_UPSERT`
-   `GROUP_UPDATE`
-   `GROUP_PARTICIPANTS_UPDATE`
-   `CONNECTION_UPDATE`
-   `CALL`

## الدعم والمساعدة

للمزيد من المعلومات حول Evolution API:

-   [التوثيق الرسمي](https://doc.evolution-api.com/)
-   [مجموعة Postman](https://www.postman.com/agenciadgcode/evolution-api/)

## الترخيص

هذا المشروع مفتوح المصدر ومتاح للاستخدام الحر.
