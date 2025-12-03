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
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('whats_app_instances')->onDelete('cascade');
            $table->string('jid')->index(); // WhatsApp JID
            $table->string('phone_number')->nullable();
            $table->string('name')->nullable();
            $table->string('push_name')->nullable(); // الاسم الذي يظهر في WhatsApp
            $table->string('profile_picture_url')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_business')->default(false);
            $table->json('metadata')->nullable(); // بيانات إضافية
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['instance_id', 'jid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contacts');
    }
};
