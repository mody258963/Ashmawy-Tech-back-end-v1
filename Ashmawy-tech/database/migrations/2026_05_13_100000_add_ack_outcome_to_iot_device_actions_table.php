<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_device_actions', function (Blueprint $table) {
            $table->string('ack_outcome', 32)->nullable()->after('message_id');
            $table->json('ack_payload')->nullable()->after('ack_outcome');
        });
    }

    public function down(): void
    {
        Schema::table('iot_device_actions', function (Blueprint $table) {
            $table->dropColumn(['ack_outcome', 'ack_payload']);
        });
    }
};
