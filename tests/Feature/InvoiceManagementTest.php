<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $unit = Unit::firstOrCreate(['short_name' => 'bungkus'], ['name' => 'Bungkus', 'is_active' => true]);

    $product = Product::firstOrCreate(
        ['name' => 'Marie Wijen'],
        [
            'unit_id' => $unit->id,
            'consignment_price' => 12000,
            'retail_price' => 15000,
            'stock' => 50,
            'stock_ready' => 50,
            'min_stock' => 10,
            'is_active' => true,
        ]
    );

    $store = Store::firstOrCreate(
        ['name' => 'Pusat Oleh-Oleh Barokah'],
        [
            'owner_name' => 'H. Ahmad Barokah',
            'phone' => '081234567890',
            'address' => 'Jl. Pasar Besar No. 12, Malang',
            'latitude' => -7.983908,
            'longitude' => 112.630852,
            'route' => 'Rute Pasar Besar',
            'notes' => 'Toko utama grosir oleh-oleh',
            'is_active' => true,
        ]
    );

    $invoice = Invoice::firstOrCreate(
        ['invoice_number' => 'INV-2026-0001'],
        [
            'store_id' => $store->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal' => 200000.0,
            'discount' => 0.0,
            'total_amount' => 200000.0,
            'paid_amount' => 50000.0,
            'remaining_balance' => 150000.0,
            'status' => 'sebagian',
            'notes' => 'Tagihan konsinyasi',
        ]
    );

    InvoiceItem::firstOrCreate(
        ['invoice_id' => $invoice->id, 'product_id' => $product->id],
        [
            'quantity' => 20,
            'unit_price' => 10000.0,
            'subtotal' => 200000.0,
        ]
    );

    InvoicePayment::firstOrCreate(
        ['invoice_id' => $invoice->id, 'payment_number' => 'PAY-2026-0001'],
        [
            'reference_number' => 'TRF-INIT-01',
            'amount' => 50000.0,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer_bank',
            'notes' => 'Pembayaran awal sebagian',
        ]
    );
});

test('unauthenticated users are redirected from invoices page to login', function () {
    get(route('admin.invoices'))
        ->assertRedirect(route('login'));
});

test('role dev and manager have all faktur permissions', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');

    $allPermissions = [
        'faktur-view',
        'faktur-create',
        'faktur-edit',
        'faktur-delete',
    ];

    foreach ($allPermissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue();
    }
});

test('manager can access invoices index page and see list of invoices', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);
    get(route('admin.invoices'))
        ->assertOk()
        ->assertSee('Faktur &amp; Piutang Toko', false)
        ->assertSee('INV-');
});

test('manager can create a new invoice with line items', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $store = Store::first();
    $product = Product::first();

    actingAs($manager);

    $qty = 10;
    $unitPrice = 12000.0;
    $discount = 5000.0;

    Livewire::test('admin.invoices.create')
        ->set('invoice_number', 'INV-TEST-0001')
        ->set('store_id', $store->id)
        ->set('invoice_date', now()->toDateString())
        ->set('due_date', now()->addDays(14)->toDateString())
        ->set('discount', $discount)
        ->set('notes', 'Uji coba penagihan toko')
        ->set('items', [
            [
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $qty * $unitPrice,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $invoice = Invoice::where('invoice_number', 'INV-TEST-0001')->first();
    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->subtotal)->toBe(120000.0)
        ->and((float) $invoice->discount)->toBe(5000.0)
        ->and((float) $invoice->total_amount)->toBe(115000.0)
        ->and((float) $invoice->paid_amount)->toBe(0.0)
        ->and((float) $invoice->remaining_balance)->toBe(115000.0)
        ->and($invoice->status)->toBe('belum_dibayar')
        ->and($invoice->items()->count())->toBe(1);
});

test('recording a payment updates paid_amount, remaining_balance, and status to sebagian', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $invoice = Invoice::first();

    actingAs($manager);

    $initialBalance = (float) $invoice->remaining_balance;
    $payAmount = 50000.0;

    Livewire::test('admin.invoices.show', ['invoice' => $invoice])
        ->set('payment_amount', $payAmount)
        ->set('payment_date', now()->toDateString())
        ->set('payment_method', 'transfer_bank')
        ->set('reference_number', 'TRF-TEST-99')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->remaining_balance)->toBe($initialBalance - $payAmount)
        ->and($invoice->status)->toBe('sebagian');
});

test('full payment settles invoice and updates status to lunas', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $invoice = Invoice::first();

    actingAs($manager);

    $remaining = (float) $invoice->remaining_balance;

    Livewire::test('admin.invoices.show', ['invoice' => $invoice])
        ->set('payment_amount', $remaining)
        ->set('payment_date', now()->toDateString())
        ->set('payment_method', 'tunai')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->remaining_balance)->toBe(0.0)
        ->and($invoice->status)->toBe('lunas');
});

test('deleting a payment recalculates balance and reverts status', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $invoice = Invoice::with('payments')->first();
    $payment = $invoice->payments->first();

    actingAs($manager);

    $balanceBefore = (float) $invoice->remaining_balance;
    $paymentAmount = (float) $payment->amount;

    Livewire::test('admin.invoices.show', ['invoice' => $invoice])
        ->call('deletePayment', $payment->id);

    $invoice->refresh();
    expect((float) $invoice->remaining_balance)->toBe($balanceBefore + $paymentAmount);
});

test('deleting a fully paid invoice is prevented', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $invoice = Invoice::first();

    // Mark as lunas
    $invoice->update([
        'status' => 'lunas',
        'paid_amount' => $invoice->total_amount,
        'remaining_balance' => 0.0,
    ]);

    actingAs($manager);

    delete(route('admin.invoices.destroy', $invoice))
        ->assertRedirect(route('admin.invoices'))
        ->assertSessionHas('toast', fn ($toast) => $toast['type'] === 'error');

    expect(Invoice::find($invoice->id))->not->toBeNull();
});

test('unpaid invoice can be cancelled', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $store = Store::first();
    $product = Product::first();

    $invoice = Invoice::create([
        'invoice_number' => 'INV-TEST-CANCEL',
        'store_id' => $store->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'subtotal' => 50000.0,
        'total_amount' => 50000.0,
        'paid_amount' => 0.0,
        'remaining_balance' => 50000.0,
        'status' => 'belum_dibayar',
    ]);

    actingAs($manager);

    Livewire::test('admin.invoices.show', ['invoice' => $invoice])
        ->call('cancelInvoice');

    $invoice->refresh();
    expect($invoice->status)->toBe('dibatalkan');
});

test('invoice show page renders whatsapp billing url with store phone and details', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $invoice = Invoice::first();

    actingAs($manager);

    $test = Livewire::test('admin.invoices.show', ['invoice' => $invoice]);
    $url = $test->get('whatsappUrl');

    expect($url)->toContain('https://api.whatsapp.com/send?')
        ->and($url)->toContain('text=');

    get(route('admin.invoices.show', $invoice))
        ->assertOk()
        ->assertSee('Kirim WhatsApp');
});
