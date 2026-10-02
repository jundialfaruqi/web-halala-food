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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Kilogram, Gram, Liter, Mililiter, Pieces, Bungkus
            $table->string('short_name')->unique(); // e.g. kg, gram, liter, ml, pcs, bungkus, toples, lembar
            $table->string('description')->nullable(); // Keterangan opsional (misal: Satuan berat, kemasan, dll)
            $table->boolean('is_active')->default(true); // Status aktif / nonaktif
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
