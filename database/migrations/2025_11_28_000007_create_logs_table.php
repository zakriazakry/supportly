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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('facebook_pages')->onDelete('cascade');
            $table->enum('type', ['comment', 'message', 'post', 'error']);
            $table->string('reference_id', 255)->nullable();
            $table->text('content')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Add index for log queries
            $table->index(['page_id', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
