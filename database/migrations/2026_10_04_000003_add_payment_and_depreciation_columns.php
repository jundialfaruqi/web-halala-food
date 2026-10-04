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
        Schema::table('raw_material_purchases', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('lunas')->after('payment_method');
            $table->decimal('paid_amount', 15, 2)->default(0.00)->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('paid_amount');
            $table->foreignId('paid_account_id')->nullable()->after('paid_at')->constrained('accounts')->nullOnDelete();
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->unsignedInteger('useful_life_months')->default(36)->after('purchase_price');
            $table->decimal('accumulated_depreciation', 15, 2)->default(0.00)->after('useful_life_months');
            $table->date('last_depreciation_date')->nullable()->after('accumulated_depreciation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_material_purchases', function (Blueprint $table) {
            $table->dropForeign(['paid_account_id']);
            $table->dropColumn(['payment_status', 'paid_amount', 'paid_at', 'paid_account_id']);
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropColumn(['useful_life_months', 'accumulated_depreciation', 'last_depreciation_date']);
        });
    }
};
