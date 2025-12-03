<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppInstance extends Model
{
    protected $table = 'whats_app_instances';

    protected $fillable = [
        'user_id',
        'instance_name',
        'token',
        'status',
        'qr_code',
        'phone_number',
        'integration_type',
        'profile_name',
        'profile_picture_url',
        'last_connected_at',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_connected_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'token',
        'qr_code',
    ];

    protected $appends = [
        'is_connected',
        'status_label',
    ];

    /**
     * العلاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع الرسائل
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'instance_id');
    }

    /**
     * العلاقة مع جهات الاتصال
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(WhatsAppContact::class, 'instance_id');
    }

    /**
     * العلاقة مع المحادثات
     */
    public function chats(): HasMany
    {
        return $this->hasMany(WhatsAppChat::class, 'instance_id');
    }

    /**
     * التحقق من حالة الاتصال
     */
    public function getIsConnectedAttribute(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * الحصول على تسمية الحالة بالعربية
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'في الانتظار',
            'connected' => 'متصل',
            'disconnected' => 'غير متصل',
            default => 'غير معروف',
        };
    }

    /**
     * الحصول على عدد الرسائل المرسلة اليوم
     */
    public function getTodayMessagesCount(): int
    {
        return $this->messages()
            ->where('from_me', true)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * الحصول على عدد الرسائل الواردة غير المقروءة
     */
    public function getUnreadMessagesCount(): int
    {
        return $this->messages()
            ->where('from_me', false)
            ->where('status', '!=', 'read')
            ->count();
    }

    /**
     * Scope للحصول على الـ instances النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للحصول على الـ instances المتصلة فقط
     */
    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    /**
     * تحديث حالة الاتصال
     */
    public function updateConnectionStatus(string $status, array $data = []): void
    {
        $updateData = ['status' => $status];

        if ($status === 'connected') {
            $updateData['last_connected_at'] = now();
            if (isset($data['phone_number'])) {
                $updateData['phone_number'] = $data['phone_number'];
            }
            if (isset($data['profile_name'])) {
                $updateData['profile_name'] = $data['profile_name'];
            }
            if (isset($data['profile_picture_url'])) {
                $updateData['profile_picture_url'] = $data['profile_picture_url'];
            }
        }

        $this->update($updateData);
    }
}
