<?php

use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::livewire('/login', 'auth.login')->name('login')->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    session()->flash('toast', [
        'message' => 'Anda telah berhasil keluar dari akun.',
        'type'    => 'success',
    ]);

    return redirect()->route('login');
})->name('logout');

Route::redirect('/admin', '/admin/dashboard');

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'admin.dashboard')->name('admin.dashboard')->middleware('permission:dashboard-view');
    Route::livewire('/users', 'admin.users')->name('admin.users')->middleware('permission:user-manage');
    Route::livewire('/roles', 'admin.roles')->name('admin.roles')->middleware('permission:role-manage|permission-manage');

    // Master Satuan
    Route::livewire('/units', 'admin.units.index')->name('admin.units')->middleware('permission:satuan-view');
    Route::livewire('/units/create', 'admin.units.create')->name('admin.units.create')->middleware('permission:satuan-create');
    Route::livewire('/units/{unit}/edit', 'admin.units.edit')->name('admin.units.edit')->middleware('permission:satuan-edit');
    Route::delete('/units/{unit}', function (Unit $unit) {
        $unitName = $unit->name;
        $unit->delete();
        session()->flash('toast', [
            'message' => "Satuan '{$unitName}' berhasil dihapus.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.units');
    })->name('admin.units.destroy')->middleware('permission:satuan-delete');

    // Master Produk Jadi & Harga Konsinyasi
    Route::livewire('/products', 'admin.products.index')->name('admin.products')->middleware('permission:produk-view');
    Route::livewire('/products/create', 'admin.products.create')->name('admin.products.create')->middleware('permission:produk-create');
    Route::livewire('/products/{product}/edit', 'admin.products.edit')->name('admin.products.edit')->middleware('permission:produk-edit');
    Route::delete('/products/{product}', function (\App\Models\Product $product) {
        $hasRecipes = $product->recipes()->exists();
        $hasBatches = $product->productionBatches()->exists();
        $hasDeliveries = \App\Models\DeliveryItem::where('product_id', $product->id)->exists();
        $hasInvoices = \App\Models\InvoiceItem::where('product_id', $product->id)->exists();

        if ($hasRecipes || $hasBatches || $hasDeliveries || $hasInvoices) {
            session()->flash('toast', [
                'message' => "Produk '{$product->name}' tidak dapat dihapus karena sudah memiliki riwayat produksi, resep, atau transaksi. Silakan nonaktifkan status produk sebagai gantinya.",
                'type' => 'error',
            ]);
            return redirect()->route('admin.products');
        }

        $name = $product->name;
        $product->delete();

        session()->flash('toast', [
            'message' => "Produk '{$name}' berhasil dihapus.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.products');
    })->name('admin.products.destroy')->middleware('permission:produk-delete');

    // Pengadaan & Pembelian Bahan Baku
    Route::livewire('/purchases', 'admin.purchases.index')->name('admin.purchases')->middleware('permission:pembelian-view');
    Route::livewire('/purchases/create', 'admin.purchases.create')->name('admin.purchases.create')->middleware('permission:pembelian-create');

    // Master Bahan Baku & Resep Produk (BOM)
    Route::livewire('/raw-materials', 'admin.raw-materials.index')->name('admin.raw-materials')->middleware('permission:bahan-baku-view');

    // Produksi & Manufaktur (Eksekusi Batch Masak)
    Route::livewire('/production', 'admin.production.index')->name('admin.production')->middleware('permission:produksi-view');

    // Mitra Toko & Distribusi
    Route::livewire('/stores', 'admin.stores.index')->name('admin.stores')->middleware('permission:toko-view');
    Route::livewire('/stores/create', 'admin.stores.create')->name('admin.stores.create')->middleware('permission:toko-create');
    Route::livewire('/stores/{store}/edit', 'admin.stores.edit')->name('admin.stores.edit')->middleware('permission:toko-edit');
    Route::delete('/stores/{store}', function (\App\Models\Store $store) {
        $storeName = $store->name;
        $store->delete();
        session()->flash('toast', [
            'message' => "Toko mitra '{$storeName}' berhasil dihapus.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.stores');
    })->name('admin.stores.destroy')->middleware('permission:toko-delete');

    // Pengantaran & Surat Jalan (Distribusi)
    Route::livewire('/deliveries', 'admin.deliveries.index')->name('admin.deliveries')->middleware('permission:pengantaran-view');
    Route::livewire('/deliveries/create', 'admin.deliveries.create')->name('admin.deliveries.create')->middleware('permission:pengantaran-create');
    Route::livewire('/deliveries/{delivery}', 'admin.deliveries.show')->name('admin.deliveries.show')->middleware('permission:pengantaran-view');
    Route::livewire('/deliveries/{delivery}/edit', 'admin.deliveries.edit')->name('admin.deliveries.edit')->middleware('permission:pengantaran-edit');
    Route::delete('/deliveries/{delivery}', function (\App\Models\Delivery $delivery) {
        if ($delivery->status === 'selesai') {
            session()->flash('toast', [
                'message' => 'Surat jalan yang telah selesai serah terima tidak boleh dihapus.',
                'type' => 'error',
            ]);
            return redirect()->route('admin.deliveries');
        }

        $deliveryNumber = $delivery->delivery_number;
        \Illuminate\Support\Facades\DB::transaction(function () use ($delivery) {
            if ($delivery->status !== 'dibatalkan') {
                foreach ($delivery->items as $item) {
                    \App\Models\Product::where('id', $item->product_id)->increment('stock_ready', $item->quantity);
                }
            }
            $delivery->delete();
        });

        session()->flash('toast', [
            'message' => "Surat jalan '{$deliveryNumber}' berhasil dihapus.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.deliveries');
    })->name('admin.deliveries.destroy')->middleware('permission:pengantaran-delete');

    // Buku Kas & Keuangan
    Route::livewire('/cash-book', 'admin.cash-book.index')->name('admin.cash-book')->middleware('permission:buku-kas-view');

    // Aset Tetap Usaha
    Route::livewire('/fixed-assets', 'admin.fixed-assets.index')->name('admin.fixed-assets')->middleware('permission:aset-view');


    // Faktur Penagihan & Piutang Toko (Keuangan)
    Route::livewire('/invoices', 'admin.invoices.index')->name('admin.invoices')->middleware('permission:faktur-view');
    Route::livewire('/invoices/create', 'admin.invoices.create')->name('admin.invoices.create')->middleware('permission:faktur-create');
    Route::livewire('/invoices/{invoice}', 'admin.invoices.show')->name('admin.invoices.show')->middleware('permission:faktur-view');
    Route::livewire('/invoices/{invoice}/edit', 'admin.invoices.edit')->name('admin.invoices.edit')->middleware('permission:faktur-edit');

    // Jurnal Umum & Akuntansi
    Route::livewire('/accounting/journals', 'admin.accounting.journals.index')->name('admin.accounting.journals')->middleware('permission:jurnal-view');
    Route::livewire('/accounting/ledger', 'admin.accounting.ledger.index')->name('admin.accounting.ledger')->middleware('permission:jurnal-view');
    Route::livewire('/accounting/financial-statements', 'admin.accounting.financial-statements.index')->name('admin.accounting.financial-statements')->middleware('permission:laporan-keuangan-view');

    Route::livewire('/reports', 'admin.reports.index')->name('admin.reports')->middleware('permission:laporan-view');
    Route::delete('/invoices/{invoice}', function (\App\Models\Invoice $invoice) {
        if ($invoice->status === 'lunas') {
            session()->flash('toast', [
                'message' => 'Faktur yang telah lunas tidak boleh dihapus demi integritas data keuangan.',
                'type' => 'error',
            ]);
            return redirect()->route('admin.invoices');
        }

        $invoiceNumber = $invoice->invoice_number;
        $invoice->delete();

        session()->flash('toast', [
            'message' => "Faktur '{$invoiceNumber}' berhasil dihapus.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.invoices');
    })->name('admin.invoices.destroy')->middleware('permission:faktur-delete');

    // Pengaturan Usaha (Profil & Rekening)
    Route::livewire('/settings', 'admin.settings')->name('admin.settings')->middleware('permission:pengaturan-view');
});
