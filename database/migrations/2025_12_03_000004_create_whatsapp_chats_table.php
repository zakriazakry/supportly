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
        Schema::create('whatsapp_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('whats_app_instances')->onDelete('cascade');
            $table->string('jid')->index(); // Chat JID
            $table->string('name')->nullable();
            $table->boolean('is_group')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->integer('unread_count')->default(0);
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['instance_id', 'jid']);

            // Indexes
            $table->index('is_group');
            $table->index('is_archived');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chats');
    }
};
