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
        Schema::create('whats_app_ai_replies', function (Blueprint $table) {
            $table->id();

            // ربط بحساب الواتساب
            $table->foreignId('whats_app_instance_id')->constrained('whats_app_instances')->onDelete('cascade');

            // الحالة العامة
            $table->boolean('is_active')->default(false)->comment('تفعيل/تعطيل الذكاء الاصطناعي');

            // إعدادات المزود
            $table->enum('provider', ['openai', 'ONU'])->default('openai')->comment('مزود الذكاء الاصطناعي');
            $table->string('api_key', 500)->nullable()->comment('مفتاح API - مشفر');
            $table->string('model')->default('gpt-4o-mini')->comment('النموذج المستخدم');

            // إعدادات الاستجابة
            $table->decimal('temperature', 3, 2)->default(0.70)->comment('مدى الإبداع (0-2)');
            $table->unsignedInteger('max_tokens')->default(1000)->comment('الحد الأقصى للرموز');
            $table->unsignedSmallInteger('response_delay')->default(2)->comment('تأخير الرد بالثواني');

            // تعليمات النظام
            $table->text('system_prompt')->nullable()->comment('تعليمات النظام للذكاء الاصطناعي');

            // شروط إيقاف الرد
            $table->boolean('stop_on_owner_message')->default(true)->comment('إيقاف عند رسالة المالك');
            $table->boolean('stop_on_keyword')->default(false)->comment('إيقاف عند كلمة معينة');
            $table->json('stop_keywords')->nullable()->comment('كلمات الإيقاف - JSON array');
            $table->integer('stop_duration')->default(30)->comment('مدة التوقف بالدقائق');
            $table->integer('custom_stop_duration')->default(60)->comment('مدة مخصصة بالدقائق');

            // الإعدادات المتقدمة
            $table->boolean('include_context')->default(true)->comment('تضمين سياق المحادثة');
            $table->unsignedSmallInteger('context_messages_count')->default(5)->comment('عدد الرسائل السابقة للسياق');
            $table->boolean('show_typing')->default(true)->comment('إظهار جاري الكتابة');
            $table->boolean('ignore_groups')->default(true)->comment('تجاهل رسائل المجموعات');
            $table->boolean('only_first_message')->default(false)->comment('الرد على الرسالة الأولى فقط');
            $table->json('excluded_numbers')->nullable()->comment('الأرقام المستثناة - JSON array');

            // إحصائيات
            $table->unsignedInteger('total_messages')->default(0)->comment('إجمالي الرسائل المعالجة');
            $table->unsignedInteger('total_tokens_used')->default(0)->comment('إجمالي الرموز المستخدمة');

            $table->timestamps();

            // فهارس
            $table->index('is_active');
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_ai_replies');
    }
};
