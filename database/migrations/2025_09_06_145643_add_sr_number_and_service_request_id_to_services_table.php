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
        Schema::table('services', function (Blueprint $table) {
            // Tambah kolom sr_number
            $table->string('sr_number')->nullable()->after('id');

            // Tambah kolom service_request_id
            $table->unsignedBigInteger('service_request_id')->nullable()->after('sr_number');

            // Foreign key ke service_requests
            $table->foreign('service_request_id')
                  ->references('id')
                  ->on('service_requests')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['service_request_id']);
            $table->dropColumn(['sr_number', 'service_request_id']);
        });
    }
};
