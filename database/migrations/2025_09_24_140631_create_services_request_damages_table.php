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
       Schema::create('services_request_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('services_requests')->onDelete('cascade');
            $table->foreignId('damage_id')->constrained('damages')->onDelete('cascade');
            $table->string('damage_name');
            $table->timestamps();
            $table->unique(['service_request_id', 'damage_id']); // biar gak dobel


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services_request_damages');
    }
};
