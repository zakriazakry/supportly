<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_instance_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'total_calls',
        'success_rate',
        'last_triggered'
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'total_calls' => 'integer',
        'success_rate' => 'decimal:2',
        'last_triggered' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the WhatsApp instance that owns the webhook
     */
    public function whatsappInstance()
    {
        return $this->belongsTo(WhatsAppInstance::class, 'whatsapp_instance_id');
    }

    /**
     * Get the webhook events
     */
    public function webhookEvents()
    {
        return $this->hasMany(WebhookEvent::class);
    }

    /**
     * Check if webhook is subscribed to a specific event
     */
    public function isSubscribedTo($eventType)
    {
        return in_array($eventType, $this->events);
    }

    /**
     * Get recent successful events
     */
    public function recentSuccessfulEvents($limit = 10)
    {
        return $this->webhookEvents()
            ->where('success', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent failed events
     */
    public function recentFailedEvents($limit = 10)
    {
        return $this->webhookEvents()
            ->where('success', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
