<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

it('renders login page successfully', function () {
    Livewire::test('auth.login')
        ->assertStatus(200);
});

it('validates required fields', function () {
    Livewire::test('auth.login')
        ->set('email', '')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['email' => 'required', 'password' => 'required']);
});

it('authenticates user with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@halala-food.id',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test('auth.login')
        ->set('email', 'test@halala-food.id')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    expect(Auth::check())->toBeTrue();
});

it('fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@halala-food.id',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test('auth.login')
        ->set('email', 'test@halala-food.id')
        ->set('password', 'wrongpassword')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::check())->toBeFalse();
});

