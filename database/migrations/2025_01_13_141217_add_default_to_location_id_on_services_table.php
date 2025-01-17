<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->default(1)->change(); // Change 1 to the appropriate default location ID
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->change(); // Revert the change if necessary
        });
    }
};
