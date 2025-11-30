<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyUsageStat extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'auto_replies_count',
        'messages_sent',
        'comments_replied',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'auto_replies_count' => 'integer',
            'messages_sent' => 'integer',
            'comments_replied' => 'integer',
        ];
    }

    /**
     * Get the user that owns the stats.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create current month stats for a user.
     */
    public static function getCurrentMonthStats($userId)
    {
        return self::firstOrCreate([
            'user_id' => $userId,
            'year' => now()->year,
            'month' => now()->month,
        ]);
    }

    /**
     * Increment auto replies count.
     */
    public function incrementAutoReplies($count = 1): void
    {
        $this->increment('auto_replies_count', $count);
    }

    /**
     * Increment messages sent count.
     */
    public function incrementMessagesSent($count = 1): void
    {
        $this->increment('messages_sent', $count);
    }

    /**
     * Increment comments replied count.
     */
    public function incrementCommentsReplied($count = 1): void
    {
        $this->increment('comments_replied', $count);
    }

    /**
     * Check if user has reached monthly limit.
     */
    public function hasReachedLimit($limitType, $limit): bool
    {
        if ($limit === null) {
            return false; // Unlimited
        }

        return $this->$limitType >= $limit;
    }
}
