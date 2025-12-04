# أمثلة على Logs من Evolution API Webhook

## مثال 1: استقبال رسالة نصية

### البيانات الواردة من Webhook

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "my_whatsapp_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "3EB0123456789ABCDEF"
                },
                "messageTimestamp": 1701709085,
                "pushName": "أحمد محمد",
                "message": {
                    "conversation": "مرحباً، أريد الاستفسار عن خدماتكم"
                }
            }
        ]
    }
}
```

### Logs المسجلة

```
[2025-12-04 19:38:07] local.INFO: === Evolution Webhook Received === {
    "timestamp": "2025-12-04 19:38:07",
    "event": "MESSAGES_UPSERT",
    "instance": "my_whatsapp_instance",
    "full_data": {...}
}

[2025-12-04 19:38:07] local.INFO: 📨 New Message Event {
    "instance": "my_whatsapp_instance",
    "timestamp": "2025-12-04 19:38:07"
}

[2025-12-04 19:38:07] local.INFO: 💬 Message Details {
    "instance": "my_whatsapp_instance",
    "message_id": "3EB0123456789ABCDEF",
    "sender": "218921234567@s.whatsapp.net",
    "receiver": "Me (Bot)",
    "sender_name": "أحمد محمد",
    "from_me": false,
    "message_type": "text",
    "timestamp": "2025-12-04 18:11:25",
    "content": "مرحباً، أريد الاستفسار عن خدماتكم",
    "media_info": null
}

[2025-12-04 19:38:07] local.INFO: 📝 Text Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "text": "مرحباً، أريد الاستفسار عن خدماتكم"
}
```

---

## مثال 2: استقبال صورة مع تعليق

### البيانات الواردة

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "my_whatsapp_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "3EB0987654321FEDCBA"
                },
                "messageTimestamp": 1701709100,
                "pushName": "فاطمة علي",
                "message": {
                    "imageMessage": {
                        "caption": "هذه صورة المنتج الذي أريده",
                        "mimetype": "image/jpeg",
                        "fileLength": 245678,
                        "height": 1920,
                        "width": 1080
                    }
                }
            }
        ]
    }
}
```

### Logs المسجلة

```
[2025-12-04 19:38:22] local.INFO: 💬 Message Details {
    "instance": "my_whatsapp_instance",
    "message_id": "3EB0987654321FEDCBA",
    "sender": "218921234567@s.whatsapp.net",
    "receiver": "Me (Bot)",
    "sender_name": "فاطمة علي",
    "from_me": false,
    "message_type": "image",
    "timestamp": "2025-12-04 18:11:40",
    "content": "هذه صورة المنتج الذي أريده",
    "media_info": {
        "mimetype": "image/jpeg",
        "fileLength": 245678,
        "height": 1920,
        "width": 1080
    }
}

[2025-12-04 19:38:22] local.INFO: 🖼️ Image Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "caption": "هذه صورة المنتج الذي أريده",
    "mimetype": "image/jpeg",
    "file_size": 245678
}
```

---

## مثال 3: استقبال رسالة صوتية (Voice Note)

### البيانات الواردة

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "my_whatsapp_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "3EB0ABCDEF123456789"
                },
                "messageTimestamp": 1701709150,
                "pushName": "خالد سعيد",
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

### Logs المسجلة

```
[2025-12-04 19:39:12] local.INFO: 💬 Message Details {
    "instance": "my_whatsapp_instance",
    "message_id": "3EB0ABCDEF123456789",
    "sender": "218921234567@s.whatsapp.net",
    "receiver": "Me (Bot)",
    "sender_name": "خالد سعيد",
    "from_me": false,
    "message_type": "audio",
    "timestamp": "2025-12-04 18:12:30",
    "content": null,
    "media_info": {
        "mimetype": "audio/ogg; codecs=opus",
        "fileLength": 45678,
        "seconds": 23,
        "ptt": true
    }
}

[2025-12-04 19:39:12] local.INFO: 🎵 Audio Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "duration": 23,
    "is_ptt": true,
    "mimetype": "audio/ogg; codecs=opus"
}
```

---

## مثال 4: استقبال مستند PDF

### البيانات الواردة

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "my_whatsapp_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "3EB0FEDCBA987654321"
                },
                "messageTimestamp": 1701709200,
                "pushName": "سارة أحمد",
                "message": {
                    "documentMessage": {
                        "caption": "هذا هو العقد الموقع",
                        "fileName": "contract_signed.pdf",
                        "mimetype": "application/pdf",
                        "fileLength": 1234567
                    }
                }
            }
        ]
    }
}
```

### Logs المسجلة

```
[2025-12-04 19:40:02] local.INFO: 💬 Message Details {
    "instance": "my_whatsapp_instance",
    "message_id": "3EB0FEDCBA987654321",
    "sender": "218921234567@s.whatsapp.net",
    "receiver": "Me (Bot)",
    "sender_name": "سارة أحمد",
    "from_me": false,
    "message_type": "document",
    "timestamp": "2025-12-04 18:13:20",
    "content": "هذا هو العقد الموقع",
    "media_info": {
        "fileName": "contract_signed.pdf",
        "mimetype": "application/pdf",
        "fileLength": 1234567
    }
}

[2025-12-04 19:40:02] local.INFO: 📄 Document Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "filename": "contract_signed.pdf",
    "mimetype": "application/pdf",
    "file_size": 1234567,
    "caption": "هذا هو العقد الموقع"
}
```

---

## مثال 5: استقبال موقع جغرافي

### البيانات الواردة

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "my_whatsapp_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "3EB0123ABC456DEF789"
                },
                "messageTimestamp": 1701709250,
                "pushName": "محمد حسن",
                "message": {
                    "locationMessage": {
                        "degreesLatitude": 32.8872,
                        "degreesLongitude": 13.1913,
                        "address": "طرابلس، ليبيا"
                    }
                }
            }
        ]
    }
}
```

### Logs المسجلة

```
[2025-12-04 19:40:52] local.INFO: 💬 Message Details {
    "instance": "my_whatsapp_instance",
    "message_id": "3EB0123ABC456DEF789",
    "sender": "218921234567@s.whatsapp.net",
    "receiver": "Me (Bot)",
    "sender_name": "محمد حسن",
    "from_me": false,
    "message_type": "location",
    "timestamp": "2025-12-04 18:14:10",
    "content": "طرابلس، ليبيا",
    "media_info": {
        "latitude": 32.8872,
        "longitude": 13.1913
    }
}

[2025-12-04 19:40:52] local.INFO: 📍 Location Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "latitude": 32.8872,
    "longitude": 13.1913,
    "address": "طرابلس، ليبيا"
}
```

---

## مثال 6: تحديث حالة الاتصال (Connection Update)

### البيانات الواردة

```json
{
    "event": "CONNECTION_UPDATE",
    "instance": "my_whatsapp_instance",
    "data": {
        "state": "open"
    }
}
```

### Logs المسجلة

```
[2025-12-04 19:35:00] local.INFO: 🔌 Connection State Changed {
    "instance": "my_whatsapp_instance",
    "state": "open",
    "status": "connected",
    "timestamp": "2025-12-04 19:35:00"
}

[2025-12-04 19:35:00] local.INFO: ✅ Connection status updated in database {
    "instance": "my_whatsapp_instance",
    "status": "connected"
}
```

---

## مثال 7: تحديث QR Code

### البيانات الواردة

```json
{
    "event": "QRCODE_UPDATED",
    "instance": "my_whatsapp_instance",
    "data": {
        "qrcode": {
            "base64": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
        }
    }
}
```

### Logs المسجلة

```
[2025-12-04 19:30:00] local.INFO: 🔲 QR Code Updated {
    "instance": "my_whatsapp_instance",
    "has_qrcode": true,
    "timestamp": "2025-12-04 19:30:00"
}

[2025-12-04 19:30:00] local.INFO: ✅ QR Code saved to database {
    "instance": "my_whatsapp_instance"
}
```

---

## مثال 8: استقبال فيديو

### البيانات الواردة

```json
{
    "event": "MESSAGES_UPSERT",
    "instance": "my_whatsapp_instance",
    "data": {
        "messages": [
            {
                "key": {
                    "remoteJid": "218921234567@s.whatsapp.net",
                    "fromMe": false,
                    "id": "3EB0VIDEO123456789"
                },
                "messageTimestamp": 1701709300,
                "pushName": "ليلى عمر",
                "message": {
                    "videoMessage": {
                        "caption": "شاهد هذا الفيديو التوضيحي",
                        "mimetype": "video/mp4",
                        "fileLength": 5678901,
                        "seconds": 45,
                        "height": 1920,
                        "width": 1080
                    }
                }
            }
        ]
    }
}
```

### Logs المسجلة

```
[2025-12-04 19:41:42] local.INFO: 💬 Message Details {
    "instance": "my_whatsapp_instance",
    "message_id": "3EB0VIDEO123456789",
    "sender": "218921234567@s.whatsapp.net",
    "receiver": "Me (Bot)",
    "sender_name": "ليلى عمر",
    "from_me": false,
    "message_type": "video",
    "timestamp": "2025-12-04 18:15:00",
    "content": "شاهد هذا الفيديو التوضيحي",
    "media_info": {
        "mimetype": "video/mp4",
        "fileLength": 5678901,
        "seconds": 45,
        "height": 1920,
        "width": 1080
    }
}

[2025-12-04 19:41:42] local.INFO: 🎥 Video Message {
    "from": "218921234567@s.whatsapp.net",
    "to": "Me (Bot)",
    "caption": "شاهد هذا الفيديو التوضيحي",
    "mimetype": "video/mp4",
    "duration": 45,
    "file_size": 5678901
}
```

---

## مثال 9: رسالة من البوت (fromMe: true)

### البيانات الواردة

```json
{
    "event": "SEND_MESSAGE",
    "instance": "my_whatsapp_instance",
    "data": {
        "key": {
            "remoteJid": "218921234567@s.whatsapp.net",
            "fromMe": true,
            "id": "3EB0BOTMSG123456789"
        },
        "messageTimestamp": 1701709350,
        "message": {
            "conversation": "شكراً لتواصلك معنا، سنرد عليك قريباً"
        }
    }
}
```

### Logs المسجلة

```
[2025-12-04 19:42:32] local.INFO: 📤 Message Sent {
    "instance": "my_whatsapp_instance",
    "message_data": {...},
    "timestamp": "2025-12-04 19:42:32"
}

[2025-12-04 19:42:32] local.INFO: 💬 Message Details {
    "instance": "my_whatsapp_instance",
    "message_id": "3EB0BOTMSG123456789",
    "sender": "Me (Bot)",
    "receiver": "218921234567@s.whatsapp.net",
    "sender_name": "Unknown",
    "from_me": true,
    "message_type": "text",
    "timestamp": "2025-12-04 18:15:50",
    "content": "شكراً لتواصلك معنا، سنرد عليك قريباً",
    "media_info": null
}

[2025-12-04 19:42:32] local.INFO: 📝 Text Message {
    "from": "Me (Bot)",
    "to": "218921234567@s.whatsapp.net",
    "text": "شكراً لتواصلك معنا، سنرد عليك قريباً"
}
```

---

## كيفية مراقبة الـ Logs

### 1. مراقبة مباشرة (Real-time)

```bash
php artisan tail
```

### 2. مراقبة ملف Log محدد

```bash
tail -f storage/logs/laravel.log
```

### 3. البحث في الـ Logs

```bash
# البحث عن رسائل معينة
grep "💬 Message Details" storage/logs/laravel.log

# البحث عن رسائل من مرسل معين
grep "218921234567" storage/logs/laravel.log

# البحث عن نوع رسالة معين
grep "🖼️ Image Message" storage/logs/laravel.log
```

### 4. عرض آخر 100 سطر

```bash
tail -n 100 storage/logs/laravel.log
```

---

## الرموز التعبيرية (Emojis) المستخدمة

| Emoji | النوع           | الوصف              |
| ----- | --------------- | ------------------ |
| 📱    | Application     | بدء التطبيق        |
| 🔲    | QR Code         | تحديث رمز QR       |
| 🔌    | Connection      | تغيير حالة الاتصال |
| 📦    | Messages Set    | تحميل رسائل أولية  |
| 📨    | New Message     | رسالة جديدة        |
| 📤    | Sent Message    | رسالة مرسلة        |
| 🔄    | Update          | تحديث              |
| 🗑️    | Delete          | حذف                |
| 💬    | Message         | تفاصيل الرسالة     |
| 📝    | Text            | رسالة نصية         |
| 🖼️    | Image           | صورة               |
| 🎥    | Video           | فيديو              |
| 🎵    | Audio           | صوت                |
| 📄    | Document        | مستند              |
| 🎭    | Sticker         | ملصق               |
| 📍    | Location        | موقع               |
| 👤    | Contact         | جهة اتصال          |
| 👥    | Contacts/Groups | جهات اتصال/مجموعات |
| 👁️    | Presence        | حالة الحضور        |
| 🏷️    | Labels          | تصنيفات            |
| 📞    | Call            | مكالمة             |
| 🤖    | Typebot         | بوت                |
| ⚠️    | Warning         | تحذير              |
| ❓    | Unknown         | غير معروف          |
| ✅    | Success         | نجاح               |
