# 🧪 دليل اختبار Webhook

## كيفية اختبار Webhook Handler

### 1. الإعداد الأولي

تأكد من أن لديك:

-   ✅ Evolution API يعمل
-   ✅ Instance مُنشأ ومتصل
-   ✅ Webhook URL مُعرّف في `.env`

```env
EVOLUTION_WEBHOOK_URL=https://your-domain.com/api/evolution/webhook
```

---

## 2. اختبار باستخدام Postman/Insomnia

### اختبار رسالة نصية

**Endpoint:** `POST http://localhost:8000/api/evolution/webhook`

**Headers:**

```json
{
    "Content-Type": "application/json"
}
```

**Body:**

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "test_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "TEST123456789"
                },
                "messageTimestamp": 1701709085,
                "pushName": "اختبار المستخدم",
                "message": {
                    "conversation": "هذه رسالة اختبار"
                }
            }
        ]
    }
}
```

**النتيجة المتوقعة:**

```json
{
    "data": "ok",
    "status": 200
}
```

**Logs المتوقعة:**

```
[2025-12-04 19:38:07] local.INFO: === Evolution Webhook Received ===
[2025-12-04 19:38:07] local.INFO: 📨 New Message Event
[2025-12-04 19:38:07] local.INFO: 💬 Message Details
[2025-12-04 19:38:07] local.INFO: 📝 Text Message
```

---

### اختبار رسالة صورة

**Body:**

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "test_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "TEST_IMAGE_123"
                },
                "messageTimestamp": 1701709100,
                "pushName": "اختبار المستخدم",
                "message": {
                    "imageMessage": {
                        "caption": "صورة اختبار",
                        "mimetype": "image/jpeg",
                        "fileLength": 123456,
                        "height": 1920,
                        "width": 1080
                    }
                }
            }
        ]
    }
}
```

**Logs المتوقعة:**

```
[2025-12-04 19:38:22] local.INFO: 🖼️ Image Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "caption": "صورة اختبار",
    "mimetype": "image/jpeg",
    "file_size": 123456
}
```

---

### اختبار رسالة صوتية

**Body:**

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "test_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "TEST_AUDIO_123"
                },
                "messageTimestamp": 1701709150,
                "pushName": "اختبار المستخدم",
                "message": {
                    "audioMessage": {
                        "mimetype": "audio/ogg; codecs=opus",
                        "fileLength": 45678,
                        "seconds": 23,
                        "ptt": true
                    }
                }
            }
        ]
    }
}
```

**Logs المتوقعة:**

```
[2025-12-04 19:39:12] local.INFO: 🎵 Audio Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "duration": 23,
    "is_ptt": true,
    "mimetype": "audio/ogg; codecs=opus"
}
```

---

### اختبار تحديث الاتصال

**Body:**

```json
{
    "event": "CONNECTION_UPDATE",
    "instance": "test_instance",
    "data": {
        "state": "open"
    }
}
```

**Logs المتوقعة:**

```
[2025-12-04 19:35:00] local.INFO: 🔌 Connection State Changed {
    "instance": "test_instance",
    "state": "open",
    "status": "connected"
}
```

---

### اختبار تحديث QR Code

**Body:**

```json
{
    "event": "QRCODE_UPDATED",
    "instance": "test_instance",
    "data": {
        "qrcode": {
            "base64": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
        }
    }
}
```

**Logs المتوقعة:**

```
[2025-12-04 19:30:00] local.INFO: 🔲 QR Code Updated {
    "instance": "test_instance",
    "has_qrcode": true
}
```

---

## 3. اختبار مع WhatsApp حقيقي

### الخطوات:

1. **إنشاء Instance**

    ```bash
    # من خلال API أو Dashboard
    ```

2. **مسح QR Code**

    - افتح WhatsApp على هاتفك
    - امسح QR Code

3. **إرسال رسالة**

    - أرسل رسالة نصية إلى رقم Instance
    - راقب Logs

4. **مراقبة Logs**

    ```bash
    php artisan tail
    ```

5. **التحقق من النتائج**
    - يجب أن ترى Logs مفصلة
    - تحقق من نوع الرسالة
    - تحقق من المرسل والمستلم

---

## 4. اختبار أنواع مختلفة من الرسائل

### قائمة الاختبارات:

-   [ ] رسالة نصية عادية
-   [ ] رسالة نصية مع emoji
-   [ ] رسالة نصية مع رابط
-   [ ] صورة بدون تعليق
-   [ ] صورة مع تعليق
-   [ ] فيديو
-   [ ] رسالة صوتية (Voice Note)
-   [ ] ملف صوتي عادي
-   [ ] مستند PDF
-   [ ] مستند Word
-   [ ] ملصق (Sticker)
-   [ ] موقع جغرافي
-   [ ] جهة اتصال
-   [ ] رد فعل (Reaction)
-   [ ] رسالة من البوت (fromMe: true)

---

## 5. اختبار معالجة الأخطاء

### اختبار 1: Webhook بدون event

**Body:**

```json
{
    "instance": "test_instance",
    "data": {}
}
```

**النتيجة المتوقعة:**

```json
{
    "data": "No event type provided",
    "status": 400
}
```

### اختبار 2: Webhook بـ event غير معروف

**Body:**

```json
{
    "event": "UNKNOWN_EVENT",
    "instance": "test_instance",
    "data": {}
}
```

**Logs المتوقعة:**

```
[2025-12-04 19:38:07] local.WARNING: No handler for event type {
    "event": "UNKNOWN_EVENT",
    "method_attempted": "handleUnknownEvent"
}
```

### اختبار 3: رسالة بدون key

**Body:**

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "test_instance",
    "data": {
        "messages": [
            {
                "message": {
                    "conversation": "رسالة بدون key"
                }
            }
        ]
    }
}
```

**Logs المتوقعة:**

```
[2025-12-04 19:38:07] local.WARNING: ⚠️ Message without key received
```

---

## 6. أدوات المراقبة

### مراقبة Logs في الوقت الفعلي

```bash
# الطريقة 1: Laravel Tail
php artisan tail

# الطريقة 2: tail command
tail -f storage/logs/laravel.log

# الطريقة 3: مع تصفية
tail -f storage/logs/laravel.log | grep "💬"
```

### البحث في Logs

```bash
# البحث عن رسائل اليوم
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log

# البحث عن رسائل من instance معين
grep "test_instance" storage/logs/laravel.log

# البحث عن نوع رسالة معين
grep "🖼️ Image Message" storage/logs/laravel.log

# عد عدد الرسائل
grep "💬 Message Details" storage/logs/laravel.log | wc -l
```

---

## 7. نصائح للاختبار

### ✅ Do's (افعل)

-   اختبر كل نوع رسالة على حدة
-   راقب Logs أثناء الاختبار
-   احفظ أمثلة من Webhook payloads
-   اختبر مع بيانات حقيقية

### ❌ Don'ts (لا تفعل)

-   لا ترسل رسائل spam
-   لا تختبر على production مباشرة
-   لا تتجاهل الأخطاء في Logs
-   لا تنسى تنظيف Logs القديمة

---

## 8. استكشاف الأخطاء

### المشكلة: Webhook لا يستقبل البيانات

**الحلول:**

1. تحقق من Webhook URL في Evolution API
2. تأكد من أن الـ URL قابل للوصول
3. تحقق من Firewall settings
4. استخدم ngrok للاختبار المحلي

### المشكلة: Logs لا تظهر

**الحلول:**

1. تحقق من `config/logging.php`
2. تأكد من وجود مجلد `storage/logs`
3. تحقق من صلاحيات المجلد
4. راجع `EVOLUTION_LOGGING_ENABLED` في `.env`

### المشكلة: رسائل لا تُعالج

**الحلول:**

1. راجع Logs للأخطاء
2. تحقق من نوع الرسالة
3. تأكد من أن Instance موجود في قاعدة البيانات
4. راجع `extractMessageInfo()` method

---

## 9. أمثلة على cURL

### اختبار رسالة نصية

```bash
curl -X POST http://localhost:8000/api/evolution/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "MESSAGES_UPSERT",
    "instance": "test_instance",
    "data": {
      "messages": [{
        "key": {
          "remoteJid": "218921234567@s.whatsapp.net",
          "fromMe": false,
          "id": "TEST123"
        },
        "messageTimestamp": 1701709085,
        "pushName": "Test User",
        "message": {
          "conversation": "Hello World"
        }
      }]
    }
  }'
```

### اختبار Connection Update

```bash
curl -X POST http://localhost:8000/api/evolution/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "CONNECTION_UPDATE",
    "instance": "test_instance",
    "data": {
      "state": "open"
    }
  }'
```

---

## 10. Checklist النهائي

قبل الانتقال إلى Production:

-   [ ] اختبار جميع أنواع الرسائل
-   [ ] اختبار معالجة الأخطاء
-   [ ] مراجعة Logs
-   [ ] اختبار مع بيانات حقيقية
-   [ ] التحقق من الأداء
-   [ ] مراجعة الأمان
-   [ ] تنظيف Logs القديمة
-   [ ] توثيق أي مشاكل واجهتها

---

تم إنشاؤه بواسطة: **Antigravity AI** 🤖
