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
        Schema::create('facebook_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_account_id')->constrained()->onDelete('cascade');
            $table->string('page_id', 100);
            $table->string('name', 255);
            $table->text('access_token'); // Page Access Token (long-lived)
            $table->string('category', 100)->nullable();
            $table->timestamps();

            // Add index for faster lookups
            $table->index('page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_pages');
    }
};
