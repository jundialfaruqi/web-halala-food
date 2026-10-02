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
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique(); // e.g. PRD-20261003-0001
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // PIC / operator dapur
            $table->integer('planned_qty'); // Target jumlah produksi (kemasan/unit)
            $table->integer('actual_qty_good')->default(0); // Lolos QC / siap jual (menambah stock_ready)
            $table->integer('actual_qty_bad')->default(0); // Produk gagal / reject / pecah
            $table->decimal('total_material_cost', 15, 2)->default(0.00); // Total biaya bahan baku yang dialokasikan
            $table->decimal('unit_cost_produced', 15, 2)->default(0.00); // HPP riil per unit hasil jadi
            $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
