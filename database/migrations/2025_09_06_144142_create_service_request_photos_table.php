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
        Schema::create('service_request_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('services_requests')->cascadeOnDelete();
            $table->enum('type', ['before','after'])->default('before'); // bisa bedakan foto before / after
            $table->string('file_path'); // path ke file image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_photos');
    }
};
