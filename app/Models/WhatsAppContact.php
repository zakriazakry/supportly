<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppContact extends Model
{
    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'instance_id',
        'jid',
        'phone_number',
        'name',
        'push_name',
        'profile_picture_url',
        'is_blocked',
        'is_business',
        'metadata',
        'last_message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_blocked' => 'boolean',
        'is_business' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /**
     * العلاقة مع الـ Instance
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id');
    }

    /**
     * الحصول على الاسم المعروض
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->push_name ?? $this->phone_number ?? $this->jid;
    }

    /**
     * Scope للجهات المحظورة
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Scope للحسابات التجارية
     */
    public function scopeBusiness($query)
    {
        return $query->where('is_business', true);
    }

    /**
     * حظر جهة الاتصال
     */
    public function block(): void
    {
        $this->update(['is_blocked' => true]);
    }

    /**
     * إلغاء حظر جهة الاتصال
     */
    public function unblock(): void
    {
        $this->update(['is_blocked' => false]);
    }
}
