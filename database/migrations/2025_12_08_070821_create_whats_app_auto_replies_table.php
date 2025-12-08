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
        Schema::create('whats_app_auto_replies', function (Blueprint $table) {
            $table->id();
            // ربط بحساب الواتساب (يستخدم كمفتاح أساسي)
            $table->foreignId('whats_app_instance_id')
                ->primary()
                ->constrained('whats_app_instances')
                ->onDelete('cascade');

            // الحالة العامة
            $table->boolean('is_active')->default(false)->comment('تفعيل/تعطيل الرد التلقائي');

            // شروط إيقاف الرد
            $table->boolean('stop_on_owner_message')->default(true)->comment('إيقاف عند رسالة المالك');
            $table->boolean('stop_on_keyword')->default(false)->comment('إيقاف عند كلمة معينة');
            $table->json('stop_keywords')->nullable()->comment('كلمات الإيقاف - JSON array');
            $table->integer('stop_duration')->default(30)->comment('مدة التوقف بالدقائق');
            $table->integer('custom_stop_duration')->default(60)->comment('مدة مخصصة بالدقائق');

            // إعدادات السلوك
            $table->boolean('ignore_groups')->default(true)->comment('تجاهل رسائل المجموعات');
            $table->boolean('show_typing')->default(true)->comment('إظهار جاري الكتابة');
            $table->boolean('reply_once')->default(false)->comment('الرد مرة واحدة فقط لكل محادثة');
            $table->integer('reply_delay')->default(2)->comment('تأخير الرد بالثواني');

            // إحصائيات
            $table->unsignedInteger('total_replies')->default(0)->comment('إجمالي الردود المرسلة');

            $table->timestamps();

            // فهارس
            $table->index('is_active');
            $table->index('whats_app_instance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_auto_replies');
    }
};
