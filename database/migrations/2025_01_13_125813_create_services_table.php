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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('offer_number');
            $table->decimal('amount_offer', 10, 2);
            $table->decimal('amount_offer_revision', 10, 2)->nullable();
            $table->date('handover_offer_date')->nullable();
            $table->string('work_order_number')->nullable();
            $table->date('work_order_date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_handover_date')->nullable();
            $table->foreignId('assign_to')->constrained('employees')->cascadeOnDelete();
            $table->date('service_start_date')->nullable();
            $table->date('service_due_date')->nullable();
            $table->time('service_start_time')->nullable();
            $table->time('service_due_time')->nullable();
            $table->enum('status', ['Scheduled', 'In Progress', 'Completed', 'Pending Parts', 'On Hold', 'Cancelled']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
