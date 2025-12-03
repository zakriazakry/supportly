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
        Schema::table('whats_app_instances', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('status');
            $table->string('integration_type')->default('WHATSAPP-BAILEYS')->after('phone_number');
            $table->string('profile_name')->nullable()->after('integration_type');
            $table->string('profile_picture_url')->nullable()->after('profile_name');
            $table->timestamp('last_connected_at')->nullable()->after('profile_picture_url');
            $table->json('settings')->nullable()->after('last_connected_at');
            $table->boolean('is_active')->default(true)->after('settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whats_app_instances', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'integration_type',
                'profile_name',
                'profile_picture_url',
                'last_connected_at',
                'settings',
                'is_active'
            ]);
        });
    }
};
