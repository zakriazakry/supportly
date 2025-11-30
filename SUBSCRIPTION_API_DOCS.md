# نظام الاشتراكات - توثيق API

## نظرة عامة
تم إضافة نظام اشتراكات كامل يتيح للمستخدمين الاشتراك في باقات مختلفة مع مميزات وقيود متنوعة.

## الجداول المضافة

### 1. جدول الباقات (packages)
يحتوي على:
- معلومات الباقة (الاسم، الوصف، السعر، المدة)
- المميزات (دعم 24 ساعة، ردود غير محدودة، تقارير متقدمة، إلخ)
- القيود (عدد الحسابات، الصفحات، القوالب، الردود الشهرية)

### 2. جدول الاشتراكات (subscriptions)
يحتوي على:
- معلومات الاشتراك (المستخدم، الباقة، تاريخ البداية والنهاية)
- حالة الاشتراك (active, expired, cancelled, pending)
- معلومات الدفع
- خيار التجديد التلقائي

## API Endpoints

### 1. الحصول على جميع الباقات المتاحة
```
GET /api/user/profile/packages
```

**الاستجابة:**
```json
{
  "status": true,
  "message": "success",
  "data": [
    {
      "id": 1,
      "name": "الباقة الاحترافية",
      "description": "الأنسب للشركات والأعمال المتوسطة",
      "price": "299.00",
      "currency": "LYD",
      "duration_type": "monthly",
      "duration_value": 1,
      "features": {
        "24_support": true,
        "unlimited_replies": true,
        "advanced_reports": true,
        "multiple_accounts": true,
        "custom_templates": true,
        "priority_processing": true
      },
      "limits": {
        "facebook_accounts": null,
        "facebook_pages": null,
        "auto_replies_per_month": null,
        "templates": null
      }
    }
  ]
}
```

### 2. الحصول على الاشتراك الحالي
```
GET /api/user/profile/subscription/current
```

**الاستجابة:**
```json
{
  "status": true,
  "message": "success",
  "data": {
    "subscription": {
      "id": 1,
      "status": "active",
      "start_date": "2024-11-01",
      "end_date": "2024-12-01",
      "remaining_days": 18,
      "auto_renew": false
    },
    "package": {
      "id": 3,
      "name": "الباقة الاحترافية",
      "description": "الأنسب للشركات والأعمال المتوسطة",
      "price": "299.00",
      "currency": "LYD",
      "features": {
        "24_support": true,
        "unlimited_replies": true,
        "advanced_reports": true,
        "multiple_accounts": true,
        "custom_templates": true,
        "priority_processing": true
      },
      "limits": {
        "facebook_accounts": null,
        "facebook_pages": null,
        "auto_replies_per_month": null,
        "templates": null
      }
    }
  }
}
```

### 3. الاشتراك في باقة جديدة
```
POST /api/user/profile/subscription/subscribe
```

**البيانات المطلوبة:**
```json
{
  "package_id": 3,
  "payment_method": "bank_transfer",
  "payment_reference": "TXN123456",
  "auto_renew": false
}
```

**الاستجابة:**
```json
{
  "status": true,
  "message": "تم الاشتراك بنجاح",
  "data": {
    "id": 1,
    "user_id": 1,
    "package_id": 3,
    "start_date": "2024-11-30",
    "end_date": "2024-12-30",
    "status": "active",
    "paid_amount": "299.00",
    "payment_method": "bank_transfer",
    "payment_reference": "TXN123456",
    "auto_renew": false
  }
}
```

### 4. إلغاء الاشتراك
```
POST /api/user/profile/subscription/cancel
```

**البيانات المطلوبة (اختيارية):**
```json
{
  "reason": "لم أعد بحاجة للخدمة"
}
```

**الاستجابة:**
```json
{
  "status": true,
  "message": "تم إلغاء الاشتراك بنجاح",
  "data": null
}
```

### 5. الحصول على سجل الاشتراكات
```
GET /api/user/profile/subscription/history
```

**الاستجابة:**
```json
{
  "status": true,
  "message": "success",
  "data": [
    {
      "id": 1,
      "package_name": "الباقة الاحترافية",
      "start_date": "2024-11-01",
      "end_date": "2024-12-01",
      "status": "active",
      "paid_amount": "299.00",
      "payment_method": "bank_transfer",
      "cancelled_at": null
    }
  ]
}
```

### 6. التحقق من القيود والمميزات
```
GET /api/user/profile/subscription/check-limits
```

**الاستجابة:**
```json
{
  "status": true,
  "message": "success",
  "data": {
    "limits": {
      "facebook_accounts": {
        "limit": null,
        "current": 3,
        "can_add": true
      },
      "facebook_pages": {
        "limit": null,
        "current": 5,
        "can_add": true
      },
      "templates": {
        "limit": null,
        "current": 8,
        "can_add": true
      }
    },
    "features": {
      "24_support": true,
      "unlimited_replies": true,
      "advanced_reports": true,
      "multiple_accounts": true,
      "custom_templates": true,
      "priority_processing": true
    }
  }
}
```

## استخدام القيود في الكود

### التحقق من إمكانية إضافة مورد جديد
```php
// في أي Controller
$user = $request->user();

// التحقق من إمكانية إضافة حساب فيسبوك جديد
if (!$user->canAdd('facebook_accounts')) {
    return responseFormat(null, 'لقد وصلت للحد الأقصى من الحسابات المسموحة', 403);
}

// التحقق من إمكانية إضافة صفحة جديدة
if (!$user->canAdd('facebook_pages')) {
    return responseFormat(null, 'لقد وصلت للحد الأقصى من الصفحات المسموحة', 403);
}

// التحقق من إمكانية إضافة قالب جديد
if (!$user->canAdd('templates')) {
    return responseFormat(null, 'لقد وصلت للحد الأقصى من القوالب المسموحة', 403);
}
```

### التحقق من المميزات
```php
$user = $request->user();

// التحقق من ميزة الدعم 24 ساعة
if ($user->hasFeature('24_support')) {
    // السماح بإرسال تذكرة دعم
}

// التحقق من ميزة الردود غير المحدودة
if ($user->hasFeature('unlimited_replies')) {
    // السماح بإرسال ردود غير محدودة
}

// التحقق من ميزة التقارير المتقدمة
if ($user->hasFeature('advanced_reports')) {
    // عرض التقارير المتقدمة
}
```

### الحصول على القيود
```php
$user = $request->user();

// الحصول على حد الحسابات
$accountsLimit = $user->getLimit('facebook_accounts'); // null = غير محدود

// الحصول على حد الصفحات
$pagesLimit = $user->getLimit('facebook_pages');

// الحصول على حد القوالب
$templatesLimit = $user->getLimit('templates');
```

## تشغيل المشروع

### 1. تشغيل Migrations
```bash
php artisan migrate
```

### 2. إضافة الباقات التجريبية
```bash
php artisan db:seed --class=PackageSeeder
```

### 3. التحقق من الاشتراكات المنتهية (يمكن إضافته في Scheduler)
```php
// في app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        \App\Models\Subscription::checkExpiredSubscriptions();
    })->daily();
}
```

## الباقات المتاحة (بعد تشغيل Seeder)

1. **الباقة المجانية** - 0 د.ل/شهرياً
   - 1 حساب فيسبوك
   - 2 صفحة
   - 100 رد شهرياً
   - 3 قوالب

2. **الباقة الأساسية** - 99 د.ل/شهرياً
   - 2 حساب فيسبوك
   - 5 صفحات
   - 500 رد شهرياً
   - 10 قوالب
   - ربط حسابات متعددة
   - قوالب رسائل مخصصة

3. **الباقة الاحترافية** - 299 د.ل/شهرياً
   - حسابات غير محدودة
   - صفحات غير محدودة
   - ردود غير محدودة
   - قوالب غير محدودة
   - جميع المميزات

4. **باقة الأعمال** - 599 د.ل/شهرياً
   - كل مميزات الباقة الاحترافية
   - دعم مخصص

## ملاحظات مهمة

1. **حالة الاشتراك**: في الكود الحالي، يتم تفعيل الاشتراك مباشرة. في التطبيق الحقيقي، يجب أن يكون `pending` حتى يتم تأكيد الدفع.

2. **التجديد التلقائي**: يمكن إضافة Job للتحقق من الاشتراكات التي تحتاج للتجديد التلقائي.

3. **القيود الشهرية**: بالنسبة لـ `limit_auto_replies_per_month`، يجب إضافة جدول لتتبع عدد الردود الشهرية لكل مستخدم.

4. **الإشعارات**: يمكن إضافة إشعارات للمستخدمين قبل انتهاء الاشتراك.
