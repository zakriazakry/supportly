<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppAutoReply extends Model
{
    protected $table = 'whats_app_auto_replies';

    // لا يوجد id في هذا الجدول - يستخدم whats_app_instance_id كمفتاح أساسي
    protected $primaryKey = 'whats_app_instance_id';
    public $incrementing = false;

    protected $fillable = [
        'whats_app_instance_id',
        'is_active',
        'stop_on_owner_message',
        'stop_on_keyword',
        'stop_keywords',
        'stop_duration',
        'custom_stop_duration',
        'ignore_groups',
        'show_typing',
        'reply_once',
        'reply_delay',
        'total_replies',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'stop_on_owner_message' => 'boolean',
        'stop_on_keyword' => 'boolean',
        'stop_keywords' => 'array',
        'ignore_groups' => 'boolean',
        'show_typing' => 'boolean',
        'reply_once' => 'boolean',
        'stop_duration' => 'integer',
        'custom_stop_duration' => 'integer',
        'reply_delay' => 'integer',
        'total_replies' => 'integer',
    ];

    /**
     * العلاقة مع الـ Instance
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'whats_app_instance_id');
    }

    /**
     * العلاقة مع قواعد الرد التلقائي
     */
    public function rules(): HasMany
    {
        return $this->hasMany(WhatsAppAutoReplyRoles::class, 'whats_app_auto_replies_id', 'whats_app_instance_id');
    }

    /**
     * الحصول على القواعد النشطة فقط
     */
    public function activeRules(): HasMany
    {
        return $this->rules()->where('is_active', true)->orderBy('priority');
    }

    /**
     * زيادة عداد الردود
     */
    public function incrementReplies(): void
    {
        $this->increment('total_replies');
    }
}
