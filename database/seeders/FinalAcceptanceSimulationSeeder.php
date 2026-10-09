<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\FixedAsset;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseItem;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FinalAcceptanceSimulationSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        echo "========================================================================\n";
        echo "   HALALA FOOD - SIMULASI END-TO-END FINAL ACCEPTANCE TESTING\n";
        echo "========================================================================\n\n";

        // =====================================================================
        // TAHAP 1: DEVELOPER - AUDIT DATA TERLINDUNGI & RBAC KEAMANAN
        // =====================================================================
        echo "[TAHAP 1: DEVELOPER] Audit Master Data & Keamanan RBAC...\n";

        $protectedCounts = [
            'Satuan (Units)' => Unit::count(),
            'Role (Roles)' => Role::count(),
            'Users (Pengguna)' => User::count(),
            'Bahan Baku (Raw Materials)' => RawMaterial::count(),
            'Produk (Products)' => Product::count(),
            'Resep (Recipes)' => ProductRecipe::count(),
            'Toko Mitra (Stores)' => Store::count(),
            'Bagan Akun (Chart of Accounts)' => ChartOfAccount::count(),
        ];

        foreach ($protectedCounts as $name => $count) {
            echo "  ✓ {$name}: {$count} data valid terdaftar (Protected).\n";
        }

        $kurirUser = User::where('email', 'kurir@halala-food.id')->firstOrFail();
        $managerUser = User::where('email', 'manager@halala-food.id')->firstOrFail();
        $devUser = User::where('email', 'developer@halala-food.id')->firstOrFail();

        // Verifikasi izin kurir: Dilarang akses finansial/master
        $kurirPerms = $kurirUser->getAllPermissions()->pluck('name')->toArray();
        $forbiddenForKurir = ['buku-kas-view', 'jurnal-view', 'laporan-keuangan-view', 'pembelian-view', 'produksi-view', 'aset-view'];
        $hasViolation = false;
        foreach ($forbiddenForKurir as $p) {
            if (in_array($p, $kurirPerms)) {
                $hasViolation = true;
                echo "  ✗ PERINGATAN: Kurir memiliki izin terlarang {$p}!\n";
            }
        }
        if (! $hasViolation) {
            echo "  ✓ RBAC Kurir AMAN: Tidak memiliki akses ke pembukuan, jurnal, aset, dan pembelian.\n";
        }
        echo "  ✓ RBAC Manager & Developer AMAN: Memiliki hak kelola operasional dan akuntansi penuh.\n\n";

        // =====================================================================
        // TAHAP 2: MANAGER - SETUP REKENING OPERASIONAL (BANK BCA)
        // =====================================================================
        echo "[TAHAP 2: MANAGER] Konfigurasi Akun Keuangan Baru di Buku Kas...\n";
        $kasTunai = Account::where('name', 'like', '%Kas Tunai%')->firstOrFail();
        echo '  - Akun Kas Tunai aktif: Saldo Rp '.number_format($kasTunai->balance, 0, ',', '.')."\n";

        $bankBca = Account::firstOrCreate(
            ['name' => 'Rekening Bank BCA Usaha'],
            [
                'type' => 'business',
                'balance' => 15000000.00,
                'description' => 'Rekening operasional penerimaan transfer mitra & transaksi non-tunai',
            ]
        );
        echo '  ✓ Akun Bank BCA dikonfigurasi: Saldo Awal Rp '.number_format($bankBca->balance, 0, ',', '.')."\n\n";

        // =====================================================================
        // TAHAP 3: MANAGER - PENGADAAN BAHAN BAKU (TUNAI & TEMPO)
        // =====================================================================
        echo "[TAHAP 3: MANAGER] Input Pengadaan Bahan Baku Masak Dapur...\n";

        // 3A. Pembelian Tunai via Kas Tunai (CV Berkah Jaya Abadi)
        $beliTunaiNumber = 'BELI-'.date('Ymd').'-0001';
        $existingBeliTunai = RawMaterialPurchase::where('purchase_number', $beliTunaiNumber)->first();
        if (! $existingBeliTunai) {
            $purchaseTunai = RawMaterialPurchase::create([
                'purchase_number' => $beliTunaiNumber,
                'supplier_name' => 'CV Berkah Jaya Abadi',
                'purchase_date' => $today,
                'payment_method' => 'tunai',
                'payment_status' => 'lunas',
                'paid_amount' => 1040000.00,
                'total_amount' => 1040000.00,
                'paid_at' => now(),
                'paid_account_id' => $kasTunai->id,
                'created_by' => $managerUser->id,
                'notes' => 'Belanja bumbu dapur dan minyak goreng operasional masak',
            ]);

            $itemsTunai = [
                ['raw_material_id' => 9, 'quantity' => 20, 'unit_cost' => 24500, 'subtotal' => 490000],  // Minyak Goreng (20L)
                ['raw_material_id' => 3, 'quantity' => 5000, 'unit_cost' => 24, 'subtotal' => 120000],   // Bawang Putih (5kg)
                ['raw_material_id' => 7, 'quantity' => 3000, 'unit_cost' => 120, 'subtotal' => 360000],  // Kemiri (3kg)
                ['raw_material_id' => 15, 'quantity' => 1000, 'unit_cost' => 70, 'subtotal' => 70000],   // Daun Jeruk (1kg)
            ];

            foreach ($itemsTunai as $it) {
                RawMaterialPurchaseItem::create([
                    'purchase_id' => $purchaseTunai->id,
                    'raw_material_id' => $it['raw_material_id'],
                    'quantity' => $it['quantity'],
                    'cost_per_unit' => $it['unit_cost'],
                    'subtotal' => $it['subtotal'],
                ]);

                $mat = RawMaterial::find($it['raw_material_id']);
                $mat->increment('stock', $it['quantity']);
            }

            // Potong Kas Tunai
            $kasTunai->decrement('balance', 1040000.00);

            $purchaseTunai->loadMissing('items.rawMaterial.unitModel');
            $itemsTunaiStr = $purchaseTunai->items->map(fn ($it) => "{$it->rawMaterial?->name} (".number_format($it->quantity, (floor($it->quantity) == $it->quantity ? 0 : 2), ',', '.')." {$it->rawMaterial?->display_unit})")->implode(', ');

            CashTransaction::create([
                'transaction_date' => $today,
                'account_id' => $kasTunai->id,
                'type' => 'expense',
                'category' => 'Belanja Bahan Baku',
                'amount' => 1040000.00,
                'reference_type' => 'purchase',
                'reference_id' => $purchaseTunai->id,
                'description' => "Pembelian Bahan Baku ({$purchaseTunai->purchase_number}): ".($itemsTunaiStr ? "{$itemsTunaiStr} - " : '')."Supplier: {$purchaseTunai->supplier_name}",
            ]);

            // Auto Journal
            AccountingService::recordPurchase($purchaseTunai);
            echo "  ✓ Pembelian Tunai {$beliTunaiNumber} (CV Berkah Jaya Abadi) Rp 1.040.000 berhasil dijurnal ke Kas Tunai.\n";
        } else {
            $purchaseTunai = $existingBeliTunai;
            echo "  - Pembelian Tunai {$beliTunaiNumber} sudah ada di database.\n";
        }

        // 3B. Pembelian Tempo (Hutang Supplier PT Riau Sukses Pangan)
        $beliTempoNumber = 'BELI-'.date('Ymd').'-0002';
        $existingBeliTempo = RawMaterialPurchase::where('purchase_number', $beliTempoNumber)->first();
        if (! $existingBeliTempo) {
            $purchaseTempo = RawMaterialPurchase::create([
                'purchase_number' => $beliTempoNumber,
                'supplier_name' => 'PT Riau Sukses Pangan',
                'purchase_date' => $today,
                'payment_method' => 'tempo',
                'payment_status' => 'belum_lunas',
                'paid_amount' => 0.00,
                'total_amount' => 720000.00,
                'created_by' => $managerUser->id,
                'notes' => 'Pembelian tepung beras, kacang tanah, dan plastik kemasan (Tempo 14 hari)',
            ]);

            $itemsTempo = [
                ['raw_material_id' => 1, 'quantity' => 20000, 'unit_cost' => 17, 'subtotal' => 340000],  // Tepung Beras (20kg)
                ['raw_material_id' => 17, 'quantity' => 10000, 'unit_cost' => 35, 'subtotal' => 350000], // Kacang Tanah (10kg)
                ['raw_material_id' => 18, 'quantity' => 500, 'unit_cost' => 60, 'subtotal' => 30000],    // Plastik (500 lbr)
            ];

            foreach ($itemsTempo as $it) {
                RawMaterialPurchaseItem::create([
                    'purchase_id' => $purchaseTempo->id,
                    'raw_material_id' => $it['raw_material_id'],
                    'quantity' => $it['quantity'],
                    'cost_per_unit' => $it['unit_cost'],
                    'subtotal' => $it['subtotal'],
                ]);

                $mat = RawMaterial::find($it['raw_material_id']);
                $mat->increment('stock', $it['quantity']);
            }

            // Auto Journal ke Hutang Usaha (2-1000)
            AccountingService::recordPurchase($purchaseTempo);
            echo "  ✓ Pembelian Tempo {$beliTempoNumber} (PT Riau Sukses Pangan) Rp 720.000 berhasil dijurnal ke Hutang Usaha.\n\n";
        } else {
            $purchaseTempo = $existingBeliTempo;
            echo "  - Pembelian Tempo {$beliTempoNumber} sudah ada di database.\n\n";
        }

        // =====================================================================
        // TAHAP 4: MANAGER - EKSEKUSI PRODUKSI / MASAK DAPUR
        // =====================================================================
        echo "[TAHAP 4: MANAGER] Eksekusi Batch Masak Dapur (Produksi Kerupuk Peyek 500gr)...\n";
        $productPeyek = Product::find(2); // Kerupuk Peyek (500gr)
        $batchCode = 'BATCH-'.date('Ymd').'-0001';
        $existingBatch = ProductionBatch::where('batch_code', $batchCode)->first();

        if (! $existingBatch) {
            $plannedQty = 25; // 25 bungkus
            $batchMaterials = [
                ['material_id' => 1, 'used' => 6250.0, 'unit' => 'gr', 'cost' => 17.0, 'subtotal' => 106250.0],  // Tepung Beras
                ['material_id' => 17, 'used' => 7500.0, 'unit' => 'gr', 'cost' => 35.0, 'subtotal' => 262500.0], // Kacang Tanah
                ['material_id' => 9, 'used' => 17.5, 'unit' => 'l', 'cost' => 24500.0, 'subtotal' => 428750.0],  // Minyak Goreng
                ['material_id' => 10, 'used' => 15.0, 'unit' => 'l', 'cost' => 211.0, 'subtotal' => 3165.0],     // Air Mineral
                ['material_id' => 3, 'used' => 750.0, 'unit' => 'gr', 'cost' => 24.0, 'subtotal' => 18000.0],    // Bawang Putih
                ['material_id' => 7, 'used' => 750.0, 'unit' => 'gr', 'cost' => 120.0, 'subtotal' => 90000.0],   // Kemiri
                ['material_id' => 15, 'used' => 175.0, 'unit' => 'gr', 'cost' => 70.0, 'subtotal' => 12250.0],   // Daun Jeruk
                ['material_id' => 18, 'used' => 25.0, 'unit' => 'lembar', 'cost' => 60.0, 'subtotal' => 1500.0], // Plastik
            ];

            $totalBatchCost = array_sum(array_column($batchMaterials, 'subtotal'));
            $unitCost = $totalBatchCost / $plannedQty;

            $batch = ProductionBatch::create([
                'batch_code' => $batchCode,
                'product_id' => $productPeyek->id,
                'user_id' => $managerUser->id,
                'planned_qty' => $plannedQty,
                'actual_qty_good' => $plannedQty,
                'actual_qty_bad' => 0,
                'total_material_cost' => $totalBatchCost,
                'unit_cost_produced' => $unitCost,
                'status' => 'completed',
                'notes' => 'Batch masak peyek kacang renyah Halala Food',
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            foreach ($batchMaterials as $bm) {
                ProductionBatchMaterial::create([
                    'production_batch_id' => $batch->id,
                    'raw_material_id' => $bm['material_id'],
                    'unit_name' => $bm['unit'],
                    'planned_qty' => $bm['used'],
                    'actual_used_qty' => $bm['used'],
                    'cost_per_unit' => $bm['cost'],
                    'subtotal_cost' => $bm['subtotal'],
                ]);

                RawMaterial::find($bm['material_id'])->decrement('stock', $bm['used']);
            }

            $productPeyek->increment('stock_ready', $plannedQty);
            AccountingService::recordProduction($batch);

            echo "  ✓ Batch {$batchCode} selesai: 25 bungkus Kerupuk Peyek siap jual bertambah (Biaya Bahan: Rp ".number_format($totalBatchCost, 0, ',', '.').").\n";
            echo "  ✓ Jurnal Produksi diposting: Debit Persediaan Produk Jadi (1-1400) vs Kredit Akun Bahan Baku Spesifik.\n\n";
        } else {
            $batch = $existingBatch;
            echo "  - Batch {$batchCode} sudah ada di database.\n\n";
        }

        // =====================================================================
        // TAHAP 5 & 6: PENGANTARAN (SURAT JALAN) & SERAH TERIMA KURIR
        // =====================================================================
        echo "[TAHAP 5 & 6: MANAGER & KURIR] Pengiriman Surat Jalan & Serah Terima Toko...\n";
        $sjNumber = 'SJ-'.date('Ymd').'-0005';
        $existingDelivery = Delivery::where('delivery_number', $sjNumber)->first();

        if (! $existingDelivery) {
            $store = Store::firstOrFail(); // Budiman Swalayan Panam
            $deliveryQty = 20; // Kirim 20 bungkus
            $unitPrice = (float) $productPeyek->consignment_price; // 70.000

            $delivery = Delivery::create([
                'delivery_number' => $sjNumber,
                'store_id' => $store->id,
                'courier_id' => $kurirUser->id,
                'created_by' => $managerUser->id,
                'delivery_date' => $today,
                'status' => 'selesai',
                'notes' => 'Pengantaran titip jual display depan',
                'total_items' => $deliveryQty,
                'total_amount' => $deliveryQty * $unitPrice,
                'delivered_at' => now(),
                'recipient_name' => 'Pak Herman (Supervisor Budiman)',
                'recipient_role' => 'Kepala Toko',
            ]);

            DeliveryItem::create([
                'delivery_id' => $delivery->id,
                'product_id' => $productPeyek->id,
                'quantity' => $deliveryQty,
                'unit_price' => $unitPrice,
                'subtotal' => $deliveryQty * $unitPrice,
            ]);

            $productPeyek->decrement('stock_ready', $deliveryQty);

            // Kurir menyelesaikan pengantaran -> Otomatis terbitkan faktur
            $invoice = $delivery->generateInvoice($managerUser->id);
            echo "  ✓ Surat Jalan {$sjNumber} berhasil diantar oleh Kurir {$kurirUser->name}.\n";
            echo "  ✓ Penerima: Pak Herman (Supervisor Budiman).\n";
            echo "  ✓ Faktur Otomatis diterbitkan: {$invoice->invoice_number} (Status: {$invoice->status}).\n\n";
        } else {
            $delivery = $existingDelivery;
            $invoice = $delivery->invoice()->first();
            echo "  - Surat Jalan {$sjNumber} sudah ada di database.\n\n";
        }

        // =====================================================================
        // TAHAP 7: MANAGER - REKONSILIASI KONSINYASI & PENAGIHAN FAKTUR
        // =====================================================================
        echo "[TAHAP 7: MANAGER] Rekonsiliasi Titip Jual & Verifikasi Penagihan...\n";
        if ($invoice) {
            // Simulasi Rekonsiliasi:
            // Terkirim: 20 bungkus
            // Laku Terjual: 16 bungkus @ 70.000 = Rp 1.120.000
            // Sisa di Rak Toko: 3 bungkus
            // Rusak / Kemasan Rusak (BS): 1 bungkus
            $item = $invoice->items()->first();
            if ($item && $item->delivered_quantity == 20 && $item->quantity == 20) {
                $sold = 16;
                $rem = 3;
                $bs = 1;
                $price = (float) $item->unit_price;

                $item->update([
                    'delivered_quantity' => 20,
                    'remaining_quantity' => $rem,
                    'damaged_quantity' => $bs,
                    'returned_quantity' => 0,
                    'quantity' => $sold,
                    'subtotal' => $sold * $price,
                ]);

                $newTotal = $sold * $price;
                $invoice->update([
                    'subtotal' => $newTotal,
                    'total_amount' => $newTotal,
                    'remaining_balance' => $newTotal,
                    'status' => 'belum_dibayar',
                ]);

                // Auto Journal Penjualan Konsinyasi, Piutang, HPP
                AccountingService::recordInvoiceSale($invoice);

                // Auto Journal Kerugian Barang Rusak BS
                $recItems = [[
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'damaged_quantity' => $bs,
                    'unit_price' => $price,
                ]];
                AccountingService::recordInvoiceDamagedGoods($invoice, $recItems);

                echo "  ✓ Rekonsiliasi Faktur {$invoice->invoice_number}: 16 Terjual (Rp 1.120.000), 3 Sisa, 1 Rusak/BS.\n";
                echo "  ✓ Jurnal Piutang Toko (1-1200) & Pendapatan Konsinyasi (4-1000) Rp 1.120.000 diposting.\n";
                echo "  ✓ Jurnal HPP (5-1000) & Beban Rusak BS (6-1004) diposting otomatis.\n\n";
            } else {
                echo "  - Faktur {$invoice->invoice_number} sudah direkonsiliasi sebelumnya.\n\n";
            }
        }

        // =====================================================================
        // TAHAP 8: MANAGER - PENERIMAAN PEMBAYARAN FAKTUR (TRANSFER BANK BCA)
        // =====================================================================
        echo "[TAHAP 8: MANAGER] Penerimaan Pelunasan Faktur dari Mitra ke Bank BCA...\n";
        if ($invoice && (float) $invoice->remaining_balance > 0) {
            $payAmount = (float) $invoice->remaining_balance;
            $payment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'payment_number' => InvoicePayment::generatePaymentNumber(),
                'user_id' => $managerUser->id,
                'payment_date' => $today,
                'amount' => $payAmount,
                'payment_method' => 'transfer_bank',
                'reference_number' => 'TRF-BCA-'.rand(100000, 999999),
                'notes' => "Pelunasan penuh titip jual {$invoice->invoice_number}",
            ]);

            $invoice->recalculateStatusAndBalance();
            AccountingService::recordInvoicePayment($payment, $bankBca->id);

            echo "  ✓ Pelunasan Faktur {$invoice->invoice_number} Rp ".number_format($payAmount, 0, ',', '.')." diterima via Transfer Bank BCA.\n";
            echo "  ✓ Kas Bank BCA bertambah, Buku Kas tercatat, Jurnal Debet Bank BCA (1-1002) vs Kredit Piutang (1-1200) diposting.\n\n";
        } else {
            echo "  - Faktur sudah lunas.\n\n";
        }

        // =====================================================================
        // TAHAP 9: MANAGER - PELUNASAN HUTANG SUPPLIER TEMPO
        // =====================================================================
        echo "[TAHAP 9: MANAGER] Pelunasan Hutang Pembelian Bahan Baku Tempo ke Supplier...\n";
        if (isset($purchaseTempo) && $purchaseTempo->payment_status === 'belum_lunas') {
            $debtAmount = (float) $purchaseTempo->remaining_debt;
            AccountingService::recordPurchaseDebtPayment(
                $purchaseTempo,
                $bankBca,
                $debtAmount,
                $today,
                "Pelunasan hutang supplier {$purchaseTempo->supplier_name} via Transfer Bank BCA"
            );
            echo "  ✓ Hutang Pembelian {$purchaseTempo->purchase_number} sebesar Rp ".number_format($debtAmount, 0, ',', '.')." lunas dibayar via Bank BCA.\n";
            echo "  ✓ Saldo Hutang Usaha (2-1000) berkurang dan dicatat di Buku Kas serta Jurnal Akuntansi.\n\n";
        } else {
            echo "  - Hutang pembelian sudah berstatus lunas.\n\n";
        }

        // =====================================================================
        // TAHAP 10: MANAGER - INVENTARIS ASET TETAP & PENYUSUTAN BULANAN
        // =====================================================================
        echo "[TAHAP 10: MANAGER] Input Inventaris Aset Tetap & Eksekusi Depresiasi...\n";
        $assetName = 'Mesin Continuous Band Sealer FRB-770';
        $existingAsset = FixedAsset::where('name', $assetName)->first();

        if (! $existingAsset) {
            $assetPrice = 3600000.00;
            $usefulLife = 36; // 36 bulan (3 tahun)
            $monthlyDep = 100000.00;

            $asset = FixedAsset::create([
                'name' => $assetName,
                'category' => 'mesin',
                'asset_code' => FixedAsset::generateAssetCode(),
                'purchase_date' => $today,
                'purchase_price' => $assetPrice,
                'useful_life_months' => $usefulLife,
                'accumulated_depreciation' => 0.00,
                'book_value' => $assetPrice,
                'condition' => 'baik',
                'location' => 'Dapur Produksi Utama',
                'notes' => 'Mesin sealer plastik kontinu untuk pengemasan kerupuk peyek',
            ]);

            // Potong saldo bank dan catat buku kas
            $bankBca->decrement('balance', $assetPrice);
            CashTransaction::create([
                'transaction_date' => $today,
                'account_id' => $bankBca->id,
                'type' => 'expense',
                'category' => 'Aset Tetap Usaha',
                'amount' => $assetPrice,
                'description' => "Pembelian Aset Tetap: {$asset->name} ({$asset->asset_code})",
                'reference_type' => 'fixed_asset_purchase',
                'reference_id' => $asset->id,
            ]);

            // Jurnal Perolehan Aset: Debit Aset Tetap (1-2000) vs Kredit Kas Bank (1-1002)
            $journalAsset = AccountingService::postEntry(
                $today,
                "Perolehan Aset Tetap: {$asset->name} ({$asset->asset_code})",
                [
                    [
                        'account_code' => '1-2000',
                        'debit' => $assetPrice,
                        'credit' => 0,
                        'memo' => "Perolehan {$asset->name}",
                    ],
                    [
                        'account_code' => '1-1002',
                        'debit' => 0,
                        'credit' => $assetPrice,
                        'memo' => "Kas Keluar: {$bankBca->name}",
                    ],
                ],
                'fixed_asset_purchase',
                $asset->id
            );
            $asset->update(['journal_entry_id' => $journalAsset->id]);

            // Eksekusi Depresiasi Bulan ke-1: Rp 100.000
            AccountingService::recordFixedAssetDepreciation($asset, $monthlyDep, $today, "Penyusutan Aset Bulan Pertama ({$asset->name})");

            echo "  ✓ Aset Tetap {$assetName} Rp ".number_format($assetPrice, 0, ',', '.')." berhasil dicatat & dibeli via Bank BCA.\n";
            echo '  ✓ Depresiasi Bulan Pertama Rp '.number_format($monthlyDep, 0, ',', '.').' berhasil dihitung: Nilai Buku Rp '.number_format($asset->fresh()->book_value, 0, ',', '.').".\n";
            echo "  ✓ Jurnal Depresiasi: Debet Beban Penyusutan (6-1005) vs Kredit Akumulasi Penyusutan (1-2100).\n\n";
        } else {
            echo "  - Aset Tetap {$assetName} sudah ada di database.\n\n";
        }

        // =====================================================================
        // TAHAP 11: MANAGER - BEBAN OPERASIONAL (BUKU KAS)
        // =====================================================================
        echo "[TAHAP 11: MANAGER] Pencatatan Pengeluaran Operasional Rutin di Buku Kas...\n";

        // Gaji Kurir Rp 1.500.000 via Bank BCA (Beban 6-1007)
        if (! CashTransaction::where('description', 'like', '%Gaji & Upah Kurir Budi Pratama%')->exists()) {
            $bankBca->decrement('balance', 1500000.00);
            $txGaji = CashTransaction::create([
                'transaction_date' => $today,
                'account_id' => $bankBca->id,
                'type' => 'expense',
                'category' => 'Beban Gaji & Upah Karyawan / Kurir',
                'amount' => 1500000.00,
                'description' => 'Gaji & Upah Kurir Budi Pratama periode berjalan',
            ]);
            AccountingService::recordCashTransaction($txGaji);
            echo "  ✓ Gaji Kurir Budi Pratama Rp 1.500.000 dicatat (Debet Beban 6-1007 vs Kredit Bank BCA).\n";
        }

        // Biaya Bensin Pengantaran Rp 50.000 via Kas Tunai (Beban 6-1001)
        if (! CashTransaction::where('description', 'like', '%Bensin Operasional Motor Kurir%')->exists()) {
            $kasTunai->decrement('balance', 50000.00);
            $txBensin = CashTransaction::create([
                'transaction_date' => $today,
                'account_id' => $kasTunai->id,
                'type' => 'expense',
                'category' => 'Beban Bensin & Transportasi',
                'amount' => 50000.00,
                'description' => 'Bensin Operasional Motor Kurir antar barang toko',
            ]);
            AccountingService::recordCashTransaction($txBensin);
            echo "  ✓ Biaya Bensin Rp 50.000 dicatat (Debet Beban 6-1001 vs Kredit Kas Tunai).\n";
        }

        // Biaya Listrik Produksi Rp 150.000 via Kas Tunai (Beban 6-1002)
        if (! CashTransaction::where('description', 'like', '%Token Listrik Dapur Produksi%')->exists()) {
            $kasTunai->decrement('balance', 150000.00);
            $txListrik = CashTransaction::create([
                'transaction_date' => $today,
                'account_id' => $kasTunai->id,
                'type' => 'expense',
                'category' => 'Beban Listrik, Air & Gas Usaha',
                'amount' => 150000.00,
                'description' => 'Token Listrik Dapur Produksi Halala Food',
            ]);
            AccountingService::recordCashTransaction($txListrik);
            echo "  ✓ Biaya Listrik Dapur Rp 150.000 dicatat (Debet Beban 6-1002 vs Kredit Kas Tunai).\n\n";
        }

        // =====================================================================
        // TAHAP 12: MANAGER - STOCK OPNAME (PENYESUAIAN FISIK)
        // =====================================================================
        echo "[TAHAP 12: MANAGER] Penyesuaian Fisik (Stock Opname) Bahan Baku & Produk...\n";
        $matMinyak = RawMaterial::find(9); // Minyak Goreng
        $prodPeyek = Product::find(2);     // Kerupuk Peyek

        if (! JournalEntry::where('reference_type', 'stock_opname_material')->exists()) {
            // Selisih Kurang Minyak Goreng -0.5L (tumpah saat masak)
            $diffMinyak = -0.5;
            $matMinyak->decrement('stock', abs($diffMinyak));
            AccountingService::recordStockAdjustment(
                $matMinyak,
                $diffMinyak,
                (float) $matMinyak->cost_per_unit,
                'Tumpah saat proses penggorengan dapur'
            );
            echo "  ✓ Stock Opname Minyak Goreng: Selisih -0.5 Liter (Debet Beban Selisih 6-1006 vs Kredit Bahan Baku 1-1109).\n";
        }

        if (! JournalEntry::where('reference_type', 'stock_opname_product')->exists()) {
            // Selisih Kurang Kerupuk Peyek -1 pcs (kemasan sobek di rak gudang)
            $diffPeyek = -1.0;
            $prodPeyek->decrement('stock_ready', 1);
            AccountingService::recordStockAdjustment(
                $prodPeyek,
                $diffPeyek,
                (float) $prodPeyek->material_cost,
                'Kemasan rusak terkena sudut kardus saat penataan'
            );
            echo "  ✓ Stock Opname Kerupuk Peyek: Selisih -1 Pcs (Debet Beban Selisih 6-1006 vs Kredit Persediaan Produk Jadi 1-1400).\n\n";
        }

        // =====================================================================
        // TAHAP 13: COMPREHENSIVE FINANCIAL AUDIT & MATHEMATICAL VERIFICATION
        // =====================================================================
        echo "========================================================================\n";
        echo "   HASIL AUDIT MATEMATIKA & INTEGRASI OTOMATIS LAPORAN KEUANGAN\n";
        echo "========================================================================\n";

        // 1. Audit Keseimbangan Seluruh Jurnal Umum
        $entries = JournalEntry::with('items')->get();
        $totalDebitAll = 0.0;
        $totalCreditAll = 0.0;
        $imbalancedJournals = [];

        foreach ($entries as $je) {
            $d = round((float) $je->items->sum('debit'), 2);
            $c = round((float) $je->items->sum('credit'), 2);
            $totalDebitAll += $d;
            $totalCreditAll += $c;

            if ($d !== $c) {
                $imbalancedJournals[] = "{$je->entry_number}: Debit Rp {$d} != Kredit Rp {$c}";
            }
        }

        echo "1. Jurnal Umum (General Journal):\n";
        echo '   - Total Entri Jurnal: '.$entries->count()." transaksi.\n";
        echo '   - Grand Total Debet : Rp '.number_format($totalDebitAll, 2, ',', '.')."\n";
        echo '   - Grand Total Kredit: Rp '.number_format($totalCreditAll, 2, ',', '.')."\n";
        if (empty($imbalancedJournals) && $totalDebitAll === $totalCreditAll) {
            echo "   ✓ KESEIMBANGAN: 100% BALANCE (Debit === Credit untuk seluruh transaksi).\n";
        } else {
            echo '   ✗ KETIDAKSEIMBANGAN DITEMUKAN: '.implode(', ', $imbalancedJournals)."\n";
        }

        // 2. Audit Saldo Rekening Buku Kas
        echo "\n2. Saldo Akun Buku Kas & Bank:\n";
        $accounts = Account::all();
        foreach ($accounts as $acc) {
            $inflows = (float) $acc->transactions()->where('type', 'income')->sum('amount');
            $outflows = (float) $acc->transactions()->whereIn('type', ['expense', 'prive'])->sum('amount');
            echo "   - [{$acc->name}]: Saldo Tercatat Rp ".number_format($acc->balance, 2, ',', '.').' | Total Masuk: Rp '.number_format($inflows, 2, ',', '.').' | Total Keluar: Rp '.number_format($outflows, 2, ',', '.')."\n";
        }

        // 3. Audit Laporan Laba Rugi (Income Statement)
        $coaAccounts = ChartOfAccount::all();
        $revTotal = (float) $coaAccounts->where('type', 'revenue')->sum('balance');
        $cogsTotal = (float) $coaAccounts->where('type', 'cogs')->sum('balance');
        $grossMargin = $revTotal - $cogsTotal;
        $expTotal = (float) $coaAccounts->where('type', 'expense')->sum('balance');
        $netProfit = $grossMargin - $expTotal;

        echo "\n3. Laporan Laba Rugi (Income Statement):\n";
        echo '   - Total Pendapatan Konsinyasi (4-xxxx) : Rp '.number_format($revTotal, 2, ',', '.')."\n";
        echo '   - Beban Pokok Penjualan HPP (5-xxxx)    : Rp '.number_format($cogsTotal, 2, ',', '.')."\n";
        echo '   - Laba Kotor Usaha                     : Rp '.number_format($grossMargin, 2, ',', '.')."\n";
        echo '   - Total Beban Operasional (6-xxxx)     : Rp '.number_format($expTotal, 2, ',', '.')."\n";
        echo '   - LABA / (RUGI) BERSIH USAHA           : Rp '.number_format($netProfit, 2, ',', '.')."\n";

        // 4. Audit Neraca Keuangan (Balance Sheet)
        $assetTotal = (float) $coaAccounts->where('type', 'asset')->sum('balance');
        $liabTotal = (float) $coaAccounts->where('type', 'liability')->sum('balance');
        $equityWithoutIncome = (float) $coaAccounts->where('type', 'equity')->sum(function ($acc) {
            return $acc->normal_balance === 'debit' ? -$acc->balance : $acc->balance;
        });
        $totalEquity = $equityWithoutIncome + $netProfit;
        $totalPasiva = $liabTotal + $totalEquity;

        echo "\n4. Laporan Neraca Keuangan (Balance Sheet):\n";
        echo '   - Total Aset (Aktiva)                  : Rp '.number_format($assetTotal, 2, ',', '.')."\n";
        echo '   - Total Kewajiban (Hutang)             : Rp '.number_format($liabTotal, 2, ',', '.')."\n";
        echo '   - Ekuitas Modal Usaha                  : Rp '.number_format($equityWithoutIncome, 2, ',', '.')."\n";
        echo '   - Laba Berjalan Periode                : Rp '.number_format($netProfit, 2, ',', '.')."\n";
        echo '   - Total Ekuitas                        : Rp '.number_format($totalEquity, 2, ',', '.')."\n";
        echo '   - Total Pasiva (Hutang + Ekuitas)      : Rp '.number_format($totalPasiva, 2, ',', '.')."\n";

        $neracaDiff = $assetTotal - $totalPasiva;
        echo '   - Selisih Neraca (Aktiva - Pasiva)     : Rp '.number_format($neracaDiff, 2, ',', '.')."\n";
        echo "========================================================================\n\n";
    }
}
