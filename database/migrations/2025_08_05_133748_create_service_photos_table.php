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
        Schema::create('service_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained()->onDelete('cascade');
                $table->enum('type', ['before', 'after']);
                $table->string('image_path');
                $table->string('spk_number')->nullable(); // Tambahan SPK
                $table->string('damage')->nullable(); // Tambahan kerusakan
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_photos');
    }
};
