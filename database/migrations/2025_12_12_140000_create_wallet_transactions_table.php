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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']); // credit = إضافة, debit = خصم
            $table->decimal('amount', 20, 2); // المبلغ
            $table->decimal('balance_before', 20, 2); // الرصيد قبل المعاملة
            $table->decimal('balance_after', 20, 2); // الرصيد بعد المعاملة
            $table->string('description'); // وصف المعاملة
            $table->string('reference_type')->nullable(); // نوع المرجع (coupon, subscription, etc.)
            $table->unsignedBigInteger('reference_id')->nullable(); // معرف المرجع
            $table->json('metadata')->nullable(); // بيانات إضافية
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
