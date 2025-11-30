# دليل تطبيق القيود والمميزات في نظام الاشتراكات

## نظرة عامة
هذا الدليل يوضح كيفية تحديد استخدام المستخدمين بناءً على اشتراكاتهم في التطبيق.

---

## 1. استخدام Middleware

### Middleware للتحقق من وجود اشتراك نشط

يمكنك استخدام middleware `subscription` للتأكد من أن المستخدم لديه اشتراك نشط:

```php
// في ملف routes/apis/user.php
Route::middleware(['auth:sanctum', 'subscription'])->group(function () {
    Route::get('/premium-feature', [SomeController::class, 'premiumFeature']);
});
```

### Middleware للتحقق من ميزة معينة

يمكنك استخدام middleware `feature` للتحقق من ميزة محددة:

```php
// التحقق من ميزة الدعم 24 ساعة
Route::middleware(['auth:sanctum', 'feature:24_support'])->group(function () {
    Route::post('/support/urgent-ticket', [SupportController::class, 'urgentTicket']);
});

// التحقق من ميزة التقارير المتقدمة
Route::middleware(['auth:sanctum', 'feature:advanced_reports'])->group(function () {
    Route::get('/reports/advanced', [ReportsController::class, 'advanced']);
});

// التحقق من ميزة الأولوية في المعالجة
Route::middleware(['auth:sanctum', 'feature:priority_processing'])->group(function () {
    Route::post('/process/priority', [ProcessController::class, 'priority']);
});
```

---

## 2. التحقق من القيود في Controllers

### مثال 1: التحقق قبل إضافة حساب فيسبوك

```php
public function addAccount(Request $request)
{
    $user = $request->user();
    
    // التحقق من وجود اشتراك نشط
    if (!$user->hasActiveSubscription()) {
        return responseFormat('يجب أن يكون لديك اشتراك نشط', 403);
    }
    
    // التحقق من القيود
    if (!$user->canAdd('facebook_accounts')) {
        $limit = $user->getLimit('facebook_accounts');
        $current = $user->facebookAccounts()->count();
        
        return responseFormat([
            'message' => 'لقد وصلت للحد الأقصى من الحسابات',
            'limit' => $limit,
            'current' => $current,
            'upgrade_required' => true
        ], 403);
    }
    
    // إضافة الحساب...
}
```

### مثال 2: التحقق قبل إضافة صفحة فيسبوك

```php
public function linkPage(Request $request)
{
    $user = $request->user();
    
    // التحقق من الاشتراك
    if (!$user->hasActiveSubscription()) {
        return responseFormat('يجب أن يكون لديك اشتراك نشط', 403);
    }
    
    // التحقق من القيود
    if (!$user->canAdd('facebook_pages')) {
        return responseFormat([
            'message' => 'لقد وصلت للحد الأقصى من الصفحات',
            'limit' => $user->getLimit('facebook_pages'),
            'current' => $user->facebookPages()->count(),
        ], 403);
    }
    
    // ربط الصفحة...
}
```

### مثال 3: التحقق قبل إضافة قالب

```php
public function createTemplate(Request $request)
{
    $user = $request->user();
    
    if (!$user->canAdd('templates')) {
        return responseFormat([
            'message' => 'لقد وصلت للحد الأقصى من القوالب',
            'limit' => $user->getLimit('templates'),
            'current' => $user->autoReplyTemplates()->count(),
        ], 403);
    }
    
    // إنشاء القالب...
}
```

---

## 3. التحقق من المميزات

### مثال 1: ميزة الدعم 24 ساعة

```php
public function sendUrgentTicket(Request $request)
{
    $user = $request->user();
    
    if (!$user->hasFeature('24_support')) {
        return responseFormat([
            'message' => 'هذه الميزة متاحة فقط في الباقات المتقدمة',
            'required_feature' => 'دعم 24 ساعة',
            'upgrade_required' => true
        ], 403);
    }
    
    // إرسال التذكرة العاجلة...
}
```

### مثال 2: ميزة الردود غير المحدودة

```php
public function sendAutoReply(Request $request)
{
    $user = $request->user();
    
    // إذا لم تكن الردود غير محدودة، تحقق من العدد
    if (!$user->hasFeature('unlimited_replies')) {
        $monthlyLimit = $user->getLimit('auto_replies_per_month');
        $currentCount = $this->getMonthlyRepliesCount($user);
        
        if ($currentCount >= $monthlyLimit) {
            return responseFormat([
                'message' => 'لقد وصلت للحد الأقصى من الردود الشهرية',
                'limit' => $monthlyLimit,
                'current' => $currentCount,
            ], 403);
        }
    }
    
    // إرسال الرد التلقائي...
}
```

### مثال 3: ميزة التقارير المتقدمة

```php
public function getAdvancedReport(Request $request)
{
    $user = $request->user();
    
    if (!$user->hasFeature('advanced_reports')) {
        return responseFormat([
            'message' => 'التقارير المتقدمة متاحة فقط في الباقة الاحترافية وما فوق',
            'current_package' => $user->getCurrentSubscription()?->package?->name,
            'upgrade_required' => true
        ], 403);
    }
    
    // عرض التقرير المتقدم...
}
```

---

## 4. الدوال المساعدة المتاحة في User Model

### التحقق من الاشتراك

```php
$user = auth()->user();

// هل لدى المستخدم اشتراك نشط؟
if ($user->hasActiveSubscription()) {
    // نعم
}

// الحصول على الاشتراك الحالي
$subscription = $user->getCurrentSubscription();
```

### التحقق من المميزات

```php
// التحقق من ميزة معينة
if ($user->hasFeature('24_support')) {
    // المستخدم لديه دعم 24 ساعة
}

if ($user->hasFeature('unlimited_replies')) {
    // المستخدم لديه ردود غير محدودة
}

if ($user->hasFeature('advanced_reports')) {
    // المستخدم لديه تقارير متقدمة
}

if ($user->hasFeature('multiple_accounts')) {
    // المستخدم يمكنه ربط حسابات متعددة
}

if ($user->hasFeature('custom_templates')) {
    // المستخدم يمكنه إنشاء قوالب مخصصة
}

if ($user->hasFeature('priority_processing')) {
    // المستخدم لديه أولوية في المعالجة
}
```

### الحصول على القيود

```php
// الحصول على حد معين (null = غير محدود)
$accountsLimit = $user->getLimit('facebook_accounts');
$pagesLimit = $user->getLimit('facebook_pages');
$templatesLimit = $user->getLimit('templates');
$repliesLimit = $user->getLimit('auto_replies_per_month');

// مثال:
if ($accountsLimit === null) {
    echo "عدد غير محدود من الحسابات";
} else {
    echo "الحد الأقصى: $accountsLimit حسابات";
}
```

### التحقق من إمكانية الإضافة

```php
// هل يمكن إضافة حساب فيسبوك جديد؟
if ($user->canAdd('facebook_accounts')) {
    // نعم، يمكن الإضافة
} else {
    // لا، وصل للحد الأقصى
}

// هل يمكن إضافة صفحة جديدة؟
if ($user->canAdd('facebook_pages')) {
    // نعم
}

// هل يمكن إضافة قالب جديد؟
if ($user->canAdd('templates')) {
    // نعم
}
```

---

## 5. أمثلة عملية كاملة

### مثال: Controller كامل مع جميع القيود

```php
<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TemplatesController extends Controller
{
    /**
     * عرض جميع القوالب
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $templates = $user->autoReplyTemplates;
        
        // إضافة معلومات القيود
        $data = [
            'templates' => $templates,
            'limits' => [
                'max' => $user->getLimit('templates'),
                'current' => $templates->count(),
                'can_add' => $user->canAdd('templates'),
            ]
        ];
        
        return responseFormat($data);
    }
    
    /**
     * إنشاء قالب جديد
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // 1. التحقق من الاشتراك
        if (!$user->hasActiveSubscription()) {
            return responseFormat('يجب أن يكون لديك اشتراك نشط', 403);
        }
        
        // 2. التحقق من ميزة القوالب المخصصة
        if (!$user->hasFeature('custom_templates')) {
            return responseFormat([
                'message' => 'القوالب المخصصة متاحة فقط في الباقات المدفوعة',
                'upgrade_required' => true
            ], 403);
        }
        
        // 3. التحقق من القيود
        if (!$user->canAdd('templates')) {
            return responseFormat([
                'message' => 'لقد وصلت للحد الأقصى من القوالب',
                'limit' => $user->getLimit('templates'),
                'current' => $user->autoReplyTemplates()->count(),
                'upgrade_required' => true
            ], 403);
        }
        
        // 4. إنشاء القالب
        $template = $user->autoReplyTemplates()->create([
            'name' => $request->name,
            'content' => $request->content,
            // ... باقي الحقول
        ]);
        
        return responseFormat($template, 201);
    }
}
```

---

## 6. تطبيق القيود في Routes

```php
// routes/apis/user.php

use App\Http\Controllers\User\TemplatesController;

Route::middleware('auth:sanctum')->group(function () {
    
    // Routes تحتاج فقط لاشتراك نشط
    Route::middleware('subscription')->group(function () {
        Route::get('/templates', [TemplatesController::class, 'index']);
    });
    
    // Routes تحتاج لميزة القوالب المخصصة
    Route::middleware(['subscription', 'feature:custom_templates'])->group(function () {
        Route::post('/templates', [TemplatesController::class, 'store']);
        Route::put('/templates/{id}', [TemplatesController::class, 'update']);
    });
    
    // Routes تحتاج لميزة التقارير المتقدمة
    Route::middleware(['subscription', 'feature:advanced_reports'])->group(function () {
        Route::get('/reports/advanced', [ReportsController::class, 'advanced']);
        Route::get('/analytics/detailed', [AnalyticsController::class, 'detailed']);
    });
});
```

---

## 7. رسائل الخطأ الموحدة

### إنشاء Helper للرسائل

```php
// app/Helpers/SubscriptionHelper.php

namespace App\Helpers;

class SubscriptionHelper
{
    public static function noSubscriptionError()
    {
        return [
            'message' => 'يجب أن يكون لديك اشتراك نشط للوصول إلى هذه الميزة',
            'action_required' => 'subscribe',
            'upgrade_url' => '/packages'
        ];
    }
    
    public static function limitReachedError($resource, $user)
    {
        return [
            'message' => "لقد وصلت للحد الأقصى من {$resource}",
            'limit' => $user->getLimit($resource),
            'current' => $user->{$resource}()->count(),
            'action_required' => 'upgrade',
            'upgrade_url' => '/packages'
        ];
    }
    
    public static function featureNotAvailableError($feature, $user)
    {
        return [
            'message' => 'هذه الميزة غير متاحة في باقتك الحالية',
            'required_feature' => $feature,
            'current_package' => $user->getCurrentSubscription()?->package?->name,
            'action_required' => 'upgrade',
            'upgrade_url' => '/packages'
        ];
    }
}
```

### استخدام Helper

```php
use App\Helpers\SubscriptionHelper;

public function addAccount(Request $request)
{
    $user = $request->user();
    
    if (!$user->hasActiveSubscription()) {
        return responseFormat(
            SubscriptionHelper::noSubscriptionError(), 
            403
        );
    }
    
    if (!$user->canAdd('facebook_accounts')) {
        return responseFormat(
            SubscriptionHelper::limitReachedError('facebook_accounts', $user),
            403
        );
    }
    
    // ...
}
```

---

## 8. ملخص المميزات المتاحة

| الميزة | اسم الحقل | الوصف |
|--------|-----------|-------|
| دعم 24 ساعة | `24_support` | إمكانية الوصول للدعم الفني على مدار الساعة |
| ردود غير محدودة | `unlimited_replies` | عدم وجود حد للردود التلقائية الشهرية |
| تقارير متقدمة | `advanced_reports` | الوصول للتقارير والإحصائيات المتقدمة |
| حسابات متعددة | `multiple_accounts` | إمكانية ربط أكثر من حساب فيسبوك |
| قوالب مخصصة | `custom_templates` | إنشاء وتعديل قوالب الرسائل |
| أولوية المعالجة | `priority_processing` | معالجة أسرع للطلبات |

## 9. ملخص القيود المتاحة

| القيد | اسم الحقل | الوصف |
|-------|-----------|-------|
| حسابات فيسبوك | `facebook_accounts` | عدد حسابات فيسبوك المسموح بها |
| صفحات فيسبوك | `facebook_pages` | عدد صفحات فيسبوك المسموح بها |
| الردود الشهرية | `auto_replies_per_month` | عدد الردود التلقائية المسموحة شهرياً |
| القوالب | `templates` | عدد القوالب المسموح بها |

**ملاحظة**: القيمة `null` تعني غير محدود.

---

## 10. نصائح مهمة

1. **دائماً تحقق من الاشتراك أولاً** قبل التحقق من المميزات أو القيود
2. **استخدم Middleware** للـ routes التي تحتاج لمميزات معينة
3. **قدم رسائل واضحة** للمستخدم عند الوصول للحد الأقصى
4. **اقترح الترقية** عندما يحتاج المستخدم لمميزات إضافية
5. **تتبع الاستخدام** لتحديث القيود الشهرية (مثل عدد الردود)
