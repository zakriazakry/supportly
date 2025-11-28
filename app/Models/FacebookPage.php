<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'facebook_account_id',
        'page_id',
        'name',
        'access_token',
        'category',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
    ];

    /**
     * Get the Facebook account that owns the page.
     */
    public function facebookAccount(): BelongsTo
    {
        return $this->belongsTo(FacebookAccount::class);
    }

    /**
     * Get the posts for this page.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'page_id');
    }

    /**
     * Get the auto reply templates for this page.
     */
    public function autoReplyTemplates(): HasMany
    {
        return $this->hasMany(AutoReplyTemplate::class, 'page_id');
    }

    /**
     * Get the auto replies for this page.
     */
    public function autoReplies(): HasMany
    {
        return $this->hasMany(AutoReply::class, 'page_id');
    }

    /**
     * Get the scheduled posts for this page.
     */
    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class, 'page_id');
    }

    /**
     * Get the logs for this page.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'page_id');
    }
}
