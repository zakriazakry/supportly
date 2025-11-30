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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // المستخدم
            $table->foreignId('package_id')->constrained()->onDelete('cascade'); // الباقة

            $table->date('start_date'); // تاريخ البداية
            $table->date('end_date'); // تاريخ الانتهاء
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending'); // حالة الاشتراك

            $table->decimal('paid_amount', 10, 2); // المبلغ المدفوع
            $table->string('payment_method')->nullable(); // طريقة الدفع
            $table->string('payment_reference')->nullable(); // رقم مرجعي للدفع

            $table->boolean('auto_renew')->default(false); // تجديد تلقائي
            $table->timestamp('cancelled_at')->nullable(); // تاريخ الإلغاء
            $table->text('cancellation_reason')->nullable(); // سبب الإلغاء

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
