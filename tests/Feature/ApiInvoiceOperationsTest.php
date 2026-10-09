<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

/**
 * Helper to create standard invoice test fixture.
 *
 * @return array{product: Product, store: Store, manager: User, token: string, invoice: Invoice, invoiceItem: InvoiceItem}
 */
function createTestInvoiceData(): array
{
    $unit = Unit::firstOrCreate(['short_name' => 'pcs'], ['name' => 'Pcs', 'is_active' => true]);
    $product = Product::firstOrCreate(
        ['name' => 'Roti Manis Cokelat'],
        [
            'unit_id' => $unit->id,
            'consignment_price' => 10000,
            'retail_price' => 12000,
            'stock' => 100,
            'stock_ready' => 100,
            'min_stock' => 10,
            'is_active' => true,
        ]
    );

    $store = Store::firstOrCreate(
        ['name' => 'Toko Barokah Jaya'],
        [
            'owner_name' => 'Pak Barokah',
            'phone' => '081234567890',
            'address' => 'Jl. Pahlawan No. 1',
            'route' => 'Rute Barat',
            'is_active' => true,
        ]
    );

    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $invoice = Invoice::create([
        'invoice_number' => 'INV-TEST-'.uniqid(),
        'store_id' => $store->id,
        'created_by' => $manager->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'subtotal' => 100000,
        'discount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 0,
        'remaining_balance' => 100000,
        'status' => 'belum_dibayar',
    ]);

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'delivered_quantity' => 10,
        'remaining_quantity' => 0,
        'damaged_quantity' => 0,
        'returned_quantity' => 0,
        'unit_price' => 10000,
        'subtotal' => 100000,
    ]);

    return [
        'product' => $product,
        'store' => $store,
        'manager' => $manager,
        'token' => $token,
        'invoice' => $invoice,
        'invoiceItem' => $invoiceItem,
    ];
}

test('user can record payment for an invoice and delete it', function () {
    [
        'token' => $token,
        'invoice' => $invoice,
    ] = createTestInvoiceData();

    // 1. Record partial payment
    $resPay = withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/invoices/{$invoice->id}/payments", [
            'payment_amount' => 40000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'tunai',
            'notes' => 'Pembayaran DP',
        ]);

    $resPay->assertCreated()
        ->assertJsonPath('success', true);

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(40000.0)
        ->and((float) $invoice->remaining_balance)->toBe(60000.0)
        ->and($invoice->status)->toBe('sebagian');

    $paymentId = $resPay->json('data.payment.id');

    // 2. Delete payment
    $resDelPay = withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/invoices/{$invoice->id}/payments/{$paymentId}");

    $resDelPay->assertOk()
        ->assertJsonPath('success', true);

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(0.0)
        ->and((float) $invoice->remaining_balance)->toBe(100000.0)
        ->and($invoice->status)->toBe('belum_dibayar');

    // 3. Record payment using 'amount' payload alias and 'transfer' method
    $resPayAlias = withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/invoices/{$invoice->id}/payments", [
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
        ]);

    $resPayAlias->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.payment.payment_method', 'transfer_bank');

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toBe(50000.0)
        ->and((float) $invoice->remaining_balance)->toBe(50000.0);
});

test('user can reconcile consignment invoice items and restore returned stock', function () {
    [
        'product' => $product,
        'token' => $token,
        'invoice' => $invoice,
        'invoiceItem' => $invoiceItem,
    ] = createTestInvoiceData();

    $initialStock = $product->stock_ready;

    $resRec = withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/invoices/{$invoice->id}/reconcile", [
            'items' => [
                [
                    'id' => $invoiceItem->id,
                    'remaining_quantity' => 2, // 2 masih di toko
                    'damaged_quantity' => 1,   // 1 rusak
                    'returned_quantity' => 3,  // 3 ditarik retur
                    // sold = 10 - 2 - 1 - 3 = 4 terjual (4 x 10000 = 40.000)
                ],
            ],
        ]);

    $resRec->assertOk()
        ->assertJsonPath('success', true);

    $invoice->refresh();
    $invoiceItem->refresh();
    $product->refresh();

    expect((int) $invoiceItem->quantity)->toBe(4)
        ->and((float) $invoiceItem->subtotal)->toBe(40000.0)
        ->and((float) $invoice->total_amount)->toBe(40000.0)
        ->and((float) $invoice->remaining_balance)->toBe(40000.0)
        ->and($product->stock_ready)->toBe($initialStock + 3); // stok bertambah 3 dari retur
});

test('user can update invoice details and items', function () {
    [
        'product' => $product,
        'store' => $store,
        'token' => $token,
        'invoice' => $invoice,
    ] = createTestInvoiceData();

    $resUpdate = withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/invoices/{$invoice->id}", [
            'store_id' => $store->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'discount' => 5000,
            'notes' => 'Catatan revisi',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 8,
                    'unit_price' => 10000,
                ],
            ],
        ]);

    $resUpdate->assertOk()
        ->assertJsonPath('success', true);

    $invoice->refresh();
    expect((float) $invoice->discount)->toBe(5000.0)
        ->and((float) $invoice->subtotal)->toBe(80000.0)
        ->and((float) $invoice->total_amount)->toBe(75000.0)
        ->and($invoice->notes)->toBe('Catatan revisi');
});

test('user can cancel and delete unpaid invoice', function () {
    [
        'token' => $token,
        'invoice' => $invoice,
    ] = createTestInvoiceData();

    // Cancel invoice
    $resCancel = withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/invoices/{$invoice->id}/cancel");

    $resCancel->assertOk()
        ->assertJsonPath('success', true);

    $invoice->refresh();
    expect($invoice->status)->toBe('dibatalkan');

    // Delete invoice
    $resDelete = withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/invoices/{$invoice->id}");

    $resDelete->assertOk()
        ->assertJsonPath('success', true);

    expect(Invoice::find($invoice->id))->toBeNull();
});

test('courier assigned to invoice can record payment and reconcile but cannot delete payment via API', function () {
    [
        'manager' => $manager,
        'token' => $managerToken,
        'invoice' => $invoice,
        'invoiceItem' => $invoiceItem,
    ] = createTestInvoiceData();

    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $invoice->update(['courier_id' => $kurir->id]);
    $kurirToken = JWTAuth::fromUser($kurir);

    // 1. Courier can record payment for this assigned invoice
    $resPay = withHeader('Authorization', "Bearer {$kurirToken}")
        ->postJson("/api/invoices/{$invoice->id}/payments", [
            'amount' => 20000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'tunai',
        ]);

    $resPay->assertCreated()
        ->assertJsonPath('success', true);

    $paymentId = $resPay->json('data.payment.id');

    // 2. Courier can reconcile this assigned invoice
    $resRec = withHeader('Authorization', "Bearer {$kurirToken}")
        ->postJson("/api/invoices/{$invoice->id}/reconcile", [
            'items' => [
                [
                    'id' => $invoiceItem->id,
                    'remaining_quantity' => 1,
                    'damaged_quantity' => 0,
                    'returned_quantity' => 1,
                ],
            ],
        ]);

    $resRec->assertOk()
        ->assertJsonPath('success', true);

    // 3. Courier CANNOT delete payment (403 Forbidden)
    $resDeleteForbidden = withHeader('Authorization', "Bearer {$kurirToken}")
        ->deleteJson("/api/invoices/{$invoice->id}/payments/{$paymentId}");

    $resDeleteForbidden->assertForbidden()
        ->assertJsonPath('success', false);

    // 4. Manager CAN delete payment
    auth('api')->setUser($manager);
    $resDeleteAllowed = withHeader('Authorization', "Bearer {$managerToken}")
        ->deleteJson("/api/invoices/{$invoice->id}/payments/{$paymentId}");

    $resDeleteAllowed->assertOk()
        ->assertJsonPath('success', true);
});

