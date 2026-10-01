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
    Route::livewire('/products', 'admin.products')->name('admin.products')->middleware('permission:product-manage|category-manage|recipe-manage|raw-material-manage');
    Route::livewire('/production', 'admin.production')->name('admin.production')->middleware('permission:production-manage|stock-manage|waste-manage');
    Route::livewire('/partners', 'admin.partners')->name('admin.partners')->middleware('permission:partner-manage|product-manage');
    Route::livewire('/deliveries', 'admin.deliveries')->name('admin.deliveries')->middleware('permission:delivery-manage|partner-manage');
    Route::livewire('/users', 'admin.users')->name('admin.users')->middleware('permission:user-manage');
    Route::livewire('/roles', 'admin.roles')->name('admin.roles')->middleware('permission:role-manage|permission-manage');
});
