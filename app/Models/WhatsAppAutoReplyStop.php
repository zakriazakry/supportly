<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class WhatsAppAutoReplyStop extends Model
{
    protected $table = 'whats_app_auto_reply_stops';

    protected $fillable = [
        'whats_app_instance_id',
        'contact_number',
        'stop_reason',
        'keyword_triggered',
        'stopped_at',
        'resume_at',
        'is_active',
    ];

    protected $casts = [
        'stopped_at' => 'datetime',
        'resume_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة مع الـ Instance
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'whats_app_instance_id');
    }

    /**
     * التحقق من أن الإيقاف لا يزال نشطاً
     */
    public function isStillActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // إذا انتهت مدة الإيقاف
        if (now()->greaterThanOrEqualTo($this->resume_at)) {
            $this->deactivate();
            return false;
        }

        return true;
    }

    /**
     * إلغاء تفعيل الإيقاف
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * التحقق من وجود إيقاف نشط لجهة اتصال محددة
     */
    public static function hasActiveStop(int $instanceId, string $contactNumber): bool
    {
        $stop = static::where('whats_app_instance_id', $instanceId)
            ->where('contact_number', $contactNumber)
            ->where('resume_at', '>', now())
            ->first();

        return $stop !== null;
    }

    /**
     * إنشاء إيقاف جديد لجهة اتصال
     */
    public static function createStop(
        int $instanceId,
        string $contactNumber,
        int $durationMinutes,
        string $reason = 'keyword',
        ?string $keyword = null
    ): self {
        // إلغاء تفعيل أي إيقافات سابقة
        static::where('whats_app_instance_id', $instanceId)
            ->where('contact_number', $contactNumber)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // إنشاء إيقاف جديد
        return static::create([
            'whats_app_instance_id' => $instanceId,
            'contact_number' => $contactNumber,
            'stop_reason' => $reason,
            'keyword_triggered' => $keyword,
            'stopped_at' => now(),
            'resume_at' => now()->addMinutes($durationMinutes),
            'is_active' => true,
        ]);
    }

    /**
     * Scope للإيقافات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('resume_at', '>', now());
    }

    /**
     * Scope للإيقافات المنتهية
     */
    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
            ->where('resume_at', '<=', now());
    }
}
