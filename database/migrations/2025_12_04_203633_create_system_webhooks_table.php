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
        // جدول Webhooks الرئيسي
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_instance_id')->constrained('whats_app_instances')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('url', 500);
            $table->json('events'); // Array of event types
            $table->string('secret', 255)->nullable(); // For webhook signature verification
            $table->boolean('is_active')->default(true);
            $table->integer('total_calls')->default(0);
            $table->decimal('success_rate', 5, 2)->default(100.00);
            $table->timestamp('last_triggered')->nullable();
            $table->timestamps();

            $table->index('whatsapp_instance_id');
            $table->index('is_active');
        });

        // جدول سجل أحداث Webhooks
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
            $table->string('event_type', 100);
            $table->json('payload'); // Event data
            $table->integer('response_status')->nullable(); // HTTP status code
            $table->integer('response_time')->nullable(); // in milliseconds
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('webhook_id');
            $table->index('event_type');
            $table->index('created_at');
        });

        // جدول API Keys
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_instance_id')->constrained('whats_app_instances')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('key', 255)->unique(); // Hashed API key
            $table->json('permissions'); // Array of permissions
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used')->nullable();
            $table->timestamps();

            $table->index('whatsapp_instance_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('webhooks');
    }
};
