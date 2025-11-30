# 🎯 كيفية تحديد استخدام المستخدم حسب اشتراكه

## الخطوات السريعة

### 1️⃣ تشغيل قاعدة البيانات
```bash
php artisan migrate
php artisan db:seed --class=PackageSeeder
```

### 2️⃣ في أي Controller، استخدم هذه الطرق:

#### ✅ التحقق من وجود اشتراك نشط
```php
if (!$user->hasActiveSubscription()) {
    return responseFormat('يجب أن يكون لديك اشتراك نشط', 403);
}
```

#### ✅ التحقق من ميزة معينة
```php
// دعم 24 ساعة
if (!$user->hasFeature('24_support')) {
    return responseFormat('هذه الميزة غير متاحة', 403);
}

// ردود غير محدودة
if (!$user->hasFeature('unlimited_replies')) {
    // المستخدم لديه حد شهري
}

// تقارير متقدمة
if (!$user->hasFeature('advanced_reports')) {
    return responseFormat('ترقي للباقة الاحترافية', 403);
}
```

#### ✅ التحقق من القيود (عدد الحسابات، الصفحات، إلخ)
```php
// قبل إضافة حساب فيسبوك
if (!$user->canAdd('facebook_accounts')) {
    return responseFormat([
        'message' => 'وصلت للحد الأقصى',
        'limit' => $user->getLimit('facebook_accounts'),
        'current' => $user->facebookAccounts()->count(),
    ], 403);
}

// قبل إضافة صفحة
if (!$user->canAdd('facebook_pages')) {
    return responseFormat('وصلت للحد الأقصى من الصفحات', 403);
}

// قبل إضافة قالب
if (!$user->canAdd('templates')) {
    return responseFormat('وصلت للحد الأقصى من القوالب', 403);
}
```

#### ✅ التحقق من القيود الشهرية (الردود التلقائية)
```php
// قبل إرسال رد تلقائي
if (!$user->canSendAutoReply()) {
    return responseFormat([
        'message' => 'وصلت للحد الأقصى من الردود الشهرية',
        'remaining' => $user->getRemainingAutoReplies(),
    ], 403);
}

// بعد إرسال الرد بنجاح
$user->incrementAutoReplyCount();
```

---

## 📝 مثال عملي كامل

```php
public function addFacebookAccount(Request $request)
{
    $user = $request->user();
    
    // 1. تحقق من الاشتراك
    if (!$user->hasActiveSubscription()) {
        return responseFormat('يجب أن يكون لديك اشتراك نشط', 403);
    }
    
    // 2. تحقق من ميزة الحسابات المتعددة (إذا كان عنده أكثر من حساب)
    if ($user->facebookAccounts()->count() > 0 && !$user->hasFeature('multiple_accounts')) {
        return responseFormat('الحسابات المتعددة متاحة في الباقة الأساسية وما فوق', 403);
    }
    
    // 3. تحقق من القيود
    if (!$user->canAdd('facebook_accounts')) {
        $limit = $user->getLimit('facebook_accounts');
        $current = $user->facebookAccounts()->count();
        
        return responseFormat([
            'message' => 'لقد وصلت للحد الأقصى من حسابات فيسبوك',
            'limit' => $limit,
            'current' => $current,
            'upgrade_required' => true,
            'upgrade_message' => 'قم بالترقية للباقة الاحترافية للحصول على حسابات غير محدودة'
        ], 403);
    }
    
    // 4. أضف الحساب
    $account = $user->facebookAccounts()->create([...]);
    
    return responseFormat($account, 201);
}
```

---

## 🛡️ استخدام Middleware في Routes

```php
// routes/apis/user.php

// للصفحات التي تحتاج اشتراك نشط فقط
Route::middleware(['auth:sanctum', 'subscription'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// للصفحات التي تحتاج ميزة معينة
Route::middleware(['auth:sanctum', 'feature:advanced_reports'])->group(function () {
    Route::get('/reports/advanced', [ReportsController::class, 'advanced']);
});

Route::middleware(['auth:sanctum', 'feature:24_support'])->group(function () {
    Route::post('/support/urgent', [SupportController::class, 'urgent']);
});
```

---

## 📊 جميع المميزات المتاحة

| الميزة | الكود | الوصف |
|--------|------|-------|
| دعم 24 ساعة | `24_support` | دعم فني على مدار الساعة |
| ردود غير محدودة | `unlimited_replies` | بدون حد شهري للردود |
| تقارير متقدمة | `advanced_reports` | تقارير وإحصائيات مفصلة |
| حسابات متعددة | `multiple_accounts` | ربط أكثر من حساب فيسبوك |
| قوالب مخصصة | `custom_templates` | إنشاء قوالب رسائل |
| أولوية المعالجة | `priority_processing` | معالجة أسرع |

---

## 📏 جميع القيود المتاحة

| القيد | الكود | الوصف |
|------|------|-------|
| حسابات فيسبوك | `facebook_accounts` | عدد الحسابات المسموحة |
| صفحات فيسبوك | `facebook_pages` | عدد الصفحات المسموحة |
| القوالب | `templates` | عدد القوالب المسموحة |
| الردود الشهرية | `auto_replies_per_month` | عدد الردود شهرياً |

**ملاحظة**: القيمة `null` تعني **غير محدود** ✨

---

## 🎨 أمثلة إضافية

### مثال: في FacebookWebhookController
```php
public function handleComment($commentData, $pageId)
{
    // الحصول على المستخدم من الصفحة
    $page = FacebookPage::where('page_id', $pageId)->first();
    $user = $page->user;
    
    // التحقق من القيود الشهرية
    if (!$user->canSendAutoReply()) {
        \Log::warning("User {$user->id} reached monthly limit");
        return; // لا ترسل رد
    }
    
    // إرسال الرد التلقائي
    $this->sendReply($commentData);
    
    // تحديث العداد
    $user->incrementAutoReplyCount();
}
```

### مثال: في TemplatesController
```php
public function store(Request $request)
{
    $user = $request->user();
    
    // تحقق من الميزة
    if (!$user->hasFeature('custom_templates')) {
        return responseFormat([
            'message' => 'القوالب المخصصة متاحة في الباقة الأساسية وما فوق',
            'current_package' => $user->getCurrentSubscription()?->package?->name,
            'upgrade_required' => true
        ], 403);
    }
    
    // تحقق من القيود
    if (!$user->canAdd('templates')) {
        return responseFormat([
            'message' => 'وصلت للحد الأقصى من القوالب',
            'limit' => $user->getLimit('templates'),
            'current' => $user->autoReplyTemplates()->count(),
        ], 403);
    }
    
    // إنشاء القالب
    $template = $user->autoReplyTemplates()->create($request->all());
    
    return responseFormat($template, 201);
}
```

---

## 📖 ملفات التوثيق

1. **SUBSCRIPTION_SUMMARY.md** - ملخص سريع (ابدأ من هنا)
2. **SUBSCRIPTION_API_DOCS.md** - توثيق API كامل
3. **SUBSCRIPTION_USAGE_GUIDE.md** - دليل مفصل مع أمثلة
4. **HOW_TO_LIMIT_USERS.md** - هذا الملف

---

## ✅ تم التطبيق في

- ✅ `FacebookAccountsController` - التحقق قبل إضافة حساب
- ✅ `FacebookPagesController` - التحقق قبل ربط صفحة
- ✅ `ProfileController` - إدارة الاشتراكات كاملة
- ✅ `User Model` - جميع الدوال المساعدة
- ✅ Middleware - `subscription` و `feature`

---

## 🚀 الخطوة التالية

طبق نفس المنطق في:
1. `TemplatesController` - عند إنشاء قالب جديد
2. `FacebookWebhookController` - عند إرسال رد تلقائي
3. `ReportsController` - للتقارير المتقدمة
4. `SupportController` - للدعم 24 ساعة

---

**بالتوفيق! 🎉**
