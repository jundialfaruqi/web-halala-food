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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('recipient_role', 100)->nullable()->after('recipient_name');
            $table->string('proof_image')->nullable()->after('recipient_phone');
            $table->longText('signature_data')->nullable()->after('proof_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['recipient_role', 'proof_image', 'signature_data']);
        });
    }
};
