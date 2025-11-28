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
            $table->string('facebook_post_id')->unique();
            $table->boolean('allow_like')->default(true);
            $table->boolean('allow_comment')->default(true);
            $table->boolean('allow_reply')->default(true);
            $table->boolean('for_ever_one')->default(false);
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
