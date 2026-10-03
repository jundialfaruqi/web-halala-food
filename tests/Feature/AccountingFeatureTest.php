<?php

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\AccountingService;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    seed(\Database\Seeders\AccountingSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

test('unauthenticated users are redirected from accounting pages to login', function () {
    get(route('admin.accounting.journals'))->assertRedirect(route('login'));
    get(route('admin.accounting.ledger'))->assertRedirect(route('login'));
    get(route('admin.accounting.financial-statements'))->assertRedirect(route('login'));
});

test('dev and manager roles have accounting permissions while kurir does not', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $permissions = ['jurnal-view', 'laporan-keuangan-view'];

    foreach ($permissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue()
            ->and($kurirRole->hasPermissionTo($perm))->toBeFalse();
    }
});

test('manager can access journals list and see balanced totals', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    // Create a balanced manual journal entry
    AccountingService::postEntry(
        now()->toDateString(),
        'Setoran Modal Awal Tambahan',
        [
            ['account_code' => '1-1001', 'debit' => 1000000, 'credit' => 0, 'memo' => 'Kas Bertambah'],
            ['account_code' => '3-1000', 'debit' => 0, 'credit' => 1000000, 'memo' => 'Modal Bertambah'],
        ]
    );

    get(route('admin.accounting.journals'))
        ->assertOk()
        ->assertSee('Jurnal Umum Akuntansi')
        ->assertSee('Setoran Modal Awal Tambahan')
        ->assertSee('1-1001');

    Livewire::test('admin.accounting.journals.index')
        ->assertSee('Setoran Modal Awal Tambahan')
        ->assertViewHas('totalDebit', fn ($d) => $d >= 1000000)
        ->assertViewHas('totalCredit', fn ($c) => $c >= 1000000);
});

test('manager can view ledger for a specific account', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $cashAccount = ChartOfAccount::where('code', '1-1001')->first();

    actingAs($manager);

    AccountingService::postEntry(
        now()->toDateString(),
        'Penjualan Tunai Harian',
        [
            ['account_code' => '1-1001', 'debit' => 250000, 'credit' => 0, 'memo' => 'Kas Masuk'],
            ['account_code' => '4-1000', 'debit' => 0, 'credit' => 250000, 'memo' => 'Pendapatan Toko'],
        ]
    );

    get(route('admin.accounting.ledger', ['selectedAccountId' => $cashAccount->id]))
        ->assertOk()
        ->assertSee('Buku Besar')
        ->assertSee('Kas Tunai Usaha')
        ->assertSee('Penjualan Tunai Harian');

    Livewire::test('admin.accounting.ledger.index', ['selectedAccountId' => $cashAccount->id])
        ->assertSet('selectedAccountId', $cashAccount->id)
        ->assertSee('Kas Tunai Usaha');
});

test('manager can view financial statements with income statement and balance sheet', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    // Record sample revenue and expense
    AccountingService::postEntry(
        now()->toDateString(),
        'Penjualan Konsinyasi Mitra',
        [
            ['account_code' => '1-1001', 'debit' => 500000, 'credit' => 0],
            ['account_code' => '4-1000', 'debit' => 0, 'credit' => 500000],
        ]
    );

    AccountingService::postEntry(
        now()->toDateString(),
        'Biaya Bensin Kurir',
        [
            ['account_code' => '6-1001', 'debit' => 50000, 'credit' => 0],
            ['account_code' => '1-1001', 'debit' => 0, 'credit' => 50000],
        ]
    );

    // Test Income statement
    get(route('admin.accounting.financial-statements', ['activeTab' => 'income_statement']))
        ->assertOk()
        ->assertSee('Laporan Keuangan Formal')
        ->assertSee('LAPORAN LABA RUGI')
        ->assertSee('TOTAL PENDAPATAN USAHA')
        ->assertSee('LABA BERSIH USAHA (NET PROFIT)');

    // Test Balance Sheet
    get(route('admin.accounting.financial-statements', ['activeTab' => 'balance_sheet']))
        ->assertOk()
        ->assertSee('NERACA KEUANGAN (BALANCE SHEET)')
        ->assertSee('ASET (HARTA USAHA)')
        ->assertSee('TOTAL KEWAJIBAN & EKUITAS');

    // Test component view calculations
    Livewire::test('admin.accounting.financial-statements.index')
        ->set('activeTab', 'income_statement')
        ->assertViewHas('totalRevenue', fn ($rev) => $rev >= 500000)
        ->assertViewHas('totalExpense', fn ($exp) => $exp >= 50000)
        ->set('activeTab', 'balance_sheet')
        ->assertViewHas('totalAssets', fn ($assets) => $assets > 0)
        ->assertViewHas('totalLiabilitiesAndEquity', function ($pasiva) {
            return $pasiva > 0;
        });
});

test('each raw material has a dedicated account in chart of accounts and ledger', function () {
    \App\Models\RawMaterial::firstOrCreate(
        ['name' => 'Wijen Putih Sangrai'],
        [
            'unit' => 'g',
            'stock' => 10000,
            'min_stock' => 2000,
            'cost_per_unit' => 65.0,
        ]
    );

    $materials = \App\Models\RawMaterial::all();
    expect($materials->count())->toBeGreaterThan(0);

    foreach ($materials as $material) {
        $expectedCode = AccountingService::getAccountCodeForRawMaterial($material->id);
        $account = ChartOfAccount::where('code', $expectedCode)->first();

        expect($account)->not->toBeNull()
            ->and($account->name)->toBe('Persediaan Bahan - ' . $material->name)
            ->and($account->type)->toBe('asset')
            ->and($account->normal_balance)->toBe('debit');
    }

    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $wijen = \App\Models\RawMaterial::where('name', 'Wijen Putih Sangrai')->first();
    $wijenAccount = ChartOfAccount::where('code', AccountingService::getAccountCodeForRawMaterial($wijen->id))->first();

    get(route('admin.accounting.ledger', ['selectedAccountId' => $wijenAccount->id]))
        ->assertOk()
        ->assertSee('Persediaan Bahan - Wijen Putih Sangrai')
        ->assertSee($wijenAccount->code);
});

test('creating new raw material automatically creates corresponding chart of account', function () {
    $newMaterial = \App\Models\RawMaterial::create([
        'name' => 'Tepung Terigu Segitiga',
        'unit' => 'gram',
        'stock' => 10000,
        'min_stock' => 2000,
        'cost_per_unit' => 0.012,
    ]);

    $accountCode = AccountingService::getAccountCodeForRawMaterial($newMaterial->id);
    $account = ChartOfAccount::where('code', $accountCode)->first();

    expect($account)->not->toBeNull()
        ->and($account->name)->toBe('Persediaan Bahan - Tepung Terigu Segitiga')
        ->and($account->type)->toBe('asset');
});

