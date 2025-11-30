# 🚀 دليل نظام Jobs للردود التلقائية مع Rate Limiting

## 📋 نظرة عامة

تم إنشاء نظام متقدم للردود التلقائية باستخدام **Laravel Jobs** و **Queues** للتعامل مع:
- ✅ معالجة آلاف التعليقات بشكل متزامن
- ✅ احترام حدود Facebook API (Rate Limiting)
- ✅ تأجيل الطلبات تلقائياً عند الوصول للحد الأقصى
- ✅ أولوية للمستخدمين المميزين
- ✅ عدم إيقاف البوت تحت الضغط

---

## 🎯 المشكلة والحل

### المشكلة
- Facebook API يسمح بـ **200 طلب/ساعة** لكل Page Access Token
- كل رد تلقائي قد يستخدم **1-3 طلبات**:
  1. إعجاب بالتعليق (1 طلب)
  2. الرد على التعليق (1 طلب)
  3. إرسال رسالة خاصة (1 طلب)
- مع 10 مستخدمين = **2000 طلب/ساعة** محتملة
- خطر تجاوز الحد وإيقاف البوت

### الحل
1. **Queue System**: معالجة غير متزامنة
2. **Rate Limiting**: تتبع الطلبات لكل مستخدم
3. **Auto Delay**: تأجيل تلقائي عند الوصول للحد
4. **Priority System**: أولوية للمستخدمين المميزين
5. **Safe Window**: ساعة وربع (75 دقيقة) بدلاً من ساعة

---

## 🏗️ البنية المعمارية

```
Facebook Webhook
       ↓
FacebookWebhookController (رد فوري خلال 20 ثانية)
       ↓
ProcessAutoReplyJob → Queue
       ↓
Rate Limit Check (200 طلب/ساعة)
       ↓
   [نعم: تنفيذ]  [لا: تأجيل 75 دقيقة]
       ↓
Execute Auto Reply
       ↓
Update Counters
```

---

## ⚙️ إعداد النظام

### 1. تكوين Queue Driver

في ملف `.env`:

```env
# للتطوير (استخدم database)
QUEUE_CONNECTION=database

# للإنتاج (استخدم Redis - أسرع وأفضل)
QUEUE_CONNECTION=redis

# إعدادات Redis (إذا استخدمت redis)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 2. إنشاء جدول Jobs (إذا استخدمت database)

```bash
php artisan queue:table
php artisan migrate
```

### 3. تشغيل Queue Worker

#### للتطوير:
```bash
php artisan queue:work --queue=auto-replies --tries=3 --timeout=120
```

#### للإنتاج (مع Supervisor):
```bash
php artisan queue:work --queue=auto-replies --tries=3 --timeout=120 --sleep=3 --max-jobs=1000 --max-time=3600
```

---

## 📊 كيف يعمل Rate Limiting

### نظام العد

```php
// مفتاح التخزين المؤقت
$rateLimitKey = "rate_limit:user:{$userId}:" . now()->format('Y-m-d-H');
// مثال: rate_limit:user:1:2024-12-01-14

// الحد الأقصى
$maxRequestsPerHour = 200; // عادي
$maxRequestsPerHour = 400; // مع ميزة الأولوية

// التحقق
if ($requestCount >= $maxRequestsPerHour) {
    // تأجيل الـ Job لمدة 75 دقيقة
    $this->release(75 * 60);
}
```

### مثال عملي

| الوقت | الطلبات | الحالة |
|-------|---------|--------|
| 14:00 | 0 | ✅ تنفيذ |
| 14:15 | 50 | ✅ تنفيذ |
| 14:30 | 150 | ✅ تنفيذ |
| 14:45 | 200 | ⏸️ تأجيل إلى 16:00 |
| 15:00 | 200 | ⏸️ لا يزال محظور |
| 15:15 | 0 (ساعة جديدة) | ✅ تنفيذ |

---

## 🎨 مميزات النظام

### 1. الرد الفوري لـ Facebook
```php
// في FacebookWebhookController
public function receive(Request $request)
{
    // معالجة سريعة
    $this->handleFeedChange($data);
    
    // رد فوري (خلال 20 ثانية)
    return response("OK", 200);
}
```

### 2. معالجة غير متزامنة
```php
// إرسال إلى Queue
ProcessAutoReplyJob::dispatch(...)
    ->delay(now()->addSeconds($delay));
```

### 3. نظام الأولوية
```php
if ($user->hasFeature('priority_processing')) {
    $delay = 0; // فوري
    $maxRequestsPerHour = 400; // حد أعلى
} else {
    $delay = rand(5, 10); // تأخير بسيط
    $maxRequestsPerHour = 200; // حد عادي
}
```

### 4. التحقق من القيود
```php
// في الـ Job
if (!$user->hasActiveSubscription()) {
    return; // إيقاف
}

if (!$user->canSendAutoReply()) {
    return; // وصل للحد الشهري
}
```

### 5. التأجيل التلقائي
```php
if ($requestCount >= $maxRequestsPerHour) {
    // تأجيل 75 دقيقة (ساعة وربع)
    $this->release(75 * 60);
    return;
}
```

### 6. الانتظار بين الطلبات
```php
// انتظار 1-2 ثانية بين كل طلب
usleep(rand(1000000, 2000000));
```

---

## 🔧 إعداد Supervisor (للإنتاج)

### ملف الإعداد

إنشاء ملف `/etc/supervisor/conf.d/supportly-worker.conf`:

```ini
[program:supportly-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/supportly/artisan queue:work --queue=auto-replies --tries=3 --timeout=120 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/supportly/storage/logs/worker.log
stopwaitsecs=3600
```

### تشغيل Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start supportly-worker:*
```

### مراقبة Workers

```bash
# حالة Workers
sudo supervisorctl status

# إعادة تشغيل
sudo supervisorctl restart supportly-worker:*

# إيقاف
sudo supervisorctl stop supportly-worker:*
```

---

## 📈 السيناريوهات المختلفة

### سيناريو 1: مستخدم واحد، 100 تعليق/ساعة

```
- كل رد = 3 طلبات (إعجاب + رد + رسالة)
- إجمالي = 300 طلب/ساعة
- النتيجة: ❌ سيتجاوز الحد (200)
- الحل: 
  * أول 66 تعليق → تنفيذ فوري (198 طلب)
  * باقي 34 تعليق → تأجيل للساعة التالية
```

### سيناريو 2: 10 مستخدمين، 20 تعليق/ساعة لكل واحد

```
- كل مستخدم = 20 تعليق × 3 طلبات = 60 طلب/ساعة
- إجمالي = 600 طلب/ساعة (موزعة على 10 مستخدمين)
- النتيجة: ✅ كل مستخدم تحت الحد (60 < 200)
- الحل: تنفيذ فوري للجميع
```

### سيناريو 3: مستخدم مميز (Priority)

```
- الحد الأقصى = 400 طلب/ساعة
- التأخير = 0 ثانية
- الأولوية = عالية
- النتيجة: ✅ معالجة أسرع وحد أعلى
```

---

## 🛠️ الأوامر المفيدة

### مراقبة Queue

```bash
# عرض Jobs المنتظرة
php artisan queue:monitor auto-replies

# عرض Jobs الفاشلة
php artisan queue:failed

# إعادة محاولة Job فاشل
php artisan queue:retry {job-id}

# إعادة محاولة جميع Jobs الفاشلة
php artisan queue:retry all

# حذف Jobs الفاشلة
php artisan queue:flush
```

### تنظيف Cache

```bash
# حذف Rate Limit Cache
php artisan cache:forget rate_limit:user:*

# حذف كل Cache
php artisan cache:clear
```

### اختبار النظام

```bash
# تشغيل Worker في وضع Verbose
php artisan queue:work --queue=auto-replies --verbose

# تشغيل Job واحد فقط
php artisan queue:work --queue=auto-replies --once
```

---

## 📊 مراقبة الأداء

### Logs مهمة

```php
// في ProcessAutoReplyJob
Log::info("Auto-reply completed successfully", [
    'comment_id' => $commentId,
    'requests_made' => $requestsMade,
    'total_requests_this_hour' => $requestCount + $requestsMade
]);

Log::warning("Rate limit reached, job delayed", [
    'user_id' => $userId,
    'requests' => $requestCount,
    'max' => $maxRequestsPerHour
]);
```

### مراقبة في Real-time

```bash
# متابعة Logs
tail -f storage/logs/laravel.log | grep "Auto-reply"

# عرض Worker logs
tail -f storage/logs/worker.log
```

---

## ⚠️ ملاحظات مهمة

### 1. الرد السريع لـ Facebook
- يجب الرد خلال **20 ثانية** وإلا سيعتبر الـ webhook فاشل
- لذلك نرسل الـ Job للـ Queue ونرد فوراً

### 2. نافذة الأمان (75 دقيقة)
- Facebook يحسب الحد كل ساعة
- نستخدم 75 دقيقة لنكون آمنين
- يضمن عدم تجاوز الحد

### 3. عدد Workers
- للتطوير: 1 worker كافي
- للإنتاج: 2-4 workers حسب الحمل
- لا تزيد عن 8 workers (استهلاك موارد)

### 4. Redis vs Database
- **Database**: سهل للتطوير
- **Redis**: أسرع للإنتاج (مُوصى به)

### 5. Timeout
- Job timeout = 120 ثانية
- كافي لـ 3 طلبات + انتظار

---

## 🎯 الخلاصة

| الميزة | القيمة |
|--------|--------|
| الحد الأقصى/ساعة (عادي) | 200 طلب |
| الحد الأقصى/ساعة (مميز) | 400 طلب |
| نافذة الأمان | 75 دقيقة |
| التأخير بين الطلبات | 1-2 ثانية |
| عدد المحاولات | 3 مرات |
| Timeout | 120 ثانية |

---

## 📚 الملفات المضافة

1. `app/Jobs/ProcessAutoReplyJob.php` - الـ Job الرئيسي
2. `app/Http/Controllers/Webhook/FacebookWebhookController.php` - محدّث
3. `JOBS_DOCUMENTATION.md` - هذا الملف

---

**النظام جاهز للعمل تحت أي ضغط! 🚀**
