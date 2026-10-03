<?php

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\JournalEntry;
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

test('unauthenticated users are redirected from cash book to login', function () {
    get(route('admin.cash-book'))
        ->assertRedirect(route('login'));
});

test('dev and manager roles have cash book permissions while kurir does not', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $permissions = ['buku-kas-view', 'buku-kas-create', 'buku-kas-delete'];

    foreach ($permissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue()
            ->and($kurirRole->hasPermissionTo($perm))->toBeFalse();
    }
});

test('manager can view cash book page and summary cards', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    get(route('admin.cash-book'))
        ->assertOk()
        ->assertSee('Buku Kas & Keuangan')
        ->assertSee('Total Saldo Kas Usaha')
        ->assertSee('Total Kas Belanja Pribadi');
});

test('manager can add new account and record transaction with auto-journaling', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    // 1. Add new account
    Livewire::test('admin.cash-book.index')
        ->set('account_name', 'Kas Toko Cabang')
        ->set('account_type', 'business')
        ->set('initial_balance', 500000)
        ->call('saveAccount')
        ->assertHasNoErrors();

    $newAccount = Account::where('name', 'Kas Toko Cabang')->first();
    expect($newAccount)->not->toBeNull()
        ->and((float) $newAccount->balance)->toBe(500000.0);

    // 2. Record an expense transaction
    Livewire::test('admin.cash-book.index')
        ->set('transaction_date', now()->toDateString())
        ->set('account_id', $newAccount->id)
        ->set('type', 'expense')
        ->set('category', 'Bensin & Transportasi')
        ->set('amount', 50000)
        ->set('description', 'Bensin kurir rute timur')
        ->call('saveTransaction')
        ->assertHasNoErrors();

    $newAccount->refresh();
    expect((float) $newAccount->balance)->toBe(450000.0);

    $transaction = CashTransaction::where('account_id', $newAccount->id)->latest('id')->first();
    expect($transaction)->not->toBeNull()
        ->and((float) $transaction->amount)->toBe(50000.0);

    // Check auto-journal
    $journalEntry = JournalEntry::where('reference_type', 'cash_transaction')
        ->where('reference_id', $transaction->id)
        ->with('items')
        ->first();

    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->isBalanced())->toBeTrue()
        ->and((float) $journalEntry->total_debit)->toBe(50000.0)
        ->and((float) $journalEntry->total_credit)->toBe(50000.0);
});

test('manager can delete a cash transaction and balance is restored', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $account = Account::first();
    $originalBalance = (float) $account->balance;

    actingAs($manager);

    // Record expense
    Livewire::test('admin.cash-book.index')
        ->set('transaction_date', now()->toDateString())
        ->set('account_id', $account->id)
        ->set('type', 'expense')
        ->set('category', 'Listrik & Air')
        ->set('amount', 100000)
        ->call('saveTransaction');

    $account->refresh();
    expect((float) $account->balance)->toBe($originalBalance - 100000.0);

    $trx = CashTransaction::where('account_id', $account->id)->latest('id')->first();

    // Delete transaction
    Livewire::test('admin.cash-book.index')
        ->set('deletingTransactionId', $trx->id)
        ->call('deleteTransaction');

    $account->refresh();
    expect((float) $account->balance)->toBe($originalBalance);
    expect(CashTransaction::find($trx->id))->toBeNull();
    expect(JournalEntry::where('reference_type', 'cash_transaction')->where('reference_id', $trx->id)->exists())->toBeFalse();
});
