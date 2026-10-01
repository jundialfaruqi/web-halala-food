<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('/login', 'auth.login')->name('login');

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::redirect('/admin', '/admin/dashboard');

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'admin.dashboard')->name('admin.dashboard');
    Route::livewire('/users', 'admin.dashboard')->name('admin.users');
    Route::livewire('/roles', 'admin.roles')->name('admin.roles');
});
