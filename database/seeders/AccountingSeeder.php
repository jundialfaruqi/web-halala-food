<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // 1. ASET
            [
                'code' => '1-1001',
                'name' => 'Kas Tunai Usaha',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Uang tunai kas kecil dan hasil tagihan harian usaha',
            ],
            [
                'code' => '1-1002',
                'name' => 'Kas Bank Usaha',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Rekening bank operasional usaha (misal: BCA, Mandiri, BRI)',
            ],
            [
                'code' => '1-1100',
                'name' => 'Kas Pribadi / Keluarga',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Uang kas belanja dapur & rumah tangga pribadi',
            ],
            [
                'code' => '1-1200',
                'name' => 'Piutang Toko Konsinyasi',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Tagihan hasil penjualan yang belum disetor oleh toko mitra',
            ],
            [
                'code' => '1-1300',
                'name' => 'Persediaan Bahan Baku',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Nilai persediaan fisik bahan baku mentah di dapur/gudang',
            ],
            [
                'code' => '1-1400',
                'name' => 'Persediaan Produk Jadi',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Nilai persediaan makanan jadi siap antar/jual',
            ],

            // 2. KEWAJIBAN / HUTANG
            [
                'code' => '2-1000',
                'name' => 'Hutang Usaha',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Kewajiban pembayaran tempo ke supplier bahan baku atau operasional',
            ],

            // 3. EKUITAS / MODAL
            [
                'code' => '3-1000',
                'name' => 'Modal Usaha Pemilik',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Setoran modal awal usaha dari pemilik',
            ],
            [
                'code' => '3-2000',
                'name' => 'Prive Pemilik (Penarikan Pribadi)',
                'type' => 'equity',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Penarikan dana usaha untuk keperluan pribadi / rumah tangga keluarga',
            ],
            [
                'code' => '3-3000',
                'name' => 'Laba Ditahan / Akumulasi Laba',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Akumulasi laba bersih usaha periode lalu',
            ],

            // 4. PENDAPATAN
            [
                'code' => '4-1000',
                'name' => 'Pendapatan Penjualan Konsinyasi',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'is_system' => true,
                'description' => 'Hasil penjualan titip produk di toko mitra',
            ],
            [
                'code' => '4-2000',
                'name' => 'Pendapatan Lain-lain',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'is_system' => false,
                'description' => 'Pemasukan usaha di luar penjualan toko',
            ],

            // 5. BEBAN POKOK PENJUALAN (HPP)
            [
                'code' => '5-1000',
                'name' => 'Beban Pokok Penjualan (HPP)',
                'type' => 'cogs',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Biaya bahan baku produk yang laku terjual',
            ],

            // 6. BEBAN OPERASIONAL
            [
                'code' => '6-1001',
                'name' => 'Beban Bensin & Transportasi',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Biaya bensin dan transportasi antar titip barang',
            ],
            [
                'code' => '6-1002',
                'name' => 'Beban Listrik, Air & Gas Usaha',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Biaya utilitas dapur produksi makanan',
            ],
            [
                'code' => '6-1003',
                'name' => 'Beban Kemasan, Label & Plastik',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Biaya toples, stiker kemasan, kardus, dan plastik',
            ],
            [
                'code' => '6-1004',
                'name' => 'Beban Kerugian Barang Rusak / Kadaluarsa',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Penyusutan dan kerugian atas barang retur rusak/basi',
            ],
            [
                'code' => '6-1099',
                'name' => 'Beban Operasional Lainnya',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => 'Pengeluaran operasional lain-lain',
            ],
        ];

        foreach ($accounts as $data) {
            ChartOfAccount::updateOrCreate(['code' => $data['code']], $data);
        }

        \App\Services\AccountingService::syncAllRawMaterialAccounts();
    }
}
