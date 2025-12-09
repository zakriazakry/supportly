<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class WhatsAppAiReply extends Model
{
    protected $table = 'whats_app_ai_replies';

    protected $fillable = [
        'whats_app_instance_id',
        'is_active',
        // إعدادات المزود
        'provider',
        'api_key',
        'model',
        // إعدادات الاستجابة
        'temperature',
        'max_tokens',
        'response_delay',
        // تعليمات النظام
        'system_prompt',
        // شروط إيقاف الرد
        'stop_on_owner_message',
        'stop_on_keyword',
        'stop_keywords',
        'stop_duration',
        'custom_stop_duration',
        // الإعدادات المتقدمة
        'include_context',
        'context_messages_count',
        'show_typing',
        'ignore_groups',
        'only_first_message',
        'excluded_numbers',
        // إحصائيات
        'total_messages',
        'total_tokens_used',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'response_delay' => 'integer',
        'stop_on_owner_message' => 'boolean',
        'stop_on_keyword' => 'boolean',
        'stop_keywords' => 'array',
        'stop_duration' => 'integer',
        'custom_stop_duration' => 'integer',
        'include_context' => 'boolean',
        'context_messages_count' => 'integer',
        'show_typing' => 'boolean',
        'ignore_groups' => 'boolean',
        'only_first_message' => 'boolean',
        'excluded_numbers' => 'array',
        'total_messages' => 'integer',
        'total_tokens_used' => 'integer',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * العلاقة مع الـ Instance
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'whats_app_instance_id');
    }

    /**
     * تشفير مفتاح API عند الحفظ
     */
    public function setApiKeyAttribute($value): void
    {
        if ($value) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * فك تشفير مفتاح API عند القراءة
     */
    public function getApiKeyAttribute($value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * الحصول على مفتاح API بدون فك التشفير (للتحقق من وجوده)
     */
    public function hasApiKey(): bool
    {
        return !empty($this->attributes['api_key'] ?? null);
    }

    /**
     * التحقق مما إذا كان الرقم مستثنى
     */
    public function isNumberExcluded(string $number): bool
    {
        if (empty($this->excluded_numbers)) {
            return false;
        }

        // تنظيف الرقم
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);

        foreach ($this->excluded_numbers as $excluded) {
            $cleanExcluded = preg_replace('/[^0-9]/', '', $excluded);
            if ($cleanNumber === $cleanExcluded || str_ends_with($cleanNumber, $cleanExcluded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * زيادة عداد الرسائل والرموز
     */
    public function incrementStats(int $tokens = 0): void
    {
        $this->increment('total_messages');
        if ($tokens > 0) {
            $this->increment('total_tokens_used', $tokens);
        }
    }

    /**
     * الحصول على النماذج المتاحة حسب المزود
     */
    public static function getAvailableModels(string $provider): array
    {
        return match ($provider) {
            'openai' => [
                'gpt-4o' => 'GPT-4o (الأحدث)',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ],
            'ONU' => [
                'ollama' => 'Ollama (محلي)',
            ],
            default => [],
        };
    }
}
