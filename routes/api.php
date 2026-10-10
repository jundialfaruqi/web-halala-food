<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashBookController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\RawMaterialController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:api']]);

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
    // Sinkronisasi Device Token (FCM Push Notification)
    Route::post('/user/fcm-token', [UserController::class, 'updateFcmToken'])->name('api.user.fcm_token');

    // Manajemen Pengguna (Staf & Hak Akses)
    Route::get('/users/roles', [UserController::class, 'roles'])->name('api.users.roles');
    Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('api.users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('api.users.show');
    Route::match(['put', 'patch', 'post'], '/users/{user}', [UserController::class, 'update'])->name('api.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('api.users.destroy');

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

    // Surat Jalan & Pengantaran
    Route::get('/deliveries/options', [DeliveryController::class, 'options'])->name('api.deliveries.options');
    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('api.deliveries.index');
    Route::post('/deliveries', [DeliveryController::class, 'store'])->name('api.deliveries.store');
    Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show'])->name('api.deliveries.show');
    Route::match(['put', 'patch', 'post'], '/deliveries/{delivery}', [DeliveryController::class, 'update'])->name('api.deliveries.update');
    Route::delete('/deliveries/{delivery}', [DeliveryController::class, 'destroy'])->name('api.deliveries.destroy');
    Route::post('/deliveries/{delivery}/dispatch', [DeliveryController::class, 'dispatchDelivery'])->name('api.deliveries.dispatch');
    Route::post('/deliveries/{delivery}/complete', [DeliveryController::class, 'completeDelivery'])->name('api.deliveries.complete');
    Route::post('/deliveries/{delivery}/cancel', [DeliveryController::class, 'cancelDelivery'])->name('api.deliveries.cancel');

    // Faktur & Piutang Toko
    Route::get('/invoices/create-options', [InvoiceController::class, 'createOptions'])->name('api.invoices.create-options');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('api.invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('api.invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('api.invoices.show');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('api.invoices.update');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('api.invoices.cancel');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('api.invoices.destroy');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('api.invoices.record-payment');
    Route::delete('/invoices/{invoice}/payments/{payment}', [InvoiceController::class, 'deletePayment'])->name('api.invoices.delete-payment');
    Route::post('/invoices/{invoice}/reconcile', [InvoiceController::class, 'reconcile'])->name('api.invoices.reconcile');

    // Master Bahan Baku & Resep Produk (BOM)
    Route::get('/raw-materials/options', [RawMaterialController::class, 'options'])->name('api.raw-materials.options');
    Route::get('/raw-materials', [RawMaterialController::class, 'index'])->name('api.raw-materials.index');
    Route::post('/raw-materials', [RawMaterialController::class, 'store'])->name('api.raw-materials.store');
    Route::get('/raw-materials/{rawMaterial}', [RawMaterialController::class, 'show'])->name('api.raw-materials.show');
    Route::match(['put', 'patch', 'post'], '/raw-materials/{rawMaterial}', [RawMaterialController::class, 'update'])->name('api.raw-materials.update');
    Route::delete('/raw-materials/{rawMaterial}', [RawMaterialController::class, 'destroy'])->name('api.raw-materials.destroy');
    Route::post('/raw-materials/{rawMaterial}/adjust-stock', [RawMaterialController::class, 'adjustStock'])->name('api.raw-materials.adjust-stock');

    // Resep Produk (BOM)
    Route::get('/recipes', [RawMaterialController::class, 'recipes'])->name('api.recipes.index');
    Route::post('/recipes/{product}', [RawMaterialController::class, 'saveRecipe'])->name('api.recipes.save');

    // Produksi & Manufaktur (Batch Masak & Kartu Stok)
    Route::get('/productions/options', [ProductionController::class, 'options'])->name('api.productions.options');
    Route::get('/productions/mutations', [ProductionController::class, 'mutations'])->name('api.productions.mutations');
    Route::get('/productions', [ProductionController::class, 'index'])->name('api.productions.index');
    Route::post('/productions', [ProductionController::class, 'store'])->name('api.productions.store');
    Route::get('/productions/{productionBatch}', [ProductionController::class, 'show'])->name('api.productions.show');
    Route::post('/productions/{productionBatch}/cancel', [ProductionController::class, 'cancel'])->name('api.productions.cancel');

    // Buku Kas & Keuangan
    Route::get('/cash-book', [CashBookController::class, 'index'])->name('api.cash-book.index');
    Route::post('/cash-book/transactions', [CashBookController::class, 'store'])->name('api.cash-book.store');
    Route::get('/cash-book/transactions/{id}', [CashBookController::class, 'show'])->name('api.cash-book.show');
    Route::delete('/cash-book/transactions/{id}', [CashBookController::class, 'destroy'])->name('api.cash-book.destroy');
    Route::post('/cash-book/accounts', [CashBookController::class, 'storeAccount'])->name('api.cash-book.store-account');
});
