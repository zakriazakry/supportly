<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutoReplyTemplate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'page_id',
        'type',
        'name',
        'content',
        'media_url',
    ];

    /**
     * Get the user that owns the template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Facebook page that owns the template.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }

    /**
     * Get the auto replies using this template.
     */
    public function autoReplies(): HasMany
    {
        return $this->hasMany(AutoReply::class, 'template_id');
    }

    /**
     * Get the scheduled posts using this template.
     */
    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class, 'template_id');
    }
}
