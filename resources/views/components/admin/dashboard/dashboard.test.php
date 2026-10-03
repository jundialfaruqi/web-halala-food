<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

it('redirects unauthenticated users from admin dashboard to login page', function () {
    get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

it('renders admin dashboard component with user list for users with user-manage permission', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);

    Livewire::test('admin.dashboard')
        ->assertStatus(200)
        ->assertSee('Total Pengguna')
        ->assertSee('Daftar Pengguna')
        ->assertSee('Peringatan Bahan Baku')
        ->assertSee('Omset Bulan Ini');
});

it('hides user list, raw material alerts, and omset for kurir role on admin dashboard', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();

    actingAs($kurir);

    Livewire::test('admin.dashboard')
        ->assertStatus(200)
        ->assertDontSee('Daftar Pengguna')
        ->assertDontSee('Total Pengguna')
        ->assertDontSee('Peringatan Bahan Baku')
        ->assertDontSee('Omset Bulan Ini')
        ->assertSee('Pengantaran Selesai')
        ->assertSee('Pengantaran Surat Jalan Terkini')
        ->assertSee('Stok Siap Antar');
});

it('filters users by search query on admin dashboard for managers', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $user1 = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
    $user2 = User::factory()->create(['name' => 'Siti Aminah', 'email' => 'siti@test.com']);

    actingAs($manager);

    Livewire::test('admin.dashboard')
        ->set('search', 'Budi')
        ->assertSee('Budi Santoso')
        ->assertDontSee('Siti Aminah');
});
