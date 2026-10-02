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
        Schema::create('stock_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->string('reference_type'); // 'production', 'purchase', 'waste', 'adjustment'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable(); // e.g. PRD-20261003-0001
            $table->enum('type', ['in', 'out']);
            $table->decimal('quantity', 12, 4);
            $table->decimal('stock_before', 12, 4);
            $table->decimal('stock_after', 12, 4);
            $table->decimal('cost_per_unit', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};
