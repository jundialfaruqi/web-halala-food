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
        // 1. Rekening / Akun Kas (Kas Usaha vs Kas Pribadi)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // misal: Kas Tunai Usaha, Rekening BCA Usaha, Kas Pribadi Keluarga
            $table->string('type')->default('business'); // business, personal
            $table->decimal('balance', 14, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Transaksi Mutasi Kas (Buku Kas Harian)
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('type'); // income, expense, prive, personal_expense
            $table->string('category'); // Penjualan Toko, Beli Bahan, Bensin/Transport, Listrik Usaha, dll.
            $table->decimal('amount', 14, 2);
            $table->string('reference_type')->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. Bagan Akun (Chart of Accounts / COA)
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 1-1001, 1-1300, 4-1000, 5-1000, 6-1001
            $table->string('name'); // Kas Tunai, Persediaan Bahan, Pendapatan Penjualan, HPP, dll.
            $table->string('type'); // asset, liability, equity, revenue, cogs, expense
            $table->string('normal_balance'); // debit, credit
            $table->boolean('is_system')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. Header Jurnal Umum (Double-entry Journal Entries)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique(); // JU-YYYYMM-XXX
            $table->date('entry_date');
            $table->string('reference_type')->nullable()->index(); // cash_transaction, purchase, production, invoice, manual
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Baris Rincian Jurnal (Journal Items: Debit & Credit)
        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->decimal('debit', 14, 2)->default(0.00);
            $table->decimal('credit', 14, 2)->default(0.00);
            $table->string('memo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('accounts');
    }
};
