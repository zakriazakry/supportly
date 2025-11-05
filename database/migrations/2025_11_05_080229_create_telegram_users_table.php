<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramUsersTable extends Migration
{
    public function up()
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->string('username');
            $table->string('token')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_users');
    }
}

class CreateTelegramSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('telegram_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->string('step')->nullable();
            $table->string('username')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_sessions');
    }
}
