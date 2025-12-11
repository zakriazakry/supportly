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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الباقة (مثل: الباقة الاحترافية)
            $table->text('description')->nullable(); // وصف الباقة
            $table->decimal('price', 10, 2); // السعر
            $table->string('currency', 10)->default('LYD'); // العملة (دينار ليبي)
            $table->enum('duration_type', ['monthly', 'yearly'])->default('monthly'); // نوع المدة (Fixed the name here)
            $table->integer('duration_value')->default(1); // قيمة المدة

            // المميزات
            $table->boolean('feature_24_support')->default(false); // دعم فني 24 ساعة
            $table->boolean('feature_unlimited_replies')->default(false); // ردود تلقائية غير محدودة
            $table->boolean('feature_advanced_reports')->default(false); // تقارير وإحصائيات متقدمة
            $table->boolean('feature_multiple_accounts')->default(false); // ربط حسابات متعددة
            $table->boolean('feature_custom_templates')->default(false); // قوالب رسائل مخصصة
            $table->boolean('feature_priority_processing')->default(false); // أولوية في المعالجة

            // القيود
            $table->integer('limit_facebook_accounts')->nullable(); // عدد حسابات فيسبوك المسموحة (null = غير محدود)
            $table->integer('limit_facebook_pages')->nullable(); // عدد صفحات فيسبوك المسموحة (null = غير محدود)
            $table->integer('limit_auto_replies_per_month')->nullable(); // عدد الردود التلقائية شهرياً (null = غير محدود)
            $table->integer('limit_templates')->nullable(); // عدد القوالب المسموحة (null = غير محدود)

            // whatsapp
            $table->boolean('feature_whatsapp')->default(false); // دعم واتساب
            $table->boolean('feature_whatsapp_auto_reply')->default(false); // الرد التلقائي على واتساب
            $table->boolean('feature_whatsapp_ai_reply')->default(false); // ردود AI على واتساب
            $table->boolean('feature_whatsapp_openai_support')->default(false); // دعم OpenAI على واتساب
            $table->boolean('feature_whatsapp_developer')->default(false); // دعم المطورين على واتساب
            $table->integer('limit_whatsapp_accounts')->default(1); // عدد حسابات واتساب المسموحة
            $table->integer('limit_whatsapp_auto_replies_per_month')->nullable()->default(1000); // عدد الردود التلقائية شهرياً على واتساب

            // Additional Fields
            $table->boolean('is_active')->default(true); // هل الباقة نشطة
            $table->integer('sort_order')->default(0); // ترتيب العرض
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
