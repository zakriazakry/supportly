<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'page_id',
        'template_id',
        'content',
        'media_url',
        'scheduled_at',
        'posted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'posted' => 'boolean',
        ];
    }

    /**
     * Get the Facebook page for this scheduled post.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }

    /**
     * Get the template for this scheduled post (if any).
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AutoReplyTemplate::class, 'template_id');
    }
}
