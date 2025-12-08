<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppAutoReplyRoles extends Model
{
    protected $table = 'whats_app_auto_reply_roles';

    protected $fillable = [
        'whats_app_auto_replies_id',
        'name',
        'is_active',
        'priority',
        // إعدادات المشغّل
        'trigger_type',
        'trigger_value',
        'trigger_keywords',
        'case_insensitive',
        'exact_match',
        // إعدادات الرد
        'response_type',
        'response_value',
        'random_response',
        'alternative_responses',
        // إعدادات الوسائط
        'media_type',
        'media_url',
        'media_caption',
        // إعدادات الأزرار
        'buttons_text',
        'buttons',
        // إعدادات الجدولة
        'has_schedule',
        'schedule_start',
        'schedule_end',
        'schedule_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'trigger_keywords' => 'array',
        'case_insensitive' => 'boolean',
        'exact_match' => 'boolean',
        'random_response' => 'boolean',
        'alternative_responses' => 'array',
        'buttons' => 'array',
        'has_schedule' => 'boolean',
        'schedule_days' => 'array',
    ];

    /**
     * العلاقة مع إعدادات الرد التلقائي
     */
    public function autoReply(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAutoReply::class, 'whats_app_auto_replies_id', 'whats_app_instance_id');
    }

    /**
     * التحقق من تفعيل القاعدة حسب الجدولة
     */
    public function isScheduleActive(): bool
    {
        if (!$this->has_schedule) {
            return true;
        }

        $now = now();
        $currentDay = strtolower($now->format('D')); // sat, sun, mon, etc.
        $currentTime = $now->format('H:i:s');

        // التحقق من اليوم
        if ($this->schedule_days && !in_array($currentDay, $this->schedule_days)) {
            return false;
        }

        // التحقق من الوقت
        if ($this->schedule_start && $this->schedule_end) {
            return $currentTime >= $this->schedule_start && $currentTime <= $this->schedule_end;
        }

        return true;
    }

    /**
     * التحقق من تطابق الرسالة مع القاعدة
     */
    public function matchesMessage(string $message): bool
    {
        if (!$this->is_active || !$this->isScheduleActive()) {
            return false;
        }

        $originalMessage = $message;
        if ($this->case_insensitive) {
            $message = mb_strtolower($message);
        }

        return match ($this->trigger_type) {
            'all' => true,
            'keyword' => $this->matchesKeywords($message),
            'contains' => $this->containsKeywords($message),
            'regex' => $this->matchesRegex($originalMessage),
            default => false,
        };
    }

    /**
     * التحقق من تطابق الكلمات المفتاحية
     */
    protected function matchesKeywords(string $message): bool
    {
        $keywords = $this->trigger_keywords ?? [];

        if (empty($keywords) && $this->trigger_value) {
            $keywords = array_map('trim', explode(',', $this->trigger_value));
        }

        foreach ($keywords as $keyword) {
            $keyword = $this->case_insensitive ? mb_strtolower($keyword) : $keyword;

            if ($this->exact_match) {
                if ($message === $keyword) {
                    return true;
                }
            } else {
                if (str_contains($message, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * التحقق من احتواء الرسالة على كلمات مفتاحية
     */
    protected function containsKeywords(string $message): bool
    {
        $keywords = $this->trigger_keywords ?? [];

        if (empty($keywords) && $this->trigger_value) {
            $keywords = array_map('trim', explode(',', $this->trigger_value));
        }

        foreach ($keywords as $keyword) {
            $keyword = $this->case_insensitive ? mb_strtolower($keyword) : $keyword;
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من تطابق Regex
     */
    protected function matchesRegex(string $message): bool
    {
        if (!$this->trigger_value) {
            return false;
        }

        $pattern = $this->trigger_value;
        $flags = $this->case_insensitive ? 'i' : '';

        return (bool) preg_match("/{$pattern}/{$flags}", $message);
    }

    /**
     * الحصول على الرد المناسب
     */
    public function getResponse(): ?string
    {
        if ($this->random_response && !empty($this->alternative_responses)) {
            $responses = array_merge([$this->response_value], $this->alternative_responses);
            return $responses[array_rand($responses)];
        }

        return $this->response_value;
    }

    /**
     * Scope للقواعد النشطة مرتبة حسب الأولوية
     */
    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', true)->orderBy('priority');
    }
}
