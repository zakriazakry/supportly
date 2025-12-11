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
        Schema::create('monthly_usage_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('year'); // السنة
            $table->integer('month'); // الشهر (1-12)
            $table->integer('auto_replies_count')->default(0); // عدد الردود التلقائية
            $table->integer('messages_sent')->default(0); // عدد الرسائل المرسلة
            $table->integer('comments_replied')->default(0); // عدد التعليقات المجاب عليها
            $table->integer('webhook_calls')->default(0); // عدد الاتصالات بالhook
            $table->integer('webhook_success_rate')->default(0); // نسبة نجاح الاتصالات بالhook
            $table->timestamps();

            // Index مركب للبحث السريع
            $table->unique(['user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_usage_stats');
    }
};
