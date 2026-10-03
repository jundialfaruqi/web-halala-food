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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('delivered_quantity')->nullable()->after('product_id');
            $table->integer('remaining_quantity')->default(0)->after('delivered_quantity');
            $table->integer('damaged_quantity')->default(0)->after('remaining_quantity');
            $table->integer('returned_quantity')->default(0)->after('damaged_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['delivered_quantity', 'remaining_quantity', 'damaged_quantity', 'returned_quantity']);
        });
    }
};
