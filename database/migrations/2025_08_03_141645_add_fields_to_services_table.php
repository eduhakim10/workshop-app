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
            $table->string('po_number')->nullable()->after('invoice_number');
            $table->string('spk_number')->nullable()->after('po_number');
            $table->integer('stage')->default(1)->after('status');
            $table->string('payment_terms')->nullable()->after('stage');
            $table->string('delivery_terms')->nullable()->after('payment_terms');
            $table->string('prepared_by')->nullable()->after('delivery_terms');
            $table->longText('items_offer')->nullable()->after('prepared_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'po_number',
                'spk_number',
                'stage',
                'payment_terms',
                'delivery_terms',
                'prepared_by',
                'items_offer',
            ]);
        });
    }
};
