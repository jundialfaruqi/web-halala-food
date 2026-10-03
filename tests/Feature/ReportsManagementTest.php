<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

test('unauthenticated users are redirected from reports page to login', function () {
    get(route('admin.reports'))
        ->assertRedirect(route('login'));
});

test('manager and dev have laporan-view permission while kurir does not', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    expect($devRole->hasPermissionTo('laporan-view'))->toBeTrue()
        ->and($managerRole->hasPermissionTo('laporan-view'))->toBeTrue()
        ->and($kurirRole->hasPermissionTo('laporan-view'))->toBeFalse();
});

test('manager can access reports page and view financial summary and tabs', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    get(route('admin.reports'))
        ->assertOk()
        ->assertSee('Laporan &amp; Rekapitulasi Bisnis', false)
        ->assertSee('Ringkasan Laba Rugi')
        ->assertSee('Rekap Piutang Toko Mitra')
        ->assertSee('Valuasi Stok &amp; Bahan', false);
});

test('reports component can switch tabs and period filters', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    Livewire::test('admin.reports.index')
        ->assertSee('Total Omset Tagihan')
        ->set('tab', 'stores')
        ->assertSee('Buku Rekapitulasi Mitra Toko')
        ->set('tab', 'stock_waste')
        ->assertSee('Stok Bahan Baku Dapur')
        ->assertSee('Stok Produk Jadi di Gudang')
        ->set('period', 'last_month')
        ->assertOk();
});

test('reports calculates hpp from completed production batches without database error', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $product = \App\Models\Product::first();

    \App\Models\ProductionBatch::create([
        'batch_code' => 'PRD-TEST-HPP',
        'product_id' => $product->id,
        'user_id' => $manager->id,
        'planned_qty' => 50,
        'actual_qty_good' => 50,
        'actual_qty_bad' => 0,
        'total_material_cost' => 125000.0,
        'unit_cost_produced' => 2500.0,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    actingAs($manager);

    Livewire::test('admin.reports.index')
        ->assertOk()
        ->assertSee('HPP Bahan Baku Terpakai')
        ->assertSee('289.125');
});
