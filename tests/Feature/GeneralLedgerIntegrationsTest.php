<?php

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseItem;
use App\Models\StockMutation;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    seed(AccountingSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Account::firstOrCreate(
        ['name' => 'Kas Tunai Utama'],
        [
            'type' => 'business',
            'balance' => 2000000.0,
            'description' => 'Kas Operasional',
        ]
    );

    $unit = Unit::firstOrCreate(
        ['short_name' => 'kg'],
        ['name' => 'Kilogram', 'is_active' => true]
    );

    RawMaterial::firstOrCreate(
        ['name' => 'Tepung Terigu Segitiga'],
        [
            'unit_id' => $unit->id,
            'unit' => 'kg',
            'stock' => 50,
            'min_stock' => 10,
            'cost_per_unit' => 15000,
        ]
    );

    Product::firstOrCreate(
        ['name' => 'Kue Marie Wijen Halala'],
        [
            'unit_id' => $unit->id,
            'unit' => 'bungkus',
            'consignment_price' => 20000,
            'retail_price' => 25000,
            'stock_ready' => 25,
            'is_active' => true,
        ]
    );
});

test('chart of accounts does not contain any retur pembelian account', function () {
    $returAccounts = ChartOfAccount::where('name', 'like', '%Retur Pembelian%')
        ->orWhere('description', 'like', '%retur pembelian%')
        ->get();

    expect($returAccounts)->toBeEmpty();
});

test('salaries and wages cash transaction automatically journals to 6-1007', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $cashAccount = Account::first();

    $tx = CashTransaction::create([
        'transaction_date' => now()->toDateString(),
        'account_id' => $cashAccount->id,
        'type' => 'expense',
        'category' => 'Gaji & Upah Karyawan',
        'amount' => 500000,
        'description' => 'Gaji mingguan tim produksi dapur',
    ]);

    $journal = AccountingService::recordCashTransaction($tx);

    expect($journal)->not->toBeNull();
    expect($journal->reference_type)->toBe('cash_transaction');

    $journal->load('items.chartOfAccount');
    $debitItem = $journal->items->firstWhere('debit', '>', 0);
    $creditItem = $journal->items->firstWhere('credit', '>', 0);

    expect($debitItem->chartOfAccount?->code)->toBe('6-1007');
    expect((float) $debitItem->debit)->toBe(500000.0);
    expect($creditItem->chartOfAccount?->code)->toBeIn(['1-1001', '1-1002']);
    expect((float) $creditItem->credit)->toBe(500000.0);
});

test('tempo raw material purchase records liability 2-1000 and debt repayment settles it', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $cashAccount = Account::first();
    $rawMat = RawMaterial::first();

    // 1. Catat Pembelian Bahan Baku Tempo
    $purchase = RawMaterialPurchase::create([
        'purchase_number' => 'BELI-TEST-TEMPO-01',
        'supplier_name' => 'Supplier Berkah',
        'purchase_date' => now()->toDateString(),
        'total_amount' => 300000,
        'payment_method' => 'tempo',
        'payment_status' => 'belum_lunas',
        'paid_amount' => 0,
        'created_by' => $manager->id,
    ]);

    RawMaterialPurchaseItem::create([
        'purchase_id' => $purchase->id,
        'raw_material_id' => $rawMat->id,
        'quantity' => 10,
        'cost_per_unit' => 30000,
        'subtotal' => 300000,
    ]);

    $purchaseJournal = AccountingService::recordPurchase($purchase);
    expect($purchaseJournal)->not->toBeNull();

    $purchaseJournal->load('items.chartOfAccount');
    $liabilityItem = $purchaseJournal->items->first(fn ($it) => $it->chartOfAccount?->code === '2-1000');
    expect($liabilityItem)->not->toBeNull();
    expect((float) $liabilityItem->credit)->toBe(300000.0);
    expect($purchase->remaining_debt)->toBe(300000.0);
    expect($purchase->isPaid())->toBeFalse();

    // 2. Pelunasan Hutang via livewire purchases index
    $initialBalance = (float) $cashAccount->balance;

    Livewire::test('admin.purchases.index')
        ->set('payingPurchaseId', $purchase->id)
        ->set('paymentAccountId', $cashAccount->id)
        ->set('paymentDate', now()->toDateString())
        ->set('paymentAmount', 300000)
        ->set('paymentNotes', 'Pelunasan lunas via Kas')
        ->call('saveDebtPayment')
        ->assertHasNoErrors();

    $purchase->refresh();
    expect($purchase->payment_status)->toBe('lunas');
    expect((float) $purchase->paid_amount)->toBe(300000.0);
    expect($purchase->remaining_debt)->toBe(0.0);
    expect($purchase->isPaid())->toBeTrue();

    // Pastikan saldo kas berkurang
    $cashAccount->refresh();
    expect((float) $cashAccount->balance)->toBe($initialBalance - 300000.0);

    // Pastikan jurnal pelunasan hutang terposting: Debet 2-1000 vs Kredit Kas
    $payJournal = JournalEntry::with('items.chartOfAccount')
        ->where('reference_type', 'purchase_payment')
        ->where('reference_id', $purchase->id)
        ->first();

    expect($payJournal)->not->toBeNull();
    $debitItem = $payJournal->items->firstWhere('debit', '>', 0);
    $creditItem = $payJournal->items->firstWhere('credit', '>', 0);

    expect($debitItem->chartOfAccount?->code)->toBe('2-1000');
    expect((float) $debitItem->debit)->toBe(300000.0);
    expect($creditItem->chartOfAccount?->code)->toBeIn(['1-1001', '1-1002']);
    expect((float) $creditItem->credit)->toBe(300000.0);
});

test('fixed asset creation and monthly depreciation post to 6-1005 and 1-2100', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $asset = FixedAsset::create([
        'name' => 'Mesin Oven Industri',
        'category' => 'mesin',
        'asset_code' => 'AST-999',
        'purchase_date' => now()->toDateString(),
        'purchase_price' => 3600000,
        'useful_life_months' => 36,
        'accumulated_depreciation' => 0,
        'book_value' => 3600000,
        'condition' => 'baik',
    ]);

    expect($asset->monthly_depreciation)->toBe(100000.0);

    // Posting depresiasi bulanan via Livewire
    Livewire::test('admin.fixed-assets.index')
        ->set('depreciatingAssetId', $asset->id)
        ->set('depreciationDate', now()->toDateString())
        ->set('depreciationAmount', 100000)
        ->set('depreciationNotes', 'Penyusutan bulan ke-1')
        ->call('saveDepreciation')
        ->assertHasNoErrors();

    $asset->refresh();
    expect((float) $asset->accumulated_depreciation)->toBe(100000.0);
    expect((float) $asset->book_value)->toBe(3500000.0);

    $depJournal = JournalEntry::with('items.chartOfAccount')
        ->where('reference_type', 'fixed_asset_depreciation')
        ->where('reference_id', $asset->id)
        ->first();

    expect($depJournal)->not->toBeNull();
    $debit = $depJournal->items->firstWhere('debit', '>', 0);
    $credit = $depJournal->items->firstWhere('credit', '>', 0);

    expect($debit->chartOfAccount?->code)->toBe('6-1005');
    expect((float) $debit->debit)->toBe(100000.0);
    expect($credit->chartOfAccount?->code)->toBe('1-2100');
    expect((float) $credit->credit)->toBe(100000.0);
});

test('stock opname physical adjustment journals to 6-1006 and mutates inventory', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    // 1. Stock opname bahan baku (selisih kurang: dari 50 ke 45)
    $material = RawMaterial::first();
    $material->stock = 50;
    $material->cost_per_unit = 20000;
    $material->save();

    Livewire::test('admin.raw-materials.index')
        ->call('adjustMaterialStock', $material->id, 45, 'Tumpah di dapur', now()->toDateString());

    $material->refresh();
    expect((float) $material->stock)->toBe(45.0);

    $mutation = StockMutation::where('reference_type', 'stock_opname')
        ->where('raw_material_id', $material->id)
        ->latest('id')
        ->first();

    expect($mutation)->not->toBeNull();
    expect($mutation->type)->toBe('out');
    expect((float) $mutation->quantity)->toBe(5.0);

    $matJournal = JournalEntry::with('items.chartOfAccount')
        ->where('reference_type', 'stock_opname_material')
        ->where('reference_id', $material->id)
        ->latest('id')
        ->first();

    expect($matJournal)->not->toBeNull();
    $debitItem = $matJournal->items->firstWhere('debit', '>', 0);
    expect($debitItem->chartOfAccount?->code)->toBe('6-1006');
    expect((float) $debitItem->debit)->toBe(100000.0); // 5 * 20000

    // 2. Stock opname produk jadi (selisih kurang: dari 25 ke 20)
    $product = Product::first();
    $product->stock_ready = 25;
    $product->save();

    Livewire::test('admin.products.index')
        ->call('adjustProductStock', $product->id, 20, 'Rusak display toko', now()->toDateString());

    $product->refresh();
    expect($product->stock_ready)->toBe(20);

    $prodJournal = JournalEntry::with('items.chartOfAccount')
        ->where('reference_type', 'stock_opname_product')
        ->where('reference_id', $product->id)
        ->latest('id')
        ->first();

    expect($prodJournal)->not->toBeNull();
    $prodDebit = $prodJournal->items->firstWhere('debit', '>', 0);
    $prodCredit = $prodJournal->items->firstWhere('credit', '>', 0);
    expect($prodDebit->chartOfAccount?->code)->toBe('6-1006');
    expect($prodCredit->chartOfAccount?->code)->toBe('1-1400');
});
