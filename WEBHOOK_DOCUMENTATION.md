# Evolution API Webhook Handler

## نظرة عامة

هذا الملف يوثق كيفية عمل معالج Webhook الخاص بـ Evolution API في التطبيق.

## البنية

### Route الوحيد

```php
Route::post('/evolution/webhook', [WebhookController::class, 'handle']);
```

هذا هو الـ Route الوحيد المستخدم لاستقبال جميع أحداث Evolution API.

## الملفات الرئيسية

### 1. WebhookController

**المسار:** `app/Http/Controllers/Webhook/WebhookController.php`

هذا هو المتحكم الرئيسي الذي يتعامل مع جميع أحداث Webhook من Evolution API.

### 2. Evolution Config

**المسار:** `config/evolution.php`

يحتوي على إعدادات Webhook الافتراضية:

```php
'webhook' => [
    'enabled' => true,
    'url' => env('APP_URL') . '/api/evolution/webhook',
    'by_events' => false,
    'base64' => true,
    'events' => [
        'APPLICATION_STARTUP',
        'QRCODE_UPDATED',
        'MESSAGES_UPSERT',
        'CONNECTION_UPDATE',
        'SEND_MESSAGE',
        'CHATS_UPSERT',
        'GROUPS_UPSERT',
        'GROUP_UPDATE',
        'GROUP_PARTICIPANTS_UPDATE',
        'CALL',
    ],
]
```

## الأحداث المدعومة

### 1. أحداث التطبيق

-   **APPLICATION_STARTUP**: عند بدء تشغيل التطبيق

### 2. أحداث QR Code

-   **QRCODE_UPDATED**: عند تحديث رمز QR
    -   يتم حفظ QR Code في قاعدة البيانات
    -   يتم تحديث حالة الـ Instance إلى `qr_code`

### 3. أحداث الاتصال

-   **CONNECTION_UPDATE**: عند تغيير حالة الاتصال
    -   `open`: متصل
    -   `close`: غير متصل
    -   `connecting`: جاري الاتصال

### 4. أحداث الرسائل

-   **MESSAGES_SET**: تحميل الرسائل الأولية
-   **MESSAGES_UPSERT**: رسالة جديدة واردة
-   **MESSAGES_UPDATE**: تحديث رسالة (مثل إيصالات القراءة)
-   **MESSAGES_DELETE**: حذف رسالة
-   **SEND_MESSAGE**: إرسال رسالة

### 5. أحداث جهات الاتصال

-   **CONTACTS_SET**: تحميل جهات الاتصال الأولية
-   **CONTACTS_UPSERT**: إضافة/تحديث جهة اتصال
-   **CONTACTS_UPDATE**: تحديث جهة اتصال

### 6. أحداث المحادثات

-   **CHATS_SET**: تحميل المحادثات الأولية
-   **CHATS_UPSERT**: إضافة/تحديث محادثة
-   **CHATS_UPDATE**: تحديث محادثة
-   **CHATS_DELETE**: حذف محادثة

### 7. أحداث المجموعات

-   **GROUPS_UPSERT**: إضافة/تحديث مجموعة
-   **GROUP_UPDATE**: تحديث معلومات المجموعة
-   **GROUP_PARTICIPANTS_UPDATE**: تحديث أعضاء المجموعة

### 8. أحداث أخرى

-   **PRESENCE_UPDATE**: تحديث حالة الحضور (متصل/غير متصل)
-   **LABELS_EDIT**: تعديل التصنيفات
-   **LABELS_ASSOCIATION**: ربط التصنيفات
-   **CALL**: مكالمة واردة
-   **TYPEBOT_START**: بدء Typebot
-   **TYPEBOT_CHANGE_STATUS**: تغيير حالة Typebot

## أنواع الرسائل المدعومة

### 1. رسائل نصية (Text)

```php
'type' => 'text'
'content' => 'نص الرسالة'
```

### 2. رسائل الصور (Image)

```php
'type' => 'image'
'content' => 'التعليق على الصورة'
'media_info' => [
    'mimetype' => 'image/jpeg',
    'fileLength' => 123456,
    'height' => 1920,
    'width' => 1080,
]
```

### 3. رسائل الفيديو (Video)

```php
'type' => 'video'
'content' => 'التعليق على الفيديو'
'media_info' => [
    'mimetype' => 'video/mp4',
    'fileLength' => 1234567,
    'seconds' => 30,
    'height' => 1920,
    'width' => 1080,
]
```

### 4. رسائل الصوت (Audio)

```php
'type' => 'audio'
'media_info' => [
    'mimetype' => 'audio/ogg',
    'fileLength' => 12345,
    'seconds' => 15,
    'ptt' => true, // رسالة صوتية (Voice Note)
]
```

### 5. رسائل المستندات (Document)

```php
'type' => 'document'
'content' => 'التعليق على المستند'
'media_info' => [
    'fileName' => 'document.pdf',
    'mimetype' => 'application/pdf',
    'fileLength' => 123456,
]
```

### 6. الملصقات (Sticker)

```php
'type' => 'sticker'
'media_info' => [
    'mimetype' => 'image/webp',
    'fileLength' => 12345,
]
```

### 7. الموقع (Location)

```php
'type' => 'location'
'content' => 'عنوان الموقع'
'media_info' => [
    'latitude' => 32.8872,
    'longitude' => 13.1913,
]
```

### 8. جهات الاتصال (Contact)

```php
'type' => 'contact'
'content' => 'اسم جهة الاتصال'
'media_info' => [
    'vcard' => 'BEGIN:VCARD...',
]
```

### 9. الاستطلاعات (Poll)

```php
'type' => 'poll'
'content' => 'سؤال الاستطلاع'
'media_info' => [
    'options' => [...],
]
```

### 10. ردود الفعل (Reaction)

```php
'type' => 'reaction'
'content' => '❤️'
'media_info' => [
    'key' => [...],
]
```

## السجلات (Logs)

### مستويات السجلات

#### 1. معلومات عامة (Info)

جميع الأحداث يتم تسجيلها مع emoji مميز:

-   📱 Application Started
-   🔲 QR Code Updated
-   🔌 Connection State Changed
-   📦 Messages Set Received
-   📨 New Message Event
-   💬 Message Details
-   📝 Text Message
-   🖼️ Image Message
-   🎥 Video Message
-   🎵 Audio Message
-   📄 Document Message
-   🎭 Sticker Message
-   📍 Location Message
-   👤 Contact Message
-   👥 Contacts/Groups

#### 2. تحذيرات (Warning)

-   ⚠️ Message without key
-   No handler for event type

#### 3. أخطاء (Error)

-   Webhook handling error
-   Error processing message

### مثال على Log رسالة نصية

```
[2025-12-04 19:38:07] local.INFO: 💬 Message Details {
    "instance": "my_instance",
    "message_id": "3EB0123456789ABCDEF",
    "sender": "218921234567@s.whatsapp.net",
    "receiver": "Me (Bot)",
    "sender_name": "John Doe",
    "from_me": false,
    "message_type": "text",
    "timestamp": "2025-12-04 19:38:05",
    "content": "مرحبا",
    "media_info": null
}

[2025-12-04 19:38:07] local.INFO: 📝 Text Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "text": "مرحبا"
}
```

### مثال على Log رسالة صورة

```
[2025-12-04 19:38:07] local.INFO: 🖼️ Image Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "caption": "شاهد هذه الصورة",
    "mimetype": "image/jpeg",
    "file_size": 123456
}
```

## كيفية الاستخدام

### 1. إعداد Webhook في Evolution API

عند إنشاء Instance جديد، يتم تلقائياً تكوين Webhook:

```php
'webhook' => [
    'url' => url('/api/evolution/webhook'),
    'byEvents' => false,
    'base64' => true,
    'events' => [
        'MESSAGES_UPSERT',
        'CONNECTION_UPDATE',
        'QRCODE_UPDATED',
    ]
]
```

### 2. معالجة الرسائل الواردة

يمكنك إضافة منطق معالجة مخصص في دالة `processMessages`:

```php
protected function processMessages($data)
{
    // ... الكود الحالي ...

    // TODO: أضف منطقك المخصص هنا
    // مثال: حفظ في قاعدة البيانات
    // مثال: تفعيل الرد التلقائي
    // مثال: إرسال إشعارات
}
```

### 3. مراقبة السجلات

لمراقبة جميع الأحداث الواردة:

```bash
php artisan tail
```

أو لمراقبة ملف Log محدد:

```bash
tail -f storage/logs/laravel.log
```

## الأمان

### 1. التحقق من Instance

يتم التحقق من أن Instance موجود في قاعدة البيانات قبل معالجة أي حدث.

### 2. معالجة الأخطاء

جميع الأخطاء يتم التقاطها وتسجيلها مع تفاصيل كاملة.

### 3. Validation

يتم التحقق من وجود `event` في البيانات الواردة.

## التطوير المستقبلي

### أفكار للتحسين

1. **حفظ الرسائل في قاعدة البيانات**

    - إنشاء جدول `messages`
    - حفظ جميع الرسائل الواردة والصادرة

2. **نظام الرد التلقائي**

    - معالجة الرسائل بناءً على قواعد محددة
    - إرسال ردود تلقائية

3. **إشعارات في الوقت الفعلي**

    - استخدام WebSockets
    - إرسال إشعارات للمستخدمين

4. **تحليلات وإحصائيات**

    - عدد الرسائل المرسلة/المستلمة
    - أوقات الذروة
    - أنواع الرسائل الأكثر استخداماً

5. **معالجة متقدمة للوسائط**
    - تحميل الصور/الفيديوهات
    - معالجة الملفات الصوتية
    - استخراج النصوص من الصور (OCR)

## الدعم

للمزيد من المعلومات حول Evolution API:

-   [Evolution API Documentation](https://doc.evolution-api.com/)
-   [Evolution API GitHub](https://github.com/EvolutionAPI/evolution-api)

## الملاحظات

-   تأكد من تعيين `EVOLUTION_WEBHOOK_URL` في ملف `.env`
-   تأكد من أن الـ URL قابل للوصول من خارج الشبكة المحلية
-   استخدم HTTPS في الإنتاج لضمان الأمان
