<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'page_id',
        'facebook_post_id',
        'allow_like',
        'allow_comment',
        'allow_reply',
        'for_ever_one',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allow_like' => 'boolean',
            'allow_comment' => 'boolean',
            'allow_reply' => 'boolean',
            'for_ever_one' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Facebook page that owns the post.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }

    /**
     * Get the reply states for this post.
     */
    public function replyStates(): HasMany
    {
        return $this->hasMany(PostReplyState::class, 'post_id');
    }
}
