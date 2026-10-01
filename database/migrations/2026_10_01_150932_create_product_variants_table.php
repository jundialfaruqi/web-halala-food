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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');
            $table->string('packaging_type')->default('pouch'); // pouch, jar, piece, box, other
            $table->unsignedInteger('pcs_per_package')->default(1);
            $table->decimal('weight_grams', 10, 2)->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->string('sku_code')->nullable()->unique();
            $table->decimal('base_cost', 15, 2)->default(0);
            $table->decimal('wholesale_price', 15, 2)->default(0);
            $table->decimal('retail_price', 15, 2)->default(0);
            $table->integer('stock_qty')->default(0);
            $table->integer('min_stock_alert')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
