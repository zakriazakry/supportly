# Evolution API Service - Documentation

هذا الملف يوثق كيفية استخدام `EvolutionService` للتعامل مع Evolution API v2.3.

## المحتويات

1. [الإعداد الأولي](#الإعداد-الأولي)
2. [إدارة الـ Instances](#إدارة-الـ-instances)
3. [إرسال الرسائل](#إرسال-الرسائل)
4. [إدارة المحادثات](#إدارة-المحادثات)
5. [إدارة المجموعات](#إدارة-المجموعات)
6. [إدارة الملف الشخصي](#إدارة-الملف-الشخصي)
7. [الـ Webhooks](#الـ-webhooks)
8. [التكاملات](#التكاملات)

---

## الإعداد الأولي

### 1. إضافة المتغيرات في `.env`

```env
EVOLUTION_API_KEY=your-global-api-key
EVOLUTION_BASE_URL=https://your-evolution-api-url.com
```

### 2. إضافة الإعدادات في `config/services.php`

```php
'evolution' => [
    'api_key' => env('EVOLUTION_API_KEY'),
    'base_url' => env('EVOLUTION_BASE_URL'),
],
```

---

## إدارة الـ Instances

### إنشاء Instance جديد

```php
use App\Services\EvolutionService;

$evolutionService = new EvolutionService();

$result = $evolutionService->createInstance([
    'instanceName' => 'my-instance',
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS', // WHATSAPP-BAILEYS | WHATSAPP-BUSINESS
    'webhook' => [
        'url' => 'https://your-domain.com/api/evolution/webhook',
        'byEvents' => false,
        'base64' => true,
        'events' => [
            'MESSAGES_UPSERT',
            'CONNECTION_UPDATE',
            'QRCODE_UPDATED',
        ]
    ]
]);

if ($result['success']) {
    $qrCode = $result['data']['qrcode']['base64'] ?? null;
    $instanceKey = $result['data']['hash'] ?? null;
}
```

### الحصول على جميع الـ Instances

```php
$result = $evolutionService->fetchInstances();
```

### الاتصال بـ Instance والحصول على QR Code

```php
$result = $evolutionService->connectInstance('my-instance');
```

### التحقق من حالة الاتصال

```php
$result = $evolutionService->getConnectionStatus('my-instance');
```

### إعادة تشغيل Instance

```php
$result = $evolutionService->restartInstance('my-instance');
```

### تسجيل الخروج من Instance

```php
$result = $evolutionService->logoutInstance('my-instance');
```

### حذف Instance

```php
$result = $evolutionService->deleteInstance('my-instance');
```

---

## إرسال الرسائل

### إرسال رسالة نصية

```php
$result = $evolutionService->sendText('my-instance', [
    'number' => '201234567890',
    'text' => 'مرحباً! هذه رسالة تجريبية',
    'delay' => 1200, // اختياري: تأخير بالميلي ثانية
]);
```

### إرسال صورة أو فيديو أو ملف

```php
$result = $evolutionService->sendMedia('my-instance', [
    'number' => '201234567890',
    'mediatype' => 'image', // image, video, document
    'mimetype' => 'image/png',
    'caption' => 'شاهد هذه الصورة',
    'media' => 'https://example.com/image.png', // URL أو base64
    'fileName' => 'image.png'
]);
```

### إرسال موقع

```php
$result = $evolutionService->sendLocation('my-instance', [
    'number' => '201234567890',
    'name' => 'موقعي',
    'address' => 'القاهرة، مصر',
    'latitude' => 30.0444,
    'longitude' => 31.2357
]);
```

### إرسال جهة اتصال

```php
$result = $evolutionService->sendContact('my-instance', [
    'number' => '201234567890',
    'contact' => [
        [
            'fullName' => 'أحمد محمد',
            'wuid' => '201111111111',
            'phoneNumber' => '+20 11 1111 1111',
            'organization' => 'شركة مثال', // اختياري
            'email' => 'ahmed@example.com', // اختياري
        ]
    ]
]);
```

### إرسال استطلاع رأي (Poll)

```php
$result = $evolutionService->sendPoll('my-instance', [
    'number' => '201234567890',
    'name' => 'ما هو لونك المفضل؟',
    'selectableCount' => 1,
    'values' => [
        'أحمر',
        'أزرق',
        'أخضر'
    ]
]);
```

### إرسال قائمة (List)

```php
$result = $evolutionService->sendList('my-instance', [
    'number' => '201234567890',
    'title' => 'عنوان القائمة',
    'description' => 'وصف القائمة',
    'buttonText' => 'اضغط هنا',
    'footerText' => 'تذييل القائمة',
    'sections' => [
        [
            'title' => 'القسم الأول',
            'rows' => [
                [
                    'title' => 'الخيار 1',
                    'description' => 'وصف الخيار 1',
                    'rowId' => 'option_1'
                ],
                [
                    'title' => 'الخيار 2',
                    'description' => 'وصف الخيار 2',
                    'rowId' => 'option_2'
                ]
            ]
        ]
    ]
]);
```

### إرسال أزرار (Buttons)

```php
$result = $evolutionService->sendButtons('my-instance', [
    'number' => '201234567890',
    'title' => 'عنوان الرسالة',
    'description' => 'وصف الرسالة',
    'footer' => 'تذييل الرسالة',
    'buttons' => [
        [
            'type' => 'reply',
            'displayText' => 'نعم',
            'id' => 'yes'
        ],
        [
            'type' => 'reply',
            'displayText' => 'لا',
            'id' => 'no'
        ]
    ]
]);
```

### إرسال رد فعل (Reaction)

```php
$result = $evolutionService->sendReaction('my-instance', [
    'key' => [
        'remoteJid' => '201234567890@s.whatsapp.net',
        'fromMe' => false,
        'id' => 'MESSAGE_ID'
    ],
    'reaction' => '❤️'
]);
```

### إرسال Sticker

```php
$result = $evolutionService->sendSticker('my-instance', [
    'number' => '201234567890',
    'sticker' => 'https://example.com/sticker.png' // URL أو base64
]);
```

---

## إدارة المحادثات

### التحقق من أرقام WhatsApp

```php
$result = $evolutionService->checkWhatsAppNumbers('my-instance', [
    '201234567890',
    '201111111111',
    '202222222222'
]);
```

### تحديد الرسائل كمقروءة

```php
$result = $evolutionService->markMessagesAsRead('my-instance', [
    [
        'remoteJid' => '201234567890@s.whatsapp.net',
        'fromMe' => false,
        'id' => 'MESSAGE_ID'
    ]
]);
```

### أرشفة محادثة

```php
$result = $evolutionService->archiveChat('my-instance', [
    'lastMessage' => [
        'key' => [
            'remoteJid' => '201234567890@s.whatsapp.net',
            'fromMe' => false,
            'id' => 'MESSAGE_ID'
        ]
    ],
    'chat' => '201234567890@s.whatsapp.net',
    'archive' => true // true للأرشفة، false لإلغاء الأرشفة
]);
```

### حذف رسالة للجميع

```php
$result = $evolutionService->deleteMessage('my-instance', [
    'id' => 'MESSAGE_ID',
    'remoteJid' => '201234567890@s.whatsapp.net',
    'fromMe' => true
]);
```

### الحصول على صورة الملف الشخصي

```php
$result = $evolutionService->fetchProfilePicture('my-instance', '201234567890');
```

### البحث عن جهات الاتصال

```php
$result = $evolutionService->findContacts('my-instance', [
    'id' => '201234567890@s.whatsapp.net' // اختياري
]);
```

### البحث عن الرسائل

```php
$result = $evolutionService->findMessages('my-instance', [
    'key' => [
        'remoteJid' => '201234567890@s.whatsapp.net'
    ]
], $page = 1, $offset = 10);
```

### الحصول على جميع المحادثات

```php
$result = $evolutionService->findChats('my-instance');
```

### حظر/إلغاء حظر رقم

```php
$result = $evolutionService->updateBlockStatus('my-instance', '201234567890', 'block'); // block أو unblock
```

### إرسال حالة الكتابة

```php
$result = $evolutionService->sendPresence('my-instance', [
    'number' => '201234567890',
    'delay' => 1200,
    'presence' => 'composing' // composing, recording, paused
]);
```

---

## إدارة المجموعات

### إنشاء مجموعة

```php
$result = $evolutionService->createGroup('my-instance',
    'اسم المجموعة',
    ['201111111111', '202222222222'],
    'وصف المجموعة' // اختياري
);
```

### تحديث صورة المجموعة

```php
$result = $evolutionService->updateGroupPicture('my-instance',
    'GROUP_JID@g.us',
    'https://example.com/image.png'
);
```

### تحديث اسم المجموعة

```php
$result = $evolutionService->updateGroupSubject('my-instance',
    'GROUP_JID@g.us',
    'اسم جديد للمجموعة'
);
```

### تحديث وصف المجموعة

```php
$result = $evolutionService->updateGroupDescription('my-instance',
    'GROUP_JID@g.us',
    'وصف جديد للمجموعة'
);
```

### الحصول على معلومات المجموعة

```php
$result = $evolutionService->findGroup('my-instance', 'GROUP_JID@g.us');
```

### الحصول على جميع المجموعات

```php
$result = $evolutionService->fetchAllGroups('my-instance', $getParticipants = true);
```

### إضافة/إزالة/ترقية/تخفيض أعضاء

```php
$result = $evolutionService->updateParticipant('my-instance',
    'GROUP_JID@g.us',
    'add', // add, remove, promote, demote
    ['201111111111', '202222222222']
);
```

### تحديث إعدادات المجموعة

```php
$result = $evolutionService->updateGroupSettings('my-instance',
    'GROUP_JID@g.us',
    'announcement' // announcement, not_announcement, locked, unlocked
);
```

### تفعيل/تعطيل الرسائل المؤقتة

```php
$result = $evolutionService->toggleEphemeral('my-instance',
    'GROUP_JID@g.us',
    86400 // 0 (إيقاف), 86400 (يوم), 604800 (أسبوع), 7776000 (90 يوم)
);
```

### مغادرة المجموعة

```php
$result = $evolutionService->leaveGroup('my-instance', 'GROUP_JID@g.us');
```

### الانضمام للمجموعة برابط الدعوة

```php
$result = $evolutionService->joinGroupWithCode('my-instance', 'INVITE_CODE');
```

### الحصول على رابط دعوة المجموعة

```php
$result = $evolutionService->getInviteCode('my-instance', 'GROUP_JID@g.us');
```

### إلغاء رابط دعوة المجموعة

```php
$result = $evolutionService->revokeInviteCode('my-instance', 'GROUP_JID@g.us');
```

---

## إدارة الملف الشخصي

### تحديث اسم الملف الشخصي

```php
$result = $evolutionService->updateProfileName('my-instance', 'الاسم الجديد');
```

### تحديث حالة الملف الشخصي

```php
$result = $evolutionService->updateProfileStatus('my-instance', 'الحالة الجديدة');
```

### تحديث صورة الملف الشخصي

```php
$result = $evolutionService->updateProfilePicture('my-instance', 'https://example.com/profile.png');
```

### إزالة صورة الملف الشخصي

```php
$result = $evolutionService->removeProfilePicture('my-instance');
```

### الحصول على إعدادات الخصوصية

```php
$result = $evolutionService->fetchPrivacySettings('my-instance');
```

### تحديث إعدادات الخصوصية

```php
$result = $evolutionService->updatePrivacySettings('my-instance', [
    'readreceipts' => 'all', // all, none
    'profile' => 'all', // all, contacts, contact_blacklist, none
    'status' => 'contacts', // all, contacts, contact_blacklist, none
    'online' => 'all', // all, match_last_seen
    'last' => 'contacts', // all, contacts, contact_blacklist, none
    'groupadd' => 'contacts' // all, contacts, contact_blacklist
]);
```

---

## الـ Webhooks

### تعيين Webhook

```php
$result = $evolutionService->setWebhook('my-instance', [
    'url' => 'https://your-domain.com/api/evolution/webhook',
    'byEvents' => false,
    'base64' => true,
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Content-Type' => 'application/json'
    ],
    'events' => [
        'APPLICATION_STARTUP',
        'QRCODE_UPDATED',
        'MESSAGES_SET',
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'MESSAGES_DELETE',
        'SEND_MESSAGE',
        'CONTACTS_SET',
        'CONTACTS_UPSERT',
        'CONTACTS_UPDATE',
        'PRESENCE_UPDATE',
        'CHATS_SET',
        'CHATS_UPSERT',
        'CHATS_UPDATE',
        'CHATS_DELETE',
        'GROUPS_UPSERT',
        'GROUP_UPDATE',
        'GROUP_PARTICIPANTS_UPDATE',
        'CONNECTION_UPDATE',
        'LABELS_EDIT',
        'LABELS_ASSOCIATION',
        'CALL',
        'TYPEBOT_START',
        'TYPEBOT_CHANGE_STATUS'
    ]
]);
```

### الحصول على إعدادات Webhook

```php
$result = $evolutionService->findWebhook('my-instance');
```

### معالجة Webhooks الواردة

تم إنشاء `WebhookController` الذي يعالج جميع أنواع الـ webhooks تلقائياً. يمكنك إضافة المسار في `routes/api.php`:

```php
Route::post('/evolution/webhook', [WebhookController::class, 'handle']);
```

---

## التكاملات

### Chatwoot

```php
// تعيين Chatwoot
$result = $evolutionService->setChatwoot('my-instance', [
    'chatwootAccountId' => '1',
    'chatwootToken' => 'YOUR_TOKEN',
    'chatwootUrl' => 'https://chatwoot.com',
    'chatwootSignMsg' => true,
    'chatwootReopenConversation' => true,
    'chatwootConversationPending' => false
]);

// الحصول على إعدادات Chatwoot
$result = $evolutionService->findChatwoot('my-instance');
```

### RabbitMQ

```php
// تعيين RabbitMQ
$result = $evolutionService->setRabbitmq('my-instance', [
    'enabled' => true,
    'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE']
]);

// الحصول على إعدادات RabbitMQ
$result = $evolutionService->findRabbitmq('my-instance');
```

### SQS

```php
// تعيين SQS
$result = $evolutionService->setSqs('my-instance', [
    'enabled' => true,
    'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE']
]);

// الحصول على إعدادات SQS
$result = $evolutionService->findSqs('my-instance');
```

### Typebot

```php
// تعيين Typebot
$result = $evolutionService->setTypebot('my-instance', [
    'enabled' => true,
    'url' => 'https://typebot.io',
    'typebot' => 'YOUR_TYPEBOT_ID'
]);

// بدء Typebot
$result = $evolutionService->startTypebot('my-instance', [
    'remoteJid' => '201234567890@s.whatsapp.net',
    'typebot' => 'YOUR_TYPEBOT_ID'
]);

// تغيير حالة Typebot
$result = $evolutionService->changeTypebotStatus('my-instance',
    '201234567890@s.whatsapp.net',
    'opened' // opened, closed, paused
);
```

---

## إعدادات الـ Instance

### تعيين الإعدادات

```php
$result = $evolutionService->setSettings('my-instance', [
    'rejectCall' => true,
    'msgCall' => 'عذراً، لا أقبل المكالمات',
    'groupsIgnore' => false,
    'alwaysOnline' => true,
    'readMessages' => false,
    'syncFullHistory' => false,
    'readStatus' => false
]);
```

### الحصول على الإعدادات

```php
$result = $evolutionService->findSettings('my-instance');
```

---

## Proxy

### تعيين Proxy

```php
$result = $evolutionService->setProxy('my-instance', [
    'enabled' => true,
    'host' => '0.0.0.0',
    'port' => '8000',
    'protocol' => 'http',
    'username' => 'user',
    'password' => 'pass'
]);
```

### الحصول على إعدادات Proxy

```php
$result = $evolutionService->findProxy('my-instance');
```

---

## معالجة الأخطاء

جميع الـ methods ترجع array بالشكل التالي:

```php
// في حالة النجاح
[
    'success' => true,
    'data' => [...] // البيانات المرجعة من API
]

// في حالة الفشل
[
    'success' => false,
    'error' => 'رسالة الخطأ',
    'status' => 400 // HTTP status code (اختياري)
]
```

مثال على معالجة الأخطاء:

```php
$result = $evolutionService->sendText('my-instance', [
    'number' => '201234567890',
    'text' => 'مرحباً'
]);

if ($result['success']) {
    // نجحت العملية
    $messageId = $result['data']['key']['id'];
    echo "تم إرسال الرسالة بنجاح: {$messageId}";
} else {
    // فشلت العملية
    $error = $result['error'];
    echo "حدث خطأ: {$error}";
}
```

---

## ملاحظات مهمة

1. **Instance Name**: يجب أن يكون اسم الـ instance فريداً لكل instance
2. **Remote JID**: أرقام الهواتف يجب أن تكون بصيغة `NUMBER@s.whatsapp.net` للمحادثات الفردية و `GROUP_ID@g.us` للمجموعات
3. **Media**: يمكن إرسال الوسائط عن طريق URL أو base64
4. **Webhooks**: تأكد من تعيين webhook URL صحيح لاستقبال الأحداث
5. **Logging**: جميع الأخطاء يتم تسجيلها في Laravel logs

---

## أمثلة عملية

### مثال 1: إنشاء instance وإرسال رسالة

```php
use App\Services\EvolutionService;

$service = new EvolutionService();

// 1. إنشاء instance
$createResult = $service->createInstance([
    'instanceName' => 'support-bot',
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS'
]);

if ($createResult['success']) {
    // 2. انتظار المسح الضوئي لـ QR Code
    // يمكنك عرض QR code للمستخدم
    $qrCode = $createResult['data']['qrcode']['base64'];

    // 3. بعد الاتصال، إرسال رسالة
    $sendResult = $service->sendText('support-bot', [
        'number' => '201234567890',
        'text' => 'مرحباً! تم تفعيل البوت بنجاح'
    ]);
}
```

### مثال 2: معالجة رسالة واردة من webhook

```php
// في WebhookController
protected function processMessages($data)
{
    $instanceName = $data['instance'] ?? null;
    $message = $data['data'];

    $key = $message['key'] ?? null;
    $messageContent = $message['message'] ?? null;

    if (!$key) return;

    $remoteJid = $key['remoteJid'];
    $fromMe = $key['fromMe'];
    $text = $this->extractMessageText($messageContent);

    // إذا كانت الرسالة ليست مني وتحتوي على نص
    if (!$fromMe && $text) {
        // الرد على الرسالة
        $this->evolutionService->sendText($instanceName, [
            'number' => str_replace('@s.whatsapp.net', '', $remoteJid),
            'text' => "شكراً على رسالتك: {$text}"
        ]);
    }
}
```

---

## الدعم

للمزيد من المعلومات، راجع:

-   [Evolution API Documentation](https://doc.evolution-api.com/)
-   [Evolution API GitHub](https://github.com/EvolutionAPI/evolution-api)
