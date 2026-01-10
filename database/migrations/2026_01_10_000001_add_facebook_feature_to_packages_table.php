<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('feature_facebook')->default(false)->after('feature_priority_processing');
            $table->boolean('feature_facebook_auto_reply')->default(false)->after('feature_facebook');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['feature_facebook', 'feature_facebook_auto_reply']);
        });
    }
};
