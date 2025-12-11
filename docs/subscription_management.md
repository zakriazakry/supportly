# دليل إدارة الاشتراكات والميزات (Subscription & Feature Management)

هذا الدليل يشرح كيفية استخدام نظام إدارة الاشتراكات والميزات في التطبيق، بما في ذلك `CheckFeature` middleware وكيفية تطبيقه على المسارات (Routes) للتحكم في وصول المستخدمين بناءً على باقاتهم.

## 1. نظرة عامة

يعتمد النظام على جدول `packages` لتحديد الميزات (`features`) والقيود (`limits`) المتاحة لكل باقة. يتم تعيين المستخدمين إلى باقات عبر جدول `subscriptions`.

### المكونات الرئيسية:

-   **Package Model**: يحتوي على تعريف الميزات (مثل `feature_whatsapp`) والقيود (مثل `limit_whatsapp_accounts`).
-   **User Model**: يحتوي على توابع مساعدة للتحقق من الميزات (`hasFeature`) والحدود (`canAdd`).
-   **CheckFeature Middleware**: يقوم بمنع الوصول للمسارات إذا لم يتوفر الشرط المطلوب.

---

## 2. كيفية استخدام `CheckFeature` Middleware

الـ Middleware معرف بالاسم المستعار `feature`. يمكن استخدامه بشكل مباشر في ملفات `routes`.

### الصيغة العامة:

```php
middleware('feature:اسم_الميزة,اسم_المورد')
```

-   **اسم_الميزة (Feature Name)**: (اختياري) اسم العمود في جدول `packages` بدون البادئة `feature_`. مثال: `whatsapp` للعمود `feature_whatsapp`.
-   **اسم_المورد (Resource Name)**: (اختياري) اسم المورد للتحقق من الحد (Limit) الخاص به. مثال: `whatsapp_accounts` للعمود `limit_whatsapp_accounts`.

### أمثلة للاستخدام:

#### 1. التحقق من ميزة فقط (Feature Check Only)

للتحقق مما إذا كان المستخدم يمتلك حق الوصول إلى ميزة معينة (Boolean Check).

```php
// التحقق من ميزة الواتساب (feature_whatsapp)
Route::get('/whatsapp', [Controller::class, 'index'])->middleware('feature:whatsapp');

// التحقق من ميزة الرد التلقائي (feature_whatsapp_auto_reply)
Route::post('/auto-reply', [Controller::class, 'store'])->middleware('feature:whatsapp_auto_reply');
```

#### 2. التحقق من الحد فقط (Limit Check Only)

للتحقق مما إذا كان المستخدم قد تجاوز الحد المسموح به لإضافة مورد جديد. يجب استخدام هذا عادة مع مسارات الإنشاء (`store` / `create`).

```php
// التحقق من حد حسابات الواتساب (limit_whatsapp_accounts)
// ملاحظة: نترك المعامل الأول فارغاً إذا لم نرد التحقق من ميزة محددة، لكن يفضل دائماً التحقق من الميزة الأم.
Route::post('/instances', [Controller::class, 'create'])->middleware('feature:,whatsapp_accounts');
```

#### 3. التحقق من الميزة والحد معاً (Feature & Limit Check)

وهو الاستخدام الأكثر شيوعاً عند إنشاء موارد جديدة. نتحقق أولاً أن الميزة مفعلة، ثم نتحقق من الحد.

```php
// التحقق من أن ميزة الواتساب مفعلة، وأن المستخدم لم يتجاوز عدد الحسابات المسموح به
Route::post('/instances', [Controller::class, 'create'])->middleware('feature:whatsapp,whatsapp_accounts');
```

---

## 3. تطبيق Middleware في `routes/apis/whatsapp.php`

تم تطبيق النظام على مسارات الواتساب كالتالي:

### إدارة الحسابات (Instances)

جميع المسارات تتطلب ميزة `whatsapp`. مسار الإنشاء يتطلب بالإضافة إلى ذلك التحقق من الحد.

```php
// عرض الحسابات (يتطلب ميزة whatsapp فقط)
Route::get('/instances', ...)->middleware('feature:whatsapp');

// إنشاء حساب جديد (يتطلب ميزة whatsapp + لم يتجاوز limit_whatsapp_accounts)
Route::post('/instances', ...)->middleware('feature:whatsapp,whatsapp_accounts');
```

### الرد التلقائي (Auto Reply)

تم تجميع المسارات تحت middleware خاص بميزة الرد التلقائي.

```php
Route::middleware('feature:whatsapp_auto_reply')->group(function () {
    Route::post('/auto-reply/settings', ...);
    Route::post('/auto-reply/rules', ...);
});
```

### المطورين (Developers / Webhooks)

تتطلب ميزة خاصة `feature_whatsapp_developer`.

```php
Route::middleware(['feature:whatsapp', 'feature:whatsapp_developer'])->group(function () {
    Route::post('/webhooks', ...);
    Route::post('/api-keys', ...);
});
```

---

## 4. كيفية إضافة ميزان أو قيود جديدة

1.  **قاعدة البيانات**:

    -   أضف عمود جديد في `packages` migration:
        -   للميزات: `feature_new_feature` (boolean).
        -   للقيود: `limit_new_resource` (integer, nullable).

2.  **Package Model**:

    -   أضف العمود إلى `$fillable`, `$casts`.
    -   حدث توابع `getFeatures()` أو `getLimits()` (اختياري، للعرض فقط).

3.  **User Model**:

    -   حدث تابع `canAdd($resource)` لإدراج المنطق الخاص بحساب العدد الحالي للمورد الجديد (count logic).

4.  **Routes**:
    -   استخدم `middleware('feature:new_feature,new_resource')` في المكان المناسب.

---

## 5. ملاحظات هامة

-   التحقق من الاشتراك النشط يتم تلقائياً داخل `CheckFeature` middleware.
-   المعاملات اختيارية، يمكن تمرير `feature` فقط، أو `resource` فقط (مع ترك الأول فارغاً)، أو كلاهما.
-   إذا كان الحد (Limit) في قاعدة البيانات `null`، فهذا يعني "غير محدود" (Unlimited).
