<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rute API untuk aplikasi mobile Halala Food.
| Menggunakan autentikasi JWT dengan refresh token flow.
|
*/

Route::prefix('auth')->group(function () {
    // Public auth routes
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');

    // Protected auth routes (memerlukan JWT Bearer Token)
    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    });
});

// Protected Business Routes
Route::middleware(['auth:api'])->group(function () {
    // Toko Mitra
    Route::get('/stores/routes', [StoreController::class, 'routes'])->name('api.stores.routes');
    Route::get('/stores', [StoreController::class, 'index'])->name('api.stores.index');
    Route::post('/stores', [StoreController::class, 'store'])->name('api.stores.store');
    Route::get('/stores/{store}', [StoreController::class, 'show'])->name('api.stores.show');
    Route::match(['put', 'patch', 'post'], '/stores/{store}', [StoreController::class, 'update'])->name('api.stores.update');
    Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('api.stores.destroy');

    // Master Produk Jadi
    Route::get('/products/units', [ProductController::class, 'units'])->name('api.products.units');
    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('api.products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('api.products.show');
    Route::match(['put', 'patch', 'post'], '/products/{product}', [ProductController::class, 'update'])->name('api.products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('api.products.destroy');
});
