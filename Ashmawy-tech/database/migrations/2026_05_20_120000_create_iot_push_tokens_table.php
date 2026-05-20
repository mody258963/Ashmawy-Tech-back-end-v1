<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_user_id')->constrained('iot_users')->cascadeOnDelete();
            $table->string('token', 512);
            $table->string('platform', 16);
            $table->timestamps();

            $table->unique(['iot_user_id', 'token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_push_tokens');
    }
};
