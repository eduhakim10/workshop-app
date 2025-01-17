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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete(); // Relate to customers
            $table->string('type');
            $table->string('brand');
            $table->string('model');
            $table->string('license_plate')->unique();
            $table->string('color')->nullable();
            $table->string('engine_type')->nullable();
            $table->string('chassis_number')->nullable();
            $table->date('next_service_due_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
