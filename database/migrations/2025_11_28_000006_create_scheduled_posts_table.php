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
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('facebook_pages')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('auto_reply_templates')->onDelete('set null');
            $table->text('content');
            $table->text('media_url')->nullable();
            $table->datetime('scheduled_at');
            $table->tinyInteger('posted')->default(0); // 0 = not posted, 1 = posted
            $table->timestamps();

            // Add index for scheduled posts lookup
            $table->index(['scheduled_at', 'posted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
