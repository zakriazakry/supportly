<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppChat extends Model
{
    protected $table = 'whatsapp_chats';

    protected $fillable = [
        'instance_id',
        'jid',
        'name',
        'is_group',
        'is_archived',
        'unread_count',
        'last_message',
        'last_message_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_group' => 'boolean',
        'is_archived' => 'boolean',
        'unread_count' => 'integer',
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
     * Scope للمجموعات
     */
    public function scopeGroups($query)
    {
        return $query->where('is_group', true);
    }

    /**
     * Scope للمحادثات الفردية
     */
    public function scopeIndividual($query)
    {
        return $query->where('is_group', false);
    }

    /**
     * Scope للمحادثات المؤرشفة
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope للمحادثات غير المقروءة
     */
    public function scopeUnread($query)
    {
        return $query->where('unread_count', '>', 0);
    }

    /**
     * أرشفة المحادثة
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * إلغاء أرشفة المحادثة
     */
    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * تحديث عدد الرسائل غير المقروءة
     */
    public function updateUnreadCount(int $count): void
    {
        $this->update(['unread_count' => $count]);
    }

    /**
     * تصفير عدد الرسائل غير المقروءة
     */
    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
    }
}
