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
        Schema::create('auto_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('auto_reply_templates')->onDelete('cascade');
            $table->enum('trigger_type', ['comment', 'message', 'post']);
            $table->string('trigger_keyword', 255)->nullable();
            $table->foreignId('page_id')->constrained('facebook_pages')->onDelete('cascade');
            $table->tinyInteger('active')->default(1); // 1 = active, 0 = inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_replies');
    }
};
