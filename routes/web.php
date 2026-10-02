<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('/login', 'auth.login')->name('login')->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::redirect('/admin', '/admin/dashboard');

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'admin.dashboard')->name('admin.dashboard');
    Route::livewire('/users', 'admin.users')->name('admin.users')->middleware('permission:user-manage');
    Route::livewire('/roles', 'admin.roles')->name('admin.roles')->middleware('permission:role-manage|permission-manage');

    // Master Satuan
    Route::livewire('/units', 'admin.units.index')->name('admin.units')->middleware('permission:satuan-view');
    Route::livewire('/units/create', 'admin.units.create')->name('admin.units.create')->middleware('permission:satuan-create');
    Route::livewire('/units/{unit}/edit', 'admin.units.edit')->name('admin.units.edit')->middleware('permission:satuan-edit');
    Route::delete('/units/{unit}', function (\App\Models\Unit $unit) {
        $unitName = $unit->name;
        $unit->delete();
        session()->flash('toast', [
            'message' => "Satuan '{$unitName}' berhasil dihapus.",
            'type' => 'success',
        ]);
        return redirect()->route('admin.units');
    })->name('admin.units.destroy')->middleware('permission:satuan-delete');

    // Master Bahan Baku & Resep Produk (BOM)
    Route::livewire('/raw-materials', 'admin.raw-materials.index')->name('admin.raw-materials')->middleware('permission:bahan-baku-view');
});
