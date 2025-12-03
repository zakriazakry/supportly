# Evolution API Integration - Complete Implementation

تم بناء تكامل كامل مع Evolution API v2.3 يتضمن جميع الميزات المتاحة في الـ API.

## 📁 الملفات المنشأة

### 1. **EvolutionService.php** (`app/Services/EvolutionService.php`)

Service شامل يحتوي على جميع methods للتعامل مع Evolution API:

#### الميزات المتوفرة:

-   ✅ **Instance Management** (إنشاء، حذف، إعادة تشغيل، الاتصال)
-   ✅ **Send Messages** (نص، وسائط، موقع، جهات اتصال، استطلاعات، قوائم، أزرار، ملصقات)
-   ✅ **Chat Management** (قراءة، أرشفة، حذف، تحديث، حظر)
-   ✅ **Group Management** (إنشاء، تحديث، إدارة الأعضاء، الإعدادات)
-   ✅ **Profile Settings** (تحديث الاسم، الحالة، الصورة، الخصوصية)
-   ✅ **Webhooks** (تعيين، الحصول على الإعدادات)
-   ✅ **Proxy Management** (تعيين، الحصول على الإعدادات)
-   ✅ **Settings Management** (تعيين، الحصول على الإعدادات)
-   ✅ **Label Management** (البحث، الإضافة، الإزالة)
-   ✅ **Integrations** (Chatwoot, RabbitMQ, SQS, Typebot)
-   ✅ **Call Management** (مكالمات وهمية)

### 2. **WebhookController.php** (`app/Http/Controllers/Evolution/WebhookController.php`)

Controller لمعالجة جميع أنواع الـ webhooks من Evolution API:

#### الأحداث المدعومة:

-   ✅ `APPLICATION_STARTUP` - بدء التطبيق
-   ✅ `QRCODE_UPDATED` - تحديث QR Code
-   ✅ `CONNECTION_UPDATE` - تحديث حالة الاتصال
-   ✅ `MESSAGES_SET` - تحميل الرسائل الأولية
-   ✅ `MESSAGES_UPSERT` - رسالة جديدة
-   ✅ `MESSAGES_UPDATE` - تحديث رسالة
-   ✅ `MESSAGES_DELETE` - حذف رسالة
-   ✅ `SEND_MESSAGE` - إرسال رسالة
-   ✅ `CONTACTS_SET` - تحميل جهات الاتصال
-   ✅ `CONTACTS_UPSERT` - تحديث جهة اتصال
-   ✅ `CONTACTS_UPDATE` - تحديث جهة اتصال
-   ✅ `PRESENCE_UPDATE` - تحديث الحضور
-   ✅ `CHATS_SET` - تحميل المحادثات
-   ✅ `CHATS_UPSERT` - تحديث محادثة
-   ✅ `CHATS_UPDATE` - تحديث محادثة
-   ✅ `CHATS_DELETE` - حذف محادثة
-   ✅ `GROUPS_UPSERT` - تحديث مجموعة
-   ✅ `GROUP_UPDATE` - تحديث مجموعة
-   ✅ `GROUP_PARTICIPANTS_UPDATE` - تحديث أعضاء المجموعة
-   ✅ `LABELS_EDIT` - تعديل التسميات
-   ✅ `LABELS_ASSOCIATION` - ربط التسميات
-   ✅ `CALL` - مكالمة واردة
-   ✅ `TYPEBOT_START` - بدء Typebot
-   ✅ `TYPEBOT_CHANGE_STATUS` - تغيير حالة Typebot

### 3. **WhatsAppController.php** (`app/Http/Controllers/Api/WhatsAppController.php`)

مثال عملي لـ Controller يوضح كيفية استخدام EvolutionService:

#### الـ Methods المتوفرة:

-   `createInstance()` - إنشاء instance جديد
-   `getQRCode()` - الحصول على QR Code
-   `getConnectionStatus()` - الحصول على حالة الاتصال
-   `sendMessage()` - إرسال رسالة نصية
-   `sendMedia()` - إرسال وسائط
-   `createGroup()` - إنشاء مجموعة
-   `getGroups()` - الحصول على جميع المجموعات
-   `getContacts()` - الحصول على جهات الاتصال
-   `getMessages()` - الحصول على الرسائل
-   `updateProfile()` - تحديث الملف الشخصي
-   `deleteInstance()` - حذف instance
-   `logoutInstance()` - تسجيل الخروج

### 4. **EVOLUTION_API_GUIDE.md**

دليل شامل باللغة العربية يشرح:

-   الإعداد الأولي
-   جميع الميزات المتاحة
-   أمثلة عملية لكل ميزة
-   معالجة الأخطاء
-   أمثلة متقدمة

### 5. **evolution_api_routes_example.php** (`routes/evolution_api_routes_example.php`)

ملف مثالي للـ routes يوضح كيفية تنظيم الـ API endpoints

---

## 🚀 البدء السريع

### 1. الإعداد

أضف المتغيرات التالية في ملف `.env`:

```env
EVOLUTION_API_KEY=your-global-api-key
EVOLUTION_BASE_URL=https://your-evolution-api-url.com
```

### 2. إضافة الإعدادات

في `config/services.php`:

```php
'evolution' => [
    'api_key' => env('EVOLUTION_API_KEY'),
    'base_url' => env('EVOLUTION_BASE_URL'),
],
```

### 3. إضافة الـ Routes

في `routes/api.php`:

```php
use App\Http\Controllers\Evolution\WebhookController;

// Webhook endpoint
Route::post('/evolution/webhook', [WebhookController::class, 'handle']);
```

### 4. استخدام الـ Service

```php
use App\Services\EvolutionService;

$service = new EvolutionService();

// إنشاء instance
$result = $service->createInstance([
    'instanceName' => 'my-bot',
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS'
]);

// إرسال رسالة
$result = $service->sendText('my-bot', [
    'number' => '201234567890',
    'text' => 'مرحباً!'
]);
```

---

## 📚 التوثيق الكامل

راجع ملف `EVOLUTION_API_GUIDE.md` للحصول على:

-   شرح تفصيلي لجميع الميزات
-   أمثلة عملية لكل ميزة
-   أفضل الممارسات
-   حل المشاكل الشائعة

---

## 🔧 الميزات المتقدمة

### 1. Webhook Handling

الـ `WebhookController` يعالج جميع الأحداث تلقائياً ويقوم بـ:

-   تسجيل جميع الأحداث في الـ logs
-   تحديث حالة الـ instance في قاعدة البيانات
-   معالجة الرسائل الواردة
-   تحديث QR Code تلقائياً

### 2. Error Handling

جميع الـ methods ترجع response موحد:

```php
// Success
[
    'success' => true,
    'data' => [...]
]

// Error
[
    'success' => false,
    'error' => 'Error message',
    'status' => 400
]
```

### 3. Logging

جميع الأخطاء والأحداث المهمة يتم تسجيلها في Laravel logs:

-   API Errors
-   Webhook Events
-   Connection Updates
-   Message Processing

---

## 📊 هيكل قاعدة البيانات

تأكد من وجود جدول `whats_app_instances` مع الحقول التالية:

```php
Schema::create('whats_app_instances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('instance_name')->unique();
    $table->string('instance_key')->nullable();
    $table->text('qr_code')->nullable();
    $table->string('status')->default('pending'); // pending, qr_code, connected, disconnected
    $table->string('connection_state')->nullable();
    $table->string('integration_type')->default('WHATSAPP-BAILEYS');
    $table->timestamps();
});
```

---

## 🎯 أمثلة الاستخدام

### مثال 1: إنشاء Bot بسيط

```php
use App\Services\EvolutionService;

$service = new EvolutionService();

// 1. إنشاء instance
$result = $service->createInstance([
    'instanceName' => 'support-bot',
    'qrcode' => true,
    'webhook' => [
        'url' => url('/api/evolution/webhook'),
        'events' => ['MESSAGES_UPSERT']
    ]
]);

// 2. في WebhookController
protected function handleMessagesUpsert($data)
{
    $text = $this->extractMessageText($data['data']['message']);

    if ($text === 'مرحبا') {
        $this->evolutionService->sendText($data['instance'], [
            'number' => str_replace('@s.whatsapp.net', '', $data['data']['key']['remoteJid']),
            'text' => 'مرحباً بك! كيف يمكنني مساعدتك؟'
        ]);
    }
}
```

### مثال 2: إرسال رسائل جماعية

```php
$numbers = ['201111111111', '202222222222', '203333333333'];

foreach ($numbers as $number) {
    $service->sendText('my-instance', [
        'number' => $number,
        'text' => 'رسالة جماعية لجميع العملاء',
        'delay' => 2000 // تأخير 2 ثانية بين كل رسالة
    ]);
}
```

### مثال 3: إنشاء مجموعة وإضافة أعضاء

```php
// إنشاء مجموعة
$result = $service->createGroup('my-instance',
    'مجموعة الدعم',
    ['201111111111', '202222222222'],
    'مجموعة للدعم الفني'
);

$groupJid = $result['data']['id'];

// إضافة أعضاء جدد
$service->updateParticipant('my-instance',
    $groupJid,
    'add',
    ['203333333333', '204444444444']
);
```

---

## 🔐 الأمان

1. **API Key**: احفظ الـ API key في `.env` ولا تشاركه
2. **Webhook URL**: استخدم HTTPS للـ webhook URL
3. **Authentication**: أضف middleware للـ authentication على جميع الـ routes
4. **Validation**: تحقق من صحة جميع الـ inputs
5. **Rate Limiting**: أضف rate limiting للـ API endpoints

---

## 🐛 حل المشاكل

### المشكلة: لا يتم استقبال الـ webhooks

**الحل:**

1. تأكد من أن الـ webhook URL صحيح ويمكن الوصول إليه من الإنترنت
2. تحقق من الـ logs في `storage/logs/laravel.log`
3. تأكد من تعيين الـ webhook في الـ instance

### المشكلة: فشل إرسال الرسائل

**الحل:**

1. تحقق من حالة الاتصال باستخدام `getConnectionStatus()`
2. تأكد من أن الـ instance متصل
3. تحقق من صحة رقم الهاتف (يجب أن يكون بدون + أو 00)

### المشكلة: QR Code لا يظهر

**الحل:**

1. استخدم `connectInstance()` للحصول على QR Code جديد
2. تأكد من أن الـ `qrcode` option مفعل عند إنشاء الـ instance

---

## 📝 ملاحظات مهمة

1. **Instance Name**: يجب أن يكون فريداً لكل instance
2. **Phone Numbers**: يجب أن تكون بدون + أو 00 (مثال: 201234567890)
3. **Remote JID**: للمحادثات الفردية `NUMBER@s.whatsapp.net`، للمجموعات `GROUP_ID@g.us`
4. **Media**: يمكن إرسال الوسائط عن طريق URL أو base64
5. **Delays**: استخدم delay بين الرسائل لتجنب الحظر (1-2 ثانية)

---

## 🔄 التحديثات المستقبلية

-   [ ] إضافة Queue للرسائل الجماعية
-   [ ] إضافة Dashboard لإدارة الـ instances
-   [ ] إضافة تقارير وإحصائيات
-   [ ] إضافة Auto-reply system
-   [ ] إضافة Chatbot integration

---

## 📞 الدعم

للمزيد من المعلومات:

-   [Evolution API Documentation](https://doc.evolution-api.com/)
-   [Evolution API GitHub](https://github.com/EvolutionAPI/evolution-api)
-   راجع ملف `EVOLUTION_API_GUIDE.md` للتوثيق الكامل

---

## 📄 الترخيص

هذا الكود مفتوح المصدر ويمكن استخدامه بحرية في مشاريعك.

---

**تم بناء هذا التكامل بواسطة:** Antigravity AI Assistant
**التاريخ:** 2025-12-03
**الإصدار:** 1.0.0
