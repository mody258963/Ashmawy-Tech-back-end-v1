<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_devices', function (Blueprint $table): void {
            $table->string('location')->nullable()->after('name');
            $table->text('notes')->nullable()->after('location');
        });

        Schema::create('iot_sensor_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('label')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->timestamps();
            $table->unique(['iot_device_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_sensor_slots');
        Schema::table('iot_devices', function (Blueprint $table): void {
            $table->dropColumn(['location', 'notes']);
        });
    }
};
