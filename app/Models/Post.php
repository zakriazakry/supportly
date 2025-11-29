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
        'post_id',
        'page_id',
        'enabled',
        'like_comment_enabled',
        'reply_to_comment_enabled',
        'reply_to_private_message_enabled',
        'mention_enabled',
        'share_enabled',
        'comment_reply_template',
        'private_message_template',
        'mention_reply_template',
        'keywords',
        'exclude_keywords',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'integer',
            'like_comment_enabled' => 'integer',
            'reply_to_comment_enabled' => 'integer',
            'reply_to_private_message_enabled' => 'integer',
            'mention_enabled' => 'integer',
            'share_enabled' => 'integer',
            'keywords' => 'array',
            'exclude_keywords' => 'array',
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
        return $this->belongsTo(FacebookPage::class, 'page_id', 'page_id');
    }

    /**
     * Get the reply states for this post.
     */
    public function replyStates(): HasMany
    {
        return $this->hasMany(PostReplyState::class, 'post_id');
    }
}
