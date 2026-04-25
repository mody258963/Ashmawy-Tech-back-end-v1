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
        Schema::create('technician_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('users');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_profit', 10, 2)->default(0);
            $table->decimal('commission_earned', 10, 2)->default(0);
            $table->date('month');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_stats');
    }
};
