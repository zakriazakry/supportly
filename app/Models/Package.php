<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'duration_type',
        'duration_value',
        'feature_24_support',
        'feature_unlimited_replies',
        'feature_advanced_reports',
        'feature_multiple_accounts',
        'feature_custom_templates',
        'feature_priority_processing',
        'limit_facebook_accounts',
        'limit_facebook_pages',
        'limit_auto_replies_per_month',
        'limit_templates',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'feature_24_support' => 'boolean',
            'feature_unlimited_replies' => 'boolean',
            'feature_advanced_reports' => 'boolean',
            'feature_multiple_accounts' => 'boolean',
            'feature_custom_templates' => 'boolean',
            'feature_priority_processing' => 'boolean',
            'limit_facebook_accounts' => 'integer',
            'limit_facebook_pages' => 'integer',
            'limit_auto_replies_per_month' => 'integer',
            'limit_templates' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the subscriptions for this package.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get active subscriptions for this package.
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('status', 'active');
    }

    /**
     * Scope a query to only include active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order packages by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get all features as an array.
     */
    public function getFeatures(): array
    {
        return [
            '24_support' => $this->feature_24_support,
            'unlimited_replies' => $this->feature_unlimited_replies,
            'advanced_reports' => $this->feature_advanced_reports,
            'multiple_accounts' => $this->feature_multiple_accounts,
            'custom_templates' => $this->feature_custom_templates,
            'priority_processing' => $this->feature_priority_processing,
        ];
    }

    /**
     * Get all limits as an array.
     */
    public function getLimits(): array
    {
        return [
            'facebook_accounts' => $this->limit_facebook_accounts,
            'facebook_pages' => $this->limit_facebook_pages,
            'auto_replies_per_month' => $this->limit_auto_replies_per_month,
            'templates' => $this->limit_templates,
        ];
    }
}
