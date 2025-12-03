<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'instance_id',
        'message_id',
        'remote_jid',
        'from_me',
        'message_type',
        'message_content',
        'message_data',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'message_data' => 'array',
        'from_me' => 'boolean',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    protected $appends = [
        'is_sent',
        'is_delivered',
        'is_read',
    ];

    /**
     * العلاقة مع الـ Instance
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id');
    }

    /**
     * التحقق من إرسال الرسالة
     */
    public function getIsSentAttribute(): bool
    {
        return in_array($this->status, ['sent', 'delivered', 'read']);
    }

    /**
     * التحقق من توصيل الرسالة
     */
    public function getIsDeliveredAttribute(): bool
    {
        return in_array($this->status, ['delivered', 'read']);
    }

    /**
     * التحقق من قراءة الرسالة
     */
    public function getIsReadAttribute(): bool
    {
        return $this->status === 'read';
    }

    /**
     * Scope للرسائل المرسلة
     */
    public function scopeSent($query)
    {
        return $query->where('from_me', true);
    }

    /**
     * Scope للرسائل الواردة
     */
    public function scopeReceived($query)
    {
        return $query->where('from_me', false);
    }

    /**
     * Scope للرسائل غير المقروءة
     */
    public function scopeUnread($query)
    {
        return $query->where('from_me', false)
            ->where('status', '!=', 'read');
    }

    /**
     * Scope للرسائل من جهة اتصال معينة
     */
    public function scopeFromContact($query, string $jid)
    {
        return $query->where('remote_jid', $jid);
    }

    /**
     * تحديث حالة الرسالة
     */
    public function updateStatus(string $status): void
    {
        $updateData = ['status' => $status];

        if ($status === 'sent' && !$this->sent_at) {
            $updateData['sent_at'] = now();
        } elseif ($status === 'delivered' && !$this->delivered_at) {
            $updateData['delivered_at'] = now();
        } elseif ($status === 'read' && !$this->read_at) {
            $updateData['read_at'] = now();
        }

        $this->update($updateData);
    }
}
