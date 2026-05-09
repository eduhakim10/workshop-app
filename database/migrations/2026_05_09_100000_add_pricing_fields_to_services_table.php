<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan field pricing untuk Quotation & Services:
     * - ppn_type: jenis PPN (tidak_ada / tidak_dipungut / inklusif / eksklusif)
     * - ppn_percent: persentase PPN (default 11%, dapat di-adjust user)
     * - subtotal_amount: subtotal sebelum diskon & PPN (DPP)
     * - discount_total: akumulasi diskon line-item
     * - total_price: total akhir (DPP - diskon + PPN sesuai jenis)
     * - damage_classification: klasifikasi kerusakan (Ringan / Sedang / Berat)
     *
     * Catatan: discount_percent per line item disimpan di kolom JSON
     * existing (items / items_offer), jadi tidak perlu migrasi schema baru.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'ppn_type')) {
                $table->string('ppn_type', 32)
                    ->default('tidak_ada')
                    ->after('quotation_status');
            }

            if (! Schema::hasColumn('services', 'ppn_percent')) {
                $table->decimal('ppn_percent', 6, 2)
                    ->default(11.00)
                    ->after('ppn_type');
            }

            if (! Schema::hasColumn('services', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 18, 2)
                    ->nullable()
                    ->after('ppn_percent');
            }

            if (! Schema::hasColumn('services', 'discount_total')) {
                $table->decimal('discount_total', 18, 2)
                    ->nullable()
                    ->after('subtotal_amount');
            }

            if (! Schema::hasColumn('services', 'total_price')) {
                $table->decimal('total_price', 18, 2)
                    ->nullable()
                    ->after('discount_total');
            }

            if (! Schema::hasColumn('services', 'damage_classification')) {
                $table->string('damage_classification', 16)
                    ->nullable()
                    ->after('total_price');
            }
        });

        // Pastikan kolom amount_offer & amount_offer_revision sanggup menampung
        // total dengan PPN (default decimal(10,2) hanya muat sampai 99.999.999,99).
        Schema::table('services', function (Blueprint $table) {
            try {
                $table->decimal('amount_offer', 18, 2)->nullable()->change();
                $table->decimal('amount_offer_revision', 18, 2)->nullable()->change();
            } catch (\Throwable $e) {
                // doctrine/dbal mungkin tidak tersedia pada beberapa environment.
                // Migration tetap dianggap sukses—ukuran lama (10,2) tetap dapat dipakai.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = [
                'ppn_type',
                'ppn_percent',
                'subtotal_amount',
                'discount_total',
                'total_price',
                'damage_classification',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
