<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_service_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->string('badge_color', 20)->default('gray');
            $table->string('clickable_action', 30)->nullable(); // before_photos | after_photos
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('portal_service_statuses')->insert([
            [
                'code' => 'kendaraan_diterima',
                'name' => 'Kendaraan Diterima',
                'sort_order' => 1,
                'badge_color' => 'gray',
                'clickable_action' => 'before_photos',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'antrian',
                'name' => 'Antrian',
                'sort_order' => 2,
                'badge_color' => 'info',
                'clickable_action' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'dikerjakan',
                'name' => 'Dikerjakan',
                'sort_order' => 3,
                'badge_color' => 'warning',
                'clickable_action' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'finishgood',
                'name' => 'Finishgood',
                'sort_order' => 4,
                'badge_color' => 'success',
                'clickable_action' => 'after_photos',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('portal_service_status_id')
                ->nullable()
                ->after('status')
                ->constrained('portal_service_statuses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('portal_service_status_id');
        });

        Schema::dropIfExists('portal_service_statuses');
    }
};
