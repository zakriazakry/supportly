<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whats_app_auto_reply_roles', function (Blueprint $table) {
            $table->id();
            // ربط بجدول الإعدادات العامة
            $table->foreignId('whats_app_auto_replies_id')->constrained('whats_app_auto_replies')->onDelete('cascade');
            // معلومات القاعدة الأساسية
            $table->string('name')->comment('اسم القاعدة');
            $table->boolean('is_active')->default(true)->comment('حالة القاعدة');
            $table->unsignedInteger('priority')->default(0)->comment('ترتيب الأولوية');

            // إعدادات المشغّل (Trigger)
            $table->enum('trigger_type', ['keyword', 'regex', 'all', 'contains'])->default('keyword')->comment('نوع المشغّل');
            $table->text('trigger_value')->nullable()->comment('قيمة المشغّل - نمط regex أو كلمات مفتاحية مفصولة بفاصلة');
            $table->json('trigger_keywords')->nullable()->comment('الكلمات المفتاحية - JSON array');
            $table->boolean('case_insensitive')->default(true)->comment('تجاهل حالة الأحرف');
            $table->boolean('exact_match')->default(false)->comment('مطابقة تامة');

            // إعدادات الرد
            $table->enum('response_type', ['text', 'media', 'template', 'buttons'])->default('text')->comment('نوع الرد');
            $table->text('response_value')->nullable()->comment('نص الرد');
            $table->boolean('random_response')->default(false)->comment('إرسال رد عشوائي');
            $table->json('alternative_responses')->nullable()->comment('الردود البديلة - JSON array');

            // إعدادات الوسائط
            $table->enum('media_type', ['image', 'video', 'document', 'audio'])->nullable()->comment('نوع الوسائط');
            $table->string('media_url', 500)->nullable()->comment('رابط الوسائط');
            $table->text('media_caption')->nullable()->comment('تعليق الوسائط');

            // إعدادات الأزرار
            $table->text('buttons_text')->nullable()->comment('نص رسالة الأزرار');
            $table->json('buttons')->nullable()->comment('الأزرار - JSON array [{text, id}]');

            // إعدادات الجدولة
            $table->boolean('has_schedule')->default(false)->comment('تفعيل الجدولة');
            $table->time('schedule_start')->nullable()->comment('وقت البداية');
            $table->time('schedule_end')->nullable()->comment('وقت النهاية');
            $table->json('schedule_days')->nullable()->comment('أيام التفعيل - JSON array [sat, sun, mon, ...]');

            $table->timestamps();

            // فهارس
            $table->index('is_active');
            $table->index('trigger_type');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_auto_reply_roles');
    }
};
