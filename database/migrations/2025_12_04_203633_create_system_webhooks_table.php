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
        Schema::create('system_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->default('');
            $table->text('description')->nullable();
            $table->string('url')->unique();
            $table->enum('method', ['GET', 'POST', 'PUT', 'DELETE'])->default('POST');
            $table->json('headers')->nullable();
            $table->json('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_webhooks');
    }
};
