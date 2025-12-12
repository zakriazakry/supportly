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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // كود الكوبون
            $table->enum('type', ['wallet_recharge', 'subscription_discount']); // نوع الكوبون
            $table->decimal('value', 10, 2); // القيمة (مبلغ أو نسبة)
            $table->enum('value_type', ['fixed', 'percentage'])->default('fixed'); // نوع القيمة
            $table->integer('max_uses')->default(1); // الحد الأقصى للاستخدام (null = غير محدود)
            $table->integer('used_count')->default(1); // عدد مرات الاستخدام
            $table->timestamp('valid_from')->nullable(); // تاريخ بداية الصلاحية
            $table->timestamp('valid_until')->nullable(); // تاريخ نهاية الصلاحية
            $table->boolean('is_active')->default(true); // حالة الكوبون
            $table->text('description')->nullable(); // وصف الكوبون
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
