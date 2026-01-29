<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'image',
        'email',
        'password',
        'phone',
        'otp',
        'otp_expires_at',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    public function getImageAttribute($value)
    {
        return $value ? asset('Storage/' . $value) : null;
    }
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'integer',
        ];
    }

    /**
     * Get the wallets for the user.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the Facebook accounts for the user.
     */
    public function facebookAccounts(): HasMany
    {
        return $this->hasMany(FacebookAccount::class);
    }

    /**
     * Get the Facebook pages for the user.
     */
    public function facebookPages(): HasMany
    {
        return $this->hasMany(FacebookPage::class);
    }

    /**
     * Get the auto reply templates for the user.
     */
    public function autoReplyTemplates(): HasMany
    {
        return $this->hasMany(AutoReplyTemplate::class);
    }

    /**
     * Get the posts for the user.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the post reply states for the user.
     */
    public function postReplyStates(): HasMany
    {
        return $this->hasMany(PostReplyState::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Get the subscriptions for the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the active subscription for the user.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest('end_date');
    }

    /**
     * Check if user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Get the current active subscription.
     */
    public function getCurrentSubscription()
    {
        return $this->activeSubscription()->first();
    }

    /**
     * Check if user has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        $subscription = $this->getCurrentSubscription();

        if (!$subscription || !$subscription->package) {
            return false;
        }

        $featureColumn = 'feature_' . $feature;
        return $subscription->package->$featureColumn ?? false;
    }

    /**
     * Get limit for a specific resource.
     */
    public function getLimit(string $resource): ?int
    {
        $subscription = $this->getCurrentSubscription();

        if (!$subscription || !$subscription->package) {
            return 0;
        }

        $limitColumn = 'limit_' . $resource;
        return $subscription->package->$limitColumn;
    }
    public function canPay(): bool
    {
        if ($this->getActiveWallet()?->balance >= 0.5) {
            return true;
        }
        return false;
    }

    public static function calculateAICost(array $usage): float
    {
        // دعم الصيغتين
        $inputTokens  = $usage['input_tokens']
            ?? $usage['prompt_tokens']
            ?? 0;

        $outputTokens = $usage['output_tokens']
            ?? $usage['completion_tokens']
            ?? 0;

        // الأسعار
        $inputCostPer1k  = 0.00015;
        $outputCostPer1k = 0.0006;

        // الحساب بالدولار
        $inputCost  = ($inputTokens / 1000) * $inputCostPer1k;
        $outputCost = ($outputTokens / 1000) * $outputCostPer1k;

        $totalCostUSD = $inputCost + $outputCost;

        // التحويل للدينار
        $exchangeRate = 25.0;
        $totalCostLYD = $totalCostUSD * $exchangeRate;

        // عمولة المنصة
        $platformFee = $totalCostLYD * 0.20;

        return round($totalCostLYD + $platformFee, 4);
    }
    public function chargeAIService(string $serviceName, array $usage, float $cost)
    {
        if (!$this->canPay()) {
            throw new \Exception('يجيب ان يكون رصيدك اكثر من 0.5 لكي تتمكن من استخدام هذه الخدمة');
        }

        return $this->deductWalletBalance(
            $cost,
            "استخدام خدمة {$serviceName} - Tokens: {$usage['total_tokens']}",
            'ai_service',
            null,
            [
                'service_name' => $serviceName,
                'usage' => $usage,
                'cost' => $cost,
            ]
        );
    }
    /**
     * Check if user can add more of a resource.
     */
    public function canAdd(string $resource): bool
    {
        $limit = $this->getLimit($resource);

        // null means unlimited
        if ($limit === null) {
            return true;
        }

        // Get current count based on resource type
        $count = match ($resource) {
            'facebook_accounts' => $this->facebookAccounts()->count(),
            'facebook_pages' => $this->facebookPages()->count(),
            'templates' => $this->autoReplyTemplates()->count(),
            'whatsapp_accounts' => $this->whatsappAccounts()->count(),
            'whatsapp_auto_replies_per_month' => $this->whatsappAutoReplies()->count(),
            default => 0,
        };

        return $count < $limit;
    }

    /**
     * Get the monthly usage stats for the user.
     */
    public function monthlyUsageStats(): HasMany
    {
        return $this->hasMany(MonthlyUsageStat::class);
    }

    /**
     * Get current month usage stats.
     */
    public function getCurrentMonthUsage()
    {
        return MonthlyUsageStat::getCurrentMonthStats($this->id);
    }

    /**
     * Check if user can send auto reply (monthly limit).
     */
    public function canSendAutoReply(): bool
    {
        // إذا كانت الردود غير محدودة
        if ($this->hasFeature('unlimited_replies')) {
            return true;
        }

        $limit = $this->getLimit('auto_replies_per_month');

        // إذا لم يكن هناك حد
        if ($limit === null) {
            return true;
        }

        $currentUsage = $this->getCurrentMonthUsage();
        return $currentUsage->auto_replies_count < $limit;
    }

    /**
     * Get remaining auto replies for current month.
     */
    public function getRemainingAutoReplies(): ?int
    {
        if ($this->hasFeature('unlimited_replies')) {
            return null; // Unlimited
        }

        $limit = $this->getLimit('auto_replies_per_month');

        if ($limit === null) {
            return null; // Unlimited
        }

        $currentUsage = $this->getCurrentMonthUsage();
        return max(0, $limit - $currentUsage->auto_replies_count);
    }

    /**
     * Increment auto reply count.
     */
    public function incrementAutoReplyCount(): void
    {
        $stats = $this->getCurrentMonthUsage();
        $stats->incrementAutoReplies();
    }
    // whatsapp
    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(WhatsAppInstance::class);
    }

    public function whatsappAutoReplies()
    {
        return $this->hasMany(WhatsAppMessage::class)->where('from_me', false);
    }

    /**
     * Get the active wallet for the user.
     */
    public function getActiveWallet(): ?Wallet
    {
        return $this->wallets()->active()->first();
    }

    /**
     * Add credit to user's active wallet.
     */
    public function addWalletCredit(float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null, ?array $metadata = null): WalletTransaction
    {
        $wallet = $this->getActiveWallet();

        if (!$wallet) {
            throw new \Exception('لا توجد محفظة نشطة');
        }

        return $wallet->addCredit($amount, $description, $referenceType, $referenceId, $metadata);
    }

    /**
     * Deduct from user's active wallet.
     */
    public function deductWalletBalance(float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null, ?array $metadata = null): WalletTransaction
    {
        $wallet = $this->getActiveWallet();

        if (!$wallet) {
            throw new \Exception('لا توجد محفظة نشطة');
        }

        return $wallet->deductBalance($amount, $description, $referenceType, $referenceId, $metadata);
    }

    /**
     * Apply coupon to wallet.
     */
    public function applyCoupon(string $couponCode): array
    {
        $coupon = Coupon::where('code', $couponCode)->first();

        if (!$coupon) {
            throw new \Exception('الكوبون غير موجود');
        }

        if (!$coupon->isValid()) {
            throw new \Exception('الكوبون غير صالح أو منتهي الصلاحية');
        }

        // تطبيق الكوبون حسب نوعه
        if ($coupon->type === 'wallet_recharge') {
            $amount = $coupon->value;

            $transaction = $this->addWalletCredit(
                $amount,
                "تعبئة المحفظة بواسطة الكوبون: {$couponCode}",
                'coupon',
                $coupon->id,
                ['coupon_code' => $couponCode]
            );

            $coupon->incrementUsage();

            return [
                'type' => 'wallet_recharge',
                'amount' => $amount,
                'transaction' => $transaction,
                'message' => "تم تعبئة المحفظة بمبلغ {$amount} بنجاح"
            ];
        }

        // إذا كان كوبون خصم على الاشتراك
        return [
            'type' => 'subscription_discount',
            'discount' => $coupon->value,
            'value_type' => $coupon->value_type,
            'message' => 'كوبون خصم جاهز للاستخدام عند الاشتراك'
        ];
    }

    /**
     * Purchase subscription using wallet.
     */
    public function purchaseSubscriptionWithWallet(int $packageId, ?string $couponCode = null): Subscription
    {
        $package = Package::findOrFail($packageId);

        if (!$package->is_active) {
            throw new \Exception('هذه الباقة غير متاحة حالياً');
        }

        $finalPrice = $package->price;
        $discount = 0;
        $coupon = null;

        // Retrieve current active subscription safely
        $currentSubscription = $this->getCurrentSubscription();

        // تطبيق الكوبون إذا وجد
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();

            if (!$coupon || !$coupon->isValid() || $coupon->type !== 'subscription_discount') {
                throw new \Exception('كوبون الخصم غير صالح');
            }

            if ($coupon->value_type === 'percentage') {
                $discount = ($package->price * $coupon->value) / 100;
            } else {
                $discount = $coupon->value;
            }

            $finalPrice = max(0, $package->price - $discount);
        }
        if ($currentSubscription && $currentSubscription->package_id == $packageId) {
            throw new \Exception('لديك اشتراك نشط بالفعل. يرجى إلغاء الاشتراك الحالي أولاً');
        }
        if ($currentSubscription && $currentSubscription->paid_amount != 0) {
            throw new \Exception('لديك اشتراك نشط بالفعل. يرجى إلغاء الاشتراك الحالي أولاً');
        }
        $wallet = $this->getActiveWallet();
        if (!$wallet || !$wallet->hasSufficientBalance($finalPrice)) {
            throw new \Exception('رصيد المحفظة غير كافٍ');
        }
        if ($finalPrice == 0 && $currentSubscription && $currentSubscription->paid_amount != 0) {
            throw new \Exception('لا يمكنك شراء اشتراك مجاني لانك مشترك بالفعل');
        }

        // خصم المبلغ من المحفظة
        $transaction = $this->deductWalletBalance(
            $finalPrice,
            "شراء اشتراك: {$package->name}",
            'subscription',
            null,
            [
                'package_id' => $packageId,
                'original_price' => $package->price,
                'discount' => $discount,
                'coupon_code' => $couponCode
            ]
        );

        // إنشاء الاشتراك
        $startDate = Carbon::now();
        if ($package->duration_type === 'monthly') {
            $endDate = $startDate->copy()->addMonths($package->duration_value);
        } else {
            $endDate = $startDate->copy()->addYears($package->duration_value);
        }

        // disable all active subscriptions
        // Note: This step is usually only needed if the new subscription is an UPGRADE,
        // but based on the previous logic, you intend to cancel the old one if it exists.
        $this->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        $subscription = Subscription::create([
            'user_id' => $this->id,
            'package_id' => $package->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
            'paid_amount' => $finalPrice,
            'payment_method' => 'wallet',
            'payment_reference' => $transaction->id,
            'auto_renew' => false,
        ]);

        // تحديث استخدام الكوبون
        if ($coupon) {
            $coupon->incrementUsage();
        }

        return $subscription;
    }
}
