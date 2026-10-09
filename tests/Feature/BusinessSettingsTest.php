<?php

use App\Models\BusinessSetting;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Store;
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

test('unauthenticated users are redirected from settings page to login', function () {
    get(route('admin.settings'))
        ->assertRedirect(route('login'));
});

test('role dev and manager have pengaturan permissions while kurir does not', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    expect($devRole->hasPermissionTo('pengaturan-view'))->toBeTrue()
        ->and($devRole->hasPermissionTo('pengaturan-edit'))->toBeTrue()
        ->and($managerRole->hasPermissionTo('pengaturan-view'))->toBeTrue()
        ->and($managerRole->hasPermissionTo('pengaturan-edit'))->toBeTrue()
        ->and($kurirRole->hasPermissionTo('pengaturan-view'))->toBeFalse()
        ->and($kurirRole->hasPermissionTo('pengaturan-edit'))->toBeFalse();
});

test('manager can view the business settings page', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);
    get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Pengaturan Profil &amp; Usaha', false)
        ->assertSee('Rekening Bank Resmi Pembayaran');
});

test('manager can update business profile and bank accounts', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    Livewire::test('admin.settings')
        ->set('company_name', 'HALALA FOOD INDONESIA')
        ->set('legal_name', 'PT Halala Berkah Sentosa')
        ->set('tagline', 'Snack Halal & Higienis Terpercaya')
        ->set('phone', '0811-2233-4455')
        ->set('email', 'halo@halala-food.id')
        ->set('address', 'Kota Malang, Jawa Timur, Indonesia')
        ->set('bank_accounts', [
            [
                'bank_name' => 'BSI',
                'account_number' => '711-2233-445',
                'account_name' => 'PT Halala Berkah Sentosa',
            ],
            [
                'bank_name' => 'BCA',
                'account_number' => '888-0099-112',
                'account_name' => 'PT Halala Berkah Sentosa',
            ],
        ])
        ->set('invoice_notes', 'Catatan faktur kustom untuk penagihan')
        ->set('delivery_notes', 'Catatan surat jalan kustom saat pengiriman')
        ->call('save')
        ->assertHasNoErrors();

    $settings = BusinessSetting::first();
    expect($settings->company_name)->toBe('HALALA FOOD INDONESIA')
        ->and($settings->legal_name)->toBe('PT Halala Berkah Sentosa')
        ->and($settings->phone)->toBe('0811-2233-4455')
        ->and(count($settings->bank_accounts))->toBe(2)
        ->and($settings->bank_accounts[0]['bank_name'])->toBe('BSI');
});

test('invoice and delivery detail pages render dynamic business settings and bank accounts', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    $settings = BusinessSetting::getSettings();
    $settings->update([
        'company_name' => 'HALALA CEMILAN NUSANTARA',
        'bank_accounts' => [
            [
                'bank_name' => 'BANK JATIM SYARIAH',
                'account_number' => '600-1122-3344',
                'account_name' => 'CV Halala Cemilan',
            ],
        ],
    ]);

    $store = Store::create([
        'name' => 'Toko Barokah',
        'owner_name' => 'Pak Budi',
        'phone' => '081234567890',
        'address' => 'Jl. Kebon Jeruk',
        'is_active' => true,
    ]);

    $invoice = Invoice::create([
        'invoice_number' => 'INV-TEST-001',
        'store_id' => $store->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'subtotal' => 100000,
        'total_amount' => 100000,
        'paid_amount' => 0,
        'remaining_balance' => 100000,
        'status' => 'belum_dibayar',
    ]);

    $delivery = Delivery::create([
        'delivery_number' => 'SJ-TEST-001',
        'store_id' => $store->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 5,
        'total_value' => 50000,
    ]);

    actingAs($manager);

    get(route('admin.invoices.show', $invoice))
        ->assertOk()
        ->assertSee('HALALA CEMILAN NUSANTARA')
        ->assertSee('BANK JATIM SYARIAH: 600-1122-3344 a.n CV Halala Cemilan');

    get(route('admin.deliveries.show', $delivery))
        ->assertOk()
        ->assertSee('HALALA CEMILAN NUSANTARA');
});
