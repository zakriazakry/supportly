<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReplyState extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'reply',
        'if_has',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'if_has' => 'boolean',
        ];
    }

    /**
     * Get the post that owns the reply state.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
