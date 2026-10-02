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
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Wijen, Gula Pasir, Kacang Tanah, Susu Kental Manis, Plastik
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('unit')->default('gram'); // gram, ml, pcs, lembar
            $table->decimal('stock', 12, 2)->default(0.00); // jumlah stok saat ini
            $table->decimal('min_stock', 12, 2)->default(0.00); // batas peringatan stok menipis
            $table->decimal('cost_per_unit', 12, 2)->default(0.00); // harga beli terakhir per satuan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
