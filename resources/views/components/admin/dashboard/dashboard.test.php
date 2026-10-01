<?php

use App\Models\User;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects unauthenticated users from admin dashboard to login page', function () {
    get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

it('renders admin dashboard component successfully', function () {
    $user = User::factory()->create([
        'name' => 'Admin Test',
        'email' => 'admintest@halala-food.id',
    ]);

    actingAs($user);

    Livewire::test('admin.dashboard')
        ->assertStatus(200)
        ->assertSee('Total Pengguna')
        ->assertSee('Daftar Pengguna');
});

it('filters users by search query on admin dashboard', function () {
    $admin = User::factory()->create(['name' => 'Super Admin User', 'email' => 'super@halala-food.id']);
    $user1 = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
    $user2 = User::factory()->create(['name' => 'Siti Aminah', 'email' => 'siti@test.com']);

    actingAs($admin);

    Livewire::test('admin.dashboard')
        ->set('search', 'Budi')
        ->assertSee('Budi Santoso')
        ->assertDontSee('Siti Aminah');
});

