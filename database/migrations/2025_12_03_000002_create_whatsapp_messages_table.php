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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('whats_app_instances')->onDelete('cascade');
            $table->string('message_id')->unique();
            $table->string('remote_jid'); // رقم المرسل/المستقبل
            $table->boolean('from_me')->default(false);
            $table->enum('message_type', ['text', 'image', 'video', 'audio', 'document', 'sticker', 'location', 'contact', 'buttons', 'list', 'poll'])->default('text');
            $table->text('message_content')->nullable();
            $table->json('message_data')->nullable(); // بيانات إضافية (media url, buttons, etc.)
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('remote_jid');
            $table->index('from_me');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
