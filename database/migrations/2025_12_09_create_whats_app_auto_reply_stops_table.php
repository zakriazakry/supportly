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
        Schema::create('whats_app_auto_reply_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whats_app_instance_id')
                ->constrained('whats_app_instances')
                ->onDelete('cascade')
                ->comment('معرف حساب الواتساب');

            $table->string('contact_number')->comment('رقم جهة الاتصال');
            $table->string('stop_reason')->default('keyword')->comment('سبب الإيقاف: keyword, owner_message');
            $table->string('keyword_triggered')->nullable()->comment('الكلمة التي تسببت في الإيقاف');
            $table->timestamp('stopped_at')->comment('وقت الإيقاف');
            $table->timestamp('resume_at')->comment('وقت استئناف الرد التلقائي');
            $table->boolean('is_active')->default(true)->comment('هل الإيقاف نشط');

            $table->timestamps();

            // Indexes for better performance
            $table->index(['whats_app_instance_id', 'contact_number', 'is_active']);
            $table->index('resume_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_auto_reply_stops');
    }
};
