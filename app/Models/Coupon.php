<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Coupon extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'value_type',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    /**
     * Check if coupon is valid for use.
     */
    public function isValid(): bool
    {
        // التحقق من أن الكوبون نشط
        if (!$this->is_active) {
            return false;
        }

        // التحقق من تاريخ البداية
        if ($this->valid_from && Carbon::now()->isBefore($this->valid_from)) {
            return false;
        }

        // التحقق من تاريخ الانتهاء
        if ($this->valid_until && Carbon::now()->isAfter($this->valid_until)) {
            return false;
        }

        // التحقق من عدد مرات الاستخدام
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Get remaining uses.
     */
    public function getRemainingUses(): ?int
    {
        if (!$this->max_uses) {
            return null; // Unlimited
        }

        return max(0, $this->max_uses - $this->used_count);
    }

    /**
     * Scope for active coupons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for wallet recharge coupons.
     */
    public function scopeWalletRecharge($query)
    {
        return $query->where('type', 'wallet_recharge');
    }

    /**
     * Scope for subscription discount coupons.
     */
    public function scopeSubscriptionDiscount($query)
    {
        return $query->where('type', 'subscription_discount');
    }

    /**
     * Find coupon by code.
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
}
