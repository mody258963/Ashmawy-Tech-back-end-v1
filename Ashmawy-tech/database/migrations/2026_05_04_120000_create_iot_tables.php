<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_user_id')->constrained('iot_users')->cascadeOnDelete();
            $table->uuid('device_uuid')->unique();
            $table->string('name');
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->timestamp('last_seen')->nullable();
            $table->string('mqtt_username')->unique();
            $table->text('mqtt_jwt_token')->nullable();
            $table->timestamp('jwt_expires_at')->nullable();
            $table->string('secret_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('iot_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['switch', 'dimmer', 'motor', 'sensor', 'lock', 'valve', 'hvac', 'generic'])->default('generic');
            $table->unsignedTinyInteger('channel');
            $table->json('metadata')->nullable();
            $table->json('last_state')->nullable();
            $table->timestamp('last_state_at')->nullable();
            $table->timestamps();
            $table->unique(['iot_device_id', 'channel']);
        });

        Schema::create('iot_device_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->cascadeOnDelete();
            $table->foreignId('iot_component_id')->nullable()->constrained('iot_components')->nullOnDelete();
            $table->enum('action', ['ON', 'OFF', 'TOGGLE', 'SET']);
            $table->json('value')->nullable();
            $table->enum('triggered_by', ['user', 'system', 'automation'])->default('user');
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->string('message_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('iot_sensor_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->cascadeOnDelete();
            $table->string('type', 64);
            $table->json('value');
            $table->string('message_id')->nullable()->index();
            $table->timestamp('recorded_at')->useCurrent();
            $table->index(['iot_device_id', 'type', 'recorded_at']);
        });

        Schema::create('iot_device_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->cascadeOnDelete();
            $table->string('type', 64);
            $table->json('payload')->nullable();
            $table->string('message_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_device_events');
        Schema::dropIfExists('iot_sensor_data');
        Schema::dropIfExists('iot_device_actions');
        Schema::dropIfExists('iot_components');
        Schema::dropIfExists('iot_devices');
        Schema::dropIfExists('iot_users');
    }
};
