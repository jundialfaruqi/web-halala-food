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
        Schema::create('production_batch_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->string('unit_name')->default('gram'); // snapshot unit
            $table->decimal('planned_qty', 12, 4); // kebutuhan sesuai formula BOM
            $table->decimal('actual_used_qty', 12, 4); // aktual bahan baku yang terpakai
            $table->decimal('cost_per_unit', 15, 2)->default(0.00); // harga beli per satuan saat produksi
            $table->decimal('subtotal_cost', 15, 2)->default(0.00); // total biaya bahan ini
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batch_materials');
    }
};
