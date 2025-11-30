<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        'email',
        'password',
        'phone',
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
            default => 0,
        };

        return $count < $limit;
    }
}
