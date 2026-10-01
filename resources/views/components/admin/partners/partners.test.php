<?php

use App\Models\Partner;
use App\Models\ProductVariant;
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

it('allows authorized users with partner-manage permission to access partner and barcode page', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    get(route('admin.partners'))
        ->assertOk()
        ->assertSee('Mitra Toko')
        ->assertSee('Direktori Mitra Toko')
        ->assertSee('Barcode');
});

it('restricts unauthorized users without partner-manage permission from accessing the page', function () {
    $guestRole = Role::firstOrCreate(['name' => 'regular_guest', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($guestRole);
    actingAs($user);

    get(route('admin.partners'))
        ->assertForbidden();
});

it('redirects unauthenticated users to login page', function () {
    get(route('admin.partners'))
        ->assertRedirect(route('login'));
});

it('seeds default supermarket and grocery store partners correctly', function () {
    expect(Partner::where('type', 'supermarket')->count())->toBeGreaterThanOrEqual(2)
        ->and(Partner::where('type', 'grocery_store')->count())->toBeGreaterThanOrEqual(2);

    $tipTop = Partner::where('name', 'like', '%Tip Top%')->first();
    expect($tipTop)->not->toBeNull()
        ->and($tipTop->payment_term_days)->toBe(30)
        ->and($tipTop->paymentTermLabel())->toBe('Tempo 30 Hari')
        ->and($tipTop->typeLabel())->toBe('Supermarket B2B')
        ->and((float) $tipTop->credit_limit)->toBe(15000000.0);
});

it('can create a new partner successfully via Livewire savePartner method', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $component = Livewire::test('admin.partners');

    $result = $component->instance()->savePartner(null, [
        'code' => 'MTR-TEST-999',
        'name' => 'Toko Kelontong Berkah Jaya',
        'type' => 'grocery_store',
        'contact_person' => 'Bpk. Ahmad Fauzi',
        'phone' => '081234567890',
        'whatsapp_number' => '081234567890',
        'address' => 'Jl. Merdeka No. 10',
        'city' => 'Jakarta Selatan',
        'payment_term_days' => 7,
        'credit_limit' => 5000000,
        'current_receivable' => 0,
        'notes' => 'Toko kelontong mitra baru',
        'is_active' => true,
    ]);

    expect($result['success'])->toBeTrue();

    $partner = Partner::where('code', 'MTR-TEST-999')->first();
    expect($partner)->not->toBeNull()
        ->and($partner->name)->toBe('Toko Kelontong Berkah Jaya')
        ->and($partner->type)->toBe('grocery_store')
        ->and($partner->phone)->toBe('6281234567890')
        ->and($partner->whatsappUrl())->toBe('https://wa.me/6281234567890');
});

it('validates required fields when saving a partner', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $component = Livewire::test('admin.partners');

    $result = $component->instance()->savePartner(null, [
        'code' => '',
        'name' => '',
        'type' => 'invalid_type',
        'payment_term_days' => -5,
        'credit_limit' => -100,
        'current_receivable' => -10,
        'is_active' => true,
    ]);

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toHaveKeys(['name', 'type', 'payment_term_days', 'credit_limit', 'current_receivable']);
});

it('can update existing partner details', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $partner = Partner::first();
    expect($partner)->not->toBeNull();

    $component = Livewire::test('admin.partners');

    $result = $component->instance()->savePartner($partner->id, [
        'code' => $partner->code,
        'name' => 'Nama Mitra Diperbarui',
        'type' => 'supermarket',
        'contact_person' => 'Ibu Linda',
        'phone' => '081399998888',
        'whatsapp_number' => '081399998888',
        'address' => 'Alamat Baru No. 123',
        'city' => 'Bandung',
        'payment_term_days' => 14,
        'credit_limit' => 20000000,
        'current_receivable' => 1500000,
        'notes' => 'Update catatan',
        'is_active' => true,
    ]);

    expect($result['success'])->toBeTrue();

    $partner->refresh();
    expect($partner->name)->toBe('Nama Mitra Diperbarui')
        ->and($partner->city)->toBe('Bandung')
        ->and($partner->payment_term_days)->toBe(14)
        ->and((float) $partner->credit_limit)->toBe(20000000.0);
});

it('prevents deleting a partner with active receivables', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $partnerWithDebt = Partner::where('current_receivable', '>', 0)->first();
    expect($partnerWithDebt)->not->toBeNull();

    $component = Livewire::test('admin.partners');

    $result = $component->instance()->deletePartner($partnerWithDebt->id);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('saldo piutang berjalan');

    expect(Partner::find($partnerWithDebt->id))->not->toBeNull();
});

it('allows deleting a partner with zero receivable', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $partnerZeroDebt = Partner::where('current_receivable', 0)->first();
    expect($partnerZeroDebt)->not->toBeNull();

    $component = Livewire::test('admin.partners');

    $result = $component->instance()->deletePartner($partnerZeroDebt->id);

    expect($result['success'])->toBeTrue();
    expect(Partner::find($partnerZeroDebt->id))->toBeNull();
});

it('generates clean SVG barcode using Picqer BarcodeGenerator', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $component = Livewire::test('admin.partners');

    $svgContent = $component->instance()->getBarcodeSvg('8991234001011');

    expect($svgContent)->toBeString()
        ->and($svgContent)->toContain('<svg')
        ->and($svgContent)->toContain('</svg>');
});

it('can update product variant barcode directly from generator tab', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $variant = ProductVariant::first();
    expect($variant)->not->toBeNull();

    $component = Livewire::test('admin.partners');

    $newBarcode = '8999999000123';
    $result = $component->instance()->updateVariantBarcode($variant->id, $newBarcode);

    expect($result['success'])->toBeTrue();

    $variant->refresh();
    expect($variant->barcode)->toBe($newBarcode);
});
