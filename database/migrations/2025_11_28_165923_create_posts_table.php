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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('facebook_pages')->cascadeOnDelete();
            $table->string('post_id')->unique();
            $table->boolean('enabled')->default(true);
            $table->boolean('like_comment_enabled')->default(true);
            $table->boolean('reply_to_comment_enabled')->default(true);
            $table->boolean('reply_to_private_message_enabled')->default(false);
            $table->boolean('mention_enabled')->default(true);
            $table->boolean('share_enabled')->default(false);
            $table->text('comment_reply_template')->nullable();
            $table->text('private_message_template')->nullable();
            $table->text('mention_reply_template')->nullable();
            $table->json('keywords')->nullable();
            $table->json('exclude_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
