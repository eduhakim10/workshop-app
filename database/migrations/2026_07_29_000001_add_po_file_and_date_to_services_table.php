<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'po_date')) {
                $table->date('po_date')->nullable()->after('po_number');
            }
            if (! Schema::hasColumn('services', 'po_file')) {
                $table->string('po_file')->nullable()->after('po_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'po_file')) {
                $table->dropColumn('po_file');
            }
            if (Schema::hasColumn('services', 'po_date')) {
                $table->dropColumn('po_date');
            }
        });
    }
};
