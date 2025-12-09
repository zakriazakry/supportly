<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    public $timestamps = false; // نستخدم created_at فقط

    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'response_status',
        'response_time',
        'success',
        'error_message'
    ];

    protected $casts = [
        'payload' => 'array',
        'response_status' => 'integer',
        'response_time' => 'integer',
        'success' => 'boolean',
        'created_at' => 'datetime'
    ];

    /**
     * Get the webhook that owns the event
     */
    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Check if the event was successful
     */
    public function isSuccessful()
    {
        return $this->success === true;
    }

    /**
     * Get human-readable response time
     */
    public function getFormattedResponseTime()
    {
        if ($this->response_time < 1000) {
            return $this->response_time . 'ms';
        }

        return round($this->response_time / 1000, 2) . 's';
    }
}
