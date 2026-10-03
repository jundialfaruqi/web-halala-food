<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with 3 core roles: dev, manager, and kurir.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Core Permission Groups & Permissions
        $groups = [
            'Dashboard' => [
                'description' => 'Akses ringkasan metrik performa toko dan operasional.',
                'permissions' => [
                    'dashboard-view' => 'Melihat dashboard utama dan ringkasan metrik performa.',
                ],
            ],
            'Pengguna & Hak Akses' => [
                'description' => 'Kelola akun pengguna, hierarki 3 role (dev, manager, kurir), dan konfigurasi izin akses.',
                'permissions' => [
                    'user-manage' => 'Kelola akun staf dan pengguna sistem.',
                    'role-manage' => 'Kelola data role jabatan dan wewenang.',
                    'permission-manage' => 'Kelola izin dan hak akses teknis aplikasi.',
                ],
            ],
            'Master Satuan' => [
                'description' => 'Kelola data master satuan pengukuran produk dan bahan baku.',
                'permissions' => [
                    'satuan-view' => 'Melihat daftar master satuan.',
                    'satuan-create' => 'Menambahkan data satuan baru.',
                    'satuan-edit' => 'Mengubah data master satuan.',
                    'satuan-delete' => 'Menghapus data master satuan.',
                ],
            ],
            'Master Produk Jadi' => [
                'description' => 'Kelola katalog produk jadi, harga setor konsinyasi, harga eceran toko, dan stok gudang.',
                'permissions' => [
                    'produk-view' => 'Melihat katalog produk jadi dan daftar harga.',
                    'produk-create' => 'Menambahkan produk jadi baru.',
                    'produk-edit' => 'Mengubah data produk jadi, harga setor, dan status.',
                    'produk-delete' => 'Menghapus data produk jadi.',
                ],
            ],
            'Bahan Baku & Resep' => [
                'description' => 'Kelola inventaris bahan baku, batas minimum stok, dan formula resep produk (BOM).',
                'permissions' => [
                    'bahan-baku-view' => 'Melihat daftar stok bahan baku dan formula resep produk.',
                    'bahan-baku-create' => 'Menambahkan data bahan baku baru.',
                    'bahan-baku-edit' => 'Mengubah data bahan baku dan stok.',
                    'bahan-baku-delete' => 'Menghapus data bahan baku.',
                    'resep-manage' => 'Mengatur formula resep produk (Bill of Material / BOM).',
                ],
            ],
            'Produksi & Manufaktur' => [
                'description' => 'Kelola eksekusi batch masak dapur, konversi bahan baku ke barang jadi, dan mutasi kartu stok.',
                'permissions' => [
                    'produksi-view' => 'Melihat daftar riwayat batch masak dan kartu stok.',
                    'produksi-create' => 'Membuat dan mengeksekusi batch produksi baru.',
                    'produksi-edit' => 'Mengubah catatan atau status batch produksi.',
                    'produksi-delete' => 'Membatalkan atau menghapus catatan batch produksi.',
                ],
            ],
            'Mitra Toko' => [
                'description' => 'Kelola direktori mitra toko, rute pengantaran kurir, dan komisi titip jual.',
                'permissions' => [
                    'toko-view' => 'Melihat daftar toko mitra dan rute pengantaran.',
                    'toko-create' => 'Menambahkan data toko mitra baru.',
                    'toko-edit' => 'Mengubah data toko mitra.',
                    'toko-delete' => 'Menghapus data toko mitra.',
                ],
            ],
            'Pengantaran & Distribusi' => [
                'description' => 'Kelola surat jalan pengantaran barang jadi ke toko mitra dan tracking kurir.',
                'permissions' => [
                    'pengantaran-view' => 'Melihat daftar surat jalan dan tugas pengantaran.',
                    'pengantaran-create' => 'Membuat surat jalan pengantaran baru.',
                    'pengantaran-edit' => 'Mengubah surat jalan atau memperbarui status pengantaran.',
                    'pengantaran-delete' => 'Membatalkan atau menghapus surat jalan.',
                ],
            ],
            'Faktur & Piutang Toko' => [
                'description' => 'Kelola faktur tagihan konsinyasi mitra toko, jatuh tempo piutang, dan pencatatan pembayaran pelunasan.',
                'permissions' => [
                    'faktur-view' => 'Melihat daftar faktur tagihan dan kartu piutang toko mitra.',
                    'faktur-create' => 'Membuat faktur tagihan konsinyasi baru.',
                    'faktur-edit' => 'Mengubah data faktur tagihan atau mencatat pembayaran pelunasan.',
                    'faktur-delete' => 'Membatalkan atau menghapus faktur tagihan.',
                ],
            ],
            'Pengaturan Usaha' => [
                'description' => 'Kelola profil usaha, kontak resmi, rekening bank pembayaran, dan format cetak dokumen.',
                'permissions' => [
                    'pengaturan-view' => 'Melihat profil usaha dan rekening pembayaran.',
                    'pengaturan-edit' => 'Mengubah profil usaha, kontak, dan rekening pembayaran.',
                ],
            ],
            'Pengadaan Bahan Baku' => [
                'description' => 'Kelola transaksi pembelian bahan baku dapur, nota supplier, dan pencatatan kas keluar.',
                'permissions' => [
                    'pembelian-view' => 'Melihat riwayat transaksi pembelian bahan baku.',
                    'pembelian-create' => 'Mencatat transaksi pembelian bahan baku baru.',
                    'pembelian-delete' => 'Membatalkan atau menghapus riwayat pembelian bahan baku.',
                ],
            ],
            'Buku Kas' => [
                'description' => 'Kelola mutasi buku kas harian, penerimaan kas, pengeluaran operasional, dan penarikan prive.',
                'permissions' => [
                    'buku-kas-view' => 'Melihat mutasi buku kas dan saldo rekening usaha.',
                    'buku-kas-create' => 'Mencatat transaksi kas masuk, kas keluar, dan penarikan prive.',
                    'buku-kas-delete' => 'Menghapus catatan transaksi buku kas.',
                ],
            ],
            'Akuntansi & Jurnal' => [
                'description' => 'Akses bagan akun perkiraan (COA), jurnal umum akuntansi, buku besar, dan laporan keuangan formal (laba rugi & neraca).',
                'permissions' => [
                    'jurnal-view' => 'Melihat jurnal umum akuntansi dan mutasi buku besar.',
                    'laporan-keuangan-view' => 'Melihat dan mencetak laporan laba rugi serta neraca keuangan formal.',
                ],
            ],
            'Laporan & Analisis' => [
                'description' => 'Akses laporan komprehensif laba rugi, omset penjualan, piutang mitra toko, dan analisa retur barang.',
                'permissions' => [
                    'laporan-view' => 'Melihat dan mencetak ringkasan laporan keuangan, laba rugi, dan rekapitulasi toko.',
                ],
            ],
            'Aset Tetap Usaha' => [
                'description' => 'Kelola inventaris aset tetap usaha: mesin produksi, peralatan dapur, kendaraan, dan inventaris lainnya.',
                'permissions' => [
                    'aset-view'   => 'Melihat daftar inventaris aset tetap usaha.',
                    'aset-create' => 'Mencatat aset tetap baru dan menjurnal ke buku besar.',
                    'aset-edit'   => 'Mengubah data dan kondisi aset tetap.',
                    'aset-delete' => 'Menghapus catatan aset tetap dari inventaris.',
                ],
            ],
        ];

        // Track valid active permission names
        $validPermissionNames = [];

        foreach ($groups as $groupName => $groupData) {
            $group = PermissionGroup::updateOrCreate(
                ['name' => $groupName],
                ['description' => $groupData['description']]
            );

            foreach ($groupData['permissions'] as $permissionName => $permissionDesc) {
                $validPermissionNames[] = $permissionName;

                Permission::updateOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['permission_group_id' => $group->id]
                );
            }
        }

        // Delete obsolete permissions that are no longer part of the system
        $obsoletePermissions = Permission::whereNotIn('name', $validPermissionNames)->get();
        foreach ($obsoletePermissions as $obsPerm) {
            $obsPerm->roles()->detach();
            $obsPerm->users()->detach();
            $obsPerm->delete();
        }

        // Delete empty obsolete permission groups
        $validGroupNames = array_keys($groups);
        PermissionGroup::whereNotIn('name', $validGroupNames)->each(function ($grp) {
            if ($grp->permissions()->count() === 0) {
                $grp->delete();
            }
        });

        // 2. Define exactly 3 Core Roles: dev, manager, and kurir
        $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $kurirRole = Role::firstOrCreate(['name' => 'kurir', 'guard_name' => 'web']);

        // Delete any obsolete roles not in ['dev', 'manager', 'kurir']
        Role::whereNotIn('name', ['dev', 'manager', 'kurir'])->get()->each(function ($role) {
            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();
        });

        // 3. Assign Permissions to Roles
        // Dev / Super Admin gets all permissions
        $devRole->syncPermissions(Permission::all());

        // Manager gets operational management + Bahan Baku, Resep, Produksi, Toko, Pengantaran & Faktur permissions
        $managerRole->syncPermissions([
            'dashboard-view',
            'user-manage',
            'role-manage',
            'permission-manage',
            'bahan-baku-view',
            'bahan-baku-create',
            'bahan-baku-edit',
            'bahan-baku-delete',
            'resep-manage',
            'produksi-view',
            'produksi-create',
            'produksi-edit',
            'produksi-delete',
            'toko-view',
            'toko-create',
            'toko-edit',
            'toko-delete',
            'pengantaran-view',
            'pengantaran-create',
            'pengantaran-edit',
            'pengantaran-delete',
            'faktur-view',
            'faktur-create',
            'faktur-edit',
            'faktur-delete',
            'pengaturan-view',
            'pengaturan-edit',
            'produk-view',
            'produk-create',
            'produk-edit',
            'produk-delete',
            'pembelian-view',
            'pembelian-create',
            'pembelian-delete',
            'buku-kas-view',
            'buku-kas-create',
            'buku-kas-delete',
            'jurnal-view',
            'laporan-keuangan-view',
            'laporan-view',
            'aset-view',
            'aset-create',
            'aset-edit',
            'aset-delete',
        ]);

        // Kurir gets dashboard access, toko view, pengantaran, and faktur view
        $kurirRole->syncPermissions([
            'dashboard-view',
            'toko-view',
            'pengantaran-view',
            'pengantaran-edit',
            'faktur-view',
        ]);

        // 4. Seed Standard Users for the 3 Roles
        $users = [
            [
                'email' => 'developer@halala-food.id',
                'name' => 'Developer Super Admin',
                'phone' => '081234567890',
                'role' => $devRole,
            ],
            [
                'email' => 'admin@halala-food.id',
                'name' => 'Administrator Halala',
                'phone' => '081288889999',
                'role' => $devRole,
            ],
            [
                'email' => 'manager@halala-food.id',
                'name' => 'Ibu Dewi Lestari (Manager)',
                'phone' => '081299887766',
                'role' => $managerRole,
            ],
            [
                'email' => 'kurir@halala-food.id',
                'name' => 'Budi Pratama (Kurir)',
                'phone' => '089612348765',
                'role' => $kurirRole,
            ],
        ];

        // Clean up users not in the new standard list
        $activeEmails = array_column($users, 'email');
        User::whereNotIn('email', $activeEmails)->delete();

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'phone' => User::normalizePhone($userData['phone']),
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$userData['role']]);
        }

        // 5. Seed Standard Master Units
        $defaultUnits = [
            ['name' => 'Kilogram', 'short_name' => 'kg', 'description' => 'Satuan berat standar (1.000 gram)', 'is_active' => true],
            ['name' => 'Gram', 'short_name' => 'g', 'description' => 'Satuan berat baku resep produksi', 'is_active' => true],
            ['name' => 'Liter', 'short_name' => 'l', 'description' => 'Satuan volume cairan', 'is_active' => true],
            ['name' => 'Mililiter', 'short_name' => 'ml', 'description' => 'Satuan volume cairan resep', 'is_active' => true],
            ['name' => 'Pieces', 'short_name' => 'pcs', 'description' => 'Satuan hitungan per kemasan atau butir', 'is_active' => true],
            ['name' => 'Bungkus', 'short_name' => 'bungkus', 'description' => 'Satuan kemasan kantong atau pouch', 'is_active' => true],
            ['name' => 'Toples', 'short_name' => 'toples', 'description' => 'Satuan wadah toples mika', 'is_active' => true],
            ['name' => 'Lembar', 'short_name' => 'lembar', 'description' => 'Satuan stiker, segel, dan label kemasan', 'is_active' => true],
        ];

        foreach ($defaultUnits as $unitData) {
            Unit::firstOrCreate(
                ['short_name' => $unitData['short_name']],
                $unitData
            );
        }
    }
}
