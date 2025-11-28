# قاعدة بيانات نظام البوت - Database Schema Documentation

## نظرة عامة
هذا المستند يوثق بنية قاعدة البيانات الخاصة بنظام البوت الذي يدير حسابات Facebook وصفحاتها.

---

## 1️⃣ جدول المستخدمين – `users`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint, PK, auto_increment | المعرف الأساسي |
| name | varchar(255) | اسم المستخدم |
| email | varchar(255), unique | البريد الإلكتروني |
| password | varchar(255), nullable | كلمة المرور (مشفرة) |
| phone | varchar(50), nullable | رقم الهاتف (اختياري) |
| status | tinyint(1) | حالة الحساب (1=نشط، 0=معطل) |
| facebook_id | bigint, unique, nullable | معرف حساب فيسبوك |
| facebook_token | text, nullable | رمز الوصول لفيسبوك |
| selected_page_id | bigint, nullable | الصفحة المختارة |
| page_access_token | text, nullable | رمز الوصول للصفحة |
| is_bot_active | boolean | هل البوت نشط |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

## 2️⃣ جدول المحافظ – `wallets`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint, PK, auto_increment | معرف المحفظة |
| user_id | bigint, FK → users.id | صاحب المحفظة |
| currency | varchar(10) | نوع العملة (USD, EUR, ...) |
| balance | decimal(20,2) | الرصيد الحالي |
| status | tinyint(1) | حالة المحفظة (1=نشطة، 0=موقوفة) |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**ملاحظة:** كل مستخدم يمكن أن يمتلك أكثر من محفظة.

---

## 3️⃣ جدول حسابات فيسبوك – `facebook_accounts`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint, PK, auto_increment | معرف الحساب |
| user_id | bigint, FK → users.id | صاحب الحساب |
| facebook_user_id | varchar(100) | معرف حساب فيسبوك |
| name | varchar(255) | اسم الحساب |
| access_token | text | Long-Lived User Token |
| token_expires_at | datetime, nullable | تاريخ انتهاء الـ User Token |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**ملاحظة:** كل مستخدم يمكن أن يمتلك عدة حسابات فيسبوك.

---

## 4️⃣ جدول صفحات فيسبوك – `facebook_pages`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint, PK, auto_increment | معرف الصفحة |
| facebook_account_id | bigint, FK → facebook_accounts.id | الحساب المرتبط |
| page_id | varchar(100) | معرف الصفحة في فيسبوك |
| name | varchar(255) | اسم الصفحة |
| access_token | text | Page Access Token (طويل المدى) |
| category | varchar(100), nullable | فئة الصفحة |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**ملاحظة:** كل حساب فيسبوك يمكن أن يمتلك عدة صفحات.

---

## 5️⃣ جدول القوالب (Templates) – `auto_reply_templates`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint, PK, auto_increment | معرف القالب |
| user_id | bigint, FK → users.id | صاحب القالب |
| page_id | bigint, FK → facebook_pages.id | الصفحة المرتبطة بالقالب |
| type | enum('comment','message','post') | نوع القالب |
| name | varchar(255) | اسم القالب |
| content | text | نص الرد أو محتوى المنشور |
| media_url | text, nullable | رابط صورة أو فيديو مرفق |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**استخدام:** يستخدمه البوت للرد الآلي على الرسائل أو التعليقات أو للنشر.

---

## 6️⃣ جدول الردود الآلية – `auto_replies`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint, PK, auto_increment | معرف الرد |
| template_id | bigint, FK → auto_reply_templates.id | القالب المستخدم |
| trigger_type | enum('comment','message','post') | نوع الحدث الذي يفعّل الرد |
| trigger_keyword | varchar(255), nullable | كلمة مفتاحية للرد المحدد |
| page_id | bigint, FK → facebook_pages.id | الصفحة المرتبطة |
| active | tinyint(1) | 1=مفعل، 0=غير مفعل |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

**استخدام:** يربط القوالب بالصفحات والأحداث الفعلية.

---

## 7️⃣ جدول المنشورات المجدولة – `scheduled_posts`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint, PK, auto_increment | معرف المنشور |
| page_id | bigint, FK → facebook_pages.id | الصفحة المرتبطة |
| template_id | bigint, FK → auto_reply_templates.id, nullable | إذا كان منشور مبني على قالب |
| content | text | نص المنشور |
| media_url | text, nullable | رابط الصورة أو الفيديو |
| scheduled_at | datetime | وقت النشر المجدول |
| posted | tinyint(1) | 0=لم يتم النشر، 1=تم النشر |
| created_at | timestamp | تاريخ الإنشاء |
| updated_at | timestamp | تاريخ التحديث |

---

## 8️⃣ جدول سجلات الأحداث – `logs`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| id | bigint, PK, auto_increment | معرف السجل |
| page_id | bigint, FK → facebook_pages.id | الصفحة المرتبطة |
| type | enum('comment','message','post','error') | نوع الحدث |
| reference_id | varchar(255), nullable | معرف الرسالة أو التعليق أو المنشور |
| content | text, nullable | نص الحدث أو الخطأ |
| created_at | timestamp | وقت الحدث |

---

## العلاقات بين الجداول

```
users (1) ─────→ (N) wallets
users (1) ─────→ (N) facebook_accounts
users (1) ─────→ (N) auto_reply_templates

facebook_accounts (1) ─────→ (N) facebook_pages

facebook_pages (1) ─────→ (N) auto_reply_templates
facebook_pages (1) ─────→ (N) auto_replies
facebook_pages (1) ─────→ (N) scheduled_posts
facebook_pages (1) ─────→ (N) logs

auto_reply_templates (1) ─────→ (N) auto_replies
auto_reply_templates (1) ─────→ (N) scheduled_posts
```

---

## ملاحظات هامة

1. **Foreign Keys:** جميع الجداول تستخدم Foreign Keys مع `onDelete('cascade')` لضمان حذف البيانات المرتبطة عند حذف السجل الرئيسي.

2. **Indexes:** تم إضافة Indexes على الحقول المستخدمة بكثرة في الاستعلامات مثل:
   - `facebook_user_id` في جدول `facebook_accounts`
   - `page_id` في جدول `facebook_pages`
   - `(page_id, type, created_at)` في جدول `logs`
   - `(scheduled_at, posted)` في جدول `scheduled_posts`

3. **Timestamps:** جميع الجداول تستخدم `created_at` و `updated_at` لتتبع التغييرات.

4. **Security:** رموز الوصول (access tokens) يتم تخزينها كـ `text` لتتسع للرموز الطويلة.

---

تاريخ إنشاء الوثيقة: 2025-11-28
