<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RawMaterial;
use App\Models\StockMutation;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

it('allows manager and dev to access purchases page', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    get(route('admin.purchases'))
        ->assertOk()
        ->assertSee('Pengadaan Bahan Baku')
        ->assertSee('Pesanan Pembelian (PO)')
        ->assertSee('Direktori Supplier');

    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    get(route('admin.purchases'))
        ->assertOk();
});

it('restricts unauthorized users from accessing purchases page', function () {
    $guestRole = Role::firstOrCreate(['name' => 'regular_guest', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($guestRole);
    actingAs($user);

    get(route('admin.purchases'))
        ->assertForbidden();
});

it('redirects unauthenticated users to login', function () {
    get(route('admin.purchases'))
        ->assertRedirect(route('login'));
});

it('seeds initial suppliers and purchase orders properly', function () {
    expect(Supplier::count())->toBeGreaterThanOrEqual(4);
    expect(PurchaseOrder::count())->toBeGreaterThanOrEqual(3);

    $received = PurchaseOrder::where('status', 'received')->first();
    expect($received)->not->toBeNull()
        ->and($received->po_number)->toBe('PO-20261001-0001')
        ->and($received->items->count())->toBeGreaterThan(0);
});

it('can create a new purchase order with multiple items via Livewire savePurchaseOrder method', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $supplier = Supplier::first();
    $susu = RawMaterial::where('code', 'BB-SSU-01')->first();
    $wijen = RawMaterial::where('code', 'BB-WJN-01')->first();

    $component = Livewire::test('admin.purchases');

    $result = $component->instance()->savePurchaseOrder(
        null,
        $supplier->id,
        '2026-10-02',
        '2026-10-16',
        'ordered',
        'Pengadaan bahan baku batch awal bulan',
        [
            [
                'raw_material_id' => $susu->id,
                'qty_ordered' => 15,
                'unit_price' => 95000,
                'notes' => 'Susu bubuk murni',
            ],
            [
                'raw_material_id' => $wijen->id,
                'qty_ordered' => 20,
                'unit_price' => 65000,
                'notes' => 'Wijen grade A',
            ],
        ]
    );

    expect($result['success'])->toBeTrue();

    $newPo = PurchaseOrder::where('notes', 'Pengadaan bahan baku batch awal bulan')->first();
    expect($newPo)->not->toBeNull()
        ->and($newPo->status)->toBe('ordered')
        ->and((float) $newPo->total_amount)->toBe((15 * 95000.0) + (20 * 65000.0))
        ->and($newPo->items->count())->toBe(2);
});

it('validates minimum items when saving purchase order', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    $supplier = Supplier::first();
    actingAs($admin);

    $component = Livewire::test('admin.purchases');

    $result = $component->instance()->savePurchaseOrder(null, $supplier->id, '2026-10-02', null, 'draft', 'No items', []);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('minimal 1 item');
});

it('can edit an existing draft purchase order', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $po = PurchaseOrder::where('status', 'draft')->first();
    expect($po)->not->toBeNull();

    $supplier = Supplier::first();
    $material = RawMaterial::first();

    $component = Livewire::test('admin.purchases');

    $result = $component->instance()->savePurchaseOrder(
        $po->id,
        $supplier->id,
        '2026-10-05',
        '2026-10-20',
        'ordered',
        'PO diperbarui tanggalnya',
        [
            [
                'raw_material_id' => $material->id,
                'qty_ordered' => 50,
                'unit_price' => 12000,
                'notes' => 'Revisi kuantitas',
            ],
        ]
    );

    expect($result['success'])->toBeTrue();

    $po->refresh();
    expect($po->supplier_id)->toBe($supplier->id)
        ->and($po->status)->toBe('ordered')
        ->and((float) $po->total_amount)->toBe(50 * 12000.0)
        ->and($po->items->count())->toBe(1);
});

it('receiving purchase order updates raw material stock, average cost, and logs stock mutation', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $supplier = Supplier::first();
    $material = RawMaterial::create([
        'code' => 'BB-TEST-01',
        'name' => 'Bahan Baku Uji Coba',
        'category' => 'Bahan Pokok',
        'unit' => 'kg',
        'stock_qty' => 10,
        'min_stock_alert' => 2,
        'average_cost' => 10000,
        'is_active' => true,
    ]);

    $po = PurchaseOrder::create([
        'po_number' => 'PO-TEST-9999',
        'supplier_id' => $supplier->id,
        'order_date' => '2026-10-01',
        'status' => 'ordered',
        'payment_status' => 'unpaid',
        'total_amount' => 10 * 20000,
        'created_by' => $admin->id,
    ]);

    $poItem = $po->items()->create([
        'raw_material_id' => $material->id,
        'qty_ordered' => 10,
        'qty_received' => 0,
        'unit_price' => 20000,
        'subtotal' => 10 * 20000,
    ]);

    $component = Livewire::test('admin.purchases');

    // Receive 10 kg at Rp 20.000 / kg
    // Old: 10 kg @ 10.000 = 100.000
    // In: 10 kg @ 20.000 = 200.000
    // Total: 20 kg, Total Cost: 300.000 -> New Avg Cost: 15.000
    $result = $component->instance()->receivePurchaseOrder($po->id, [
        [
            'item_id' => $poItem->id,
            'qty_received' => 10,
        ],
    ]);

    expect($result['success'])->toBeTrue();

    $po->refresh();
    $material->refresh();
    $poItem->refresh();

    expect($po->status)->toBe('received')
        ->and($po->received_at)->not->toBeNull()
        ->and($po->receiver_user_id)->toBe($admin->id)
        ->and((float) $poItem->qty_received)->toBe(10.0);

    // Verify stock and moving average cost
    expect((float) $material->stock_qty)->toBe(20.0)
        ->and((float) $material->average_cost)->toBe(15000.0);

    // Verify Stock Mutation logged
    $mutation = StockMutation::where('reference_type', 'purchase')
        ->where('reference_id', $po->id)
        ->where('raw_material_id', $material->id)
        ->first();

    expect($mutation)->not->toBeNull()
        ->and($mutation->type)->toBe('in')
        ->and((float) $mutation->quantity)->toBe(10.0)
        ->and((float) $mutation->current_stock)->toBe(20.0)
        ->and((float) $mutation->cost_per_unit)->toBe(20000.0);
});

it('cannot cancel or delete received purchase orders', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $receivedPo = PurchaseOrder::where('status', 'received')->first();
    expect($receivedPo)->not->toBeNull();

    $component = Livewire::test('admin.purchases');

    $cancelResult = $component->instance()->cancelPurchaseOrder($receivedPo->id);
    expect($cancelResult['success'])->toBeFalse();

    $deleteResult = $component->instance()->deletePurchaseOrder($receivedPo->id);
    expect($deleteResult['success'])->toBeFalse();
});

it('can cancel and delete draft purchase order', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $supplier = Supplier::first();
    $material = RawMaterial::first();

    $po = PurchaseOrder::create([
        'po_number' => 'PO-DRAFT-DEL',
        'supplier_id' => $supplier->id,
        'order_date' => '2026-10-01',
        'status' => 'draft',
        'payment_status' => 'unpaid',
        'total_amount' => 50000,
        'created_by' => $admin->id,
    ]);
    $po->items()->create([
        'raw_material_id' => $material->id,
        'qty_ordered' => 5,
        'qty_received' => 0,
        'unit_price' => 10000,
        'subtotal' => 50000,
    ]);

    $component = Livewire::test('admin.purchases');

    $cancelRes = $component->instance()->cancelPurchaseOrder($po->id);
    expect($cancelRes['success'])->toBeTrue();

    $po->refresh();
    expect($po->status)->toBe('cancelled');

    $deleteRes = $component->instance()->deletePurchaseOrder($po->id);
    expect($deleteRes['success'])->toBeTrue();

    expect(PurchaseOrder::find($po->id))->toBeNull();
});

it('can create, update, and delete supplier', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $component = Livewire::test('admin.purchases');

    // 1. Create Supplier
    $createRes = $component->instance()->saveSupplier(
        null,
        'SPL-TEST-88',
        'PT Sumber Pangan Sejahtera',
        'Ibu Ratna',
        '081299887711',
        '081299887711',
        'ratna@sumberpangan.id',
        'Jl. Raya Industri No. 5',
        'Bekasi',
        14,
        'Supplier tepung tapioka',
        true
    );

    expect($createRes['success'])->toBeTrue();

    $supplier = Supplier::where('code', 'SPL-TEST-88')->first();
    expect($supplier)->not->toBeNull()
        ->and($supplier->name)->toBe('PT Sumber Pangan Sejahtera')
        ->and($supplier->payment_terms_days)->toBe(14)
        ->and($supplier->phone)->toBe('6281299887711');

    // 2. Update Supplier
    $updateRes = $component->instance()->saveSupplier(
        $supplier->id,
        'SPL-TEST-88',
        'PT Sumber Pangan Sejahtera Utama',
        'Ibu Ratna S.',
        '081299887711',
        '081299887711',
        'ratna@sumberpangan.id',
        'Jl. Raya Industri No. 5',
        'Bekasi',
        30,
        'Revisi tempo 30 hari',
        true
    );

    expect($updateRes['success'])->toBeTrue();
    $supplier->refresh();
    expect($supplier->name)->toBe('PT Sumber Pangan Sejahtera Utama')
        ->and($supplier->payment_terms_days)->toBe(30);

    // 3. Delete Supplier without POs
    $delRes = $component->instance()->deleteSupplier($supplier->id);
    expect($delRes['success'])->toBeTrue();
    expect(Supplier::find($supplier->id))->toBeNull();
});

it('cannot delete supplier that has purchase orders', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $supplierWithPo = Supplier::has('purchaseOrders')->first();
    expect($supplierWithPo)->not->toBeNull();

    $component = Livewire::test('admin.purchases');
    $delRes = $component->instance()->deleteSupplier($supplierWithPo->id);

    expect($delRes['success'])->toBeFalse()
        ->and($delRes['message'])->toContain('tidak dapat dihapus karena memiliki riwayat');

    expect(Supplier::find($supplierWithPo->id))->not->toBeNull();
});
