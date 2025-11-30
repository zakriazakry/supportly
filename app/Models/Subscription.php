<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'status',
        'paid_amount',
        'payment_method',
        'payment_reference',
        'auto_renew',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'paid_amount' => 'decimal:2',
            'auto_renew' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package for this subscription.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>=', now());
    }

    /**
     * Scope a query to only include expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'active')
                    ->where('end_date', '<', now());
            });
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date >= now();
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->end_date < now();
    }

    /**
     * Get remaining days.
     */
    public function getRemainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Get remaining days attribute.
     */
    public function getRemainingDaysAttribute(): int
    {
        return $this->getRemainingDays();
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(string $reason = null): bool
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->auto_renew = false;

        return $this->save();
    }

    /**
     * Renew the subscription.
     */
    public function renew(): bool
    {
        if (!$this->package) {
            return false;
        }

        $this->start_date = $this->end_date->addDay();

        if ($this->package->duration_type === 'monthly') {
            $this->end_date = $this->start_date->copy()->addMonths($this->package->duration_value);
        } else {
            $this->end_date = $this->start_date->copy()->addYears($this->package->duration_value);
        }

        $this->status = 'active';

        return $this->save();
    }

    /**
     * Check and update expired subscriptions.
     */
    public static function checkExpiredSubscriptions(): void
    {
        self::where('status', 'active')
            ->where('end_date', '<', now())
            ->update(['status' => 'expired']);
    }
}
