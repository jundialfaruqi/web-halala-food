<?php

use App\Models\DeliveryItem;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Unit;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Master Produk Jadi & Harga - Halala Food')] class extends Component
{
    public function mount()
    {
        if (Gate::denies('produk-view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat daftar master produk jadi.');
        }
    }

    public function render()
    {
        $products = Product::with(['unitModel'])
            ->orderBy('name')
            ->get()
            ->map(function ($prod) {
                return [
                    'id' => $prod->id,
                    'name' => $prod->name,
                    'unit_id' => $prod->unit_id,
                    'unit_name' => $prod->unitModel?->name ?? $prod->unit,
                    'unit_short' => $prod->unitModel?->short_name ?? $prod->unit,
                    'consignment_price' => (float) $prod->consignment_price,
                    'consignment_price_formatted' => 'Rp '.number_format($prod->consignment_price, 0, ',', '.'),
                    'retail_price' => (float) $prod->retail_price,
                    'retail_price_formatted' => 'Rp '.number_format($prod->retail_price, 0, ',', '.'),
                    'stock_ready' => (int) $prod->stock_ready,
                    'description' => $prod->description ?? '-',
                    'is_active' => (bool) $prod->is_active,
                    'photo_url' => $prod->photo_url,
                    'edit_url' => route('admin.products.edit', $prod->id),
                ];
            });

        $units = Unit::where('is_active', true)->orderBy('name')->get(['id', 'name', 'short_name']);

        return view('components.admin.products.index.index', [
            'products' => $products,
            'units' => $units,
        ]);
    }

    /**
     * Toggle active/inactive status of a product.
     */
    public function toggleStatus(int $id): array
    {
        if (Gate::denies('produk-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengubah status produk.'];
        }

        $product = Product::find($id);
        if (! $product) {
            return ['success' => false, 'message' => 'Data produk tidak ditemukan.'];
        }

        $product->is_active = ! $product->is_active;
        $product->save();

        return [
            'success' => true,
            'message' => 'Status produk '.$product->name.' berhasil diubah menjadi '.($product->is_active ? 'Aktif' : 'Nonaktif').'.',
        ];
    }

    /**
     * Delete a product by ID with relational safety check.
     */
    public function deleteProduct(int $id): array
    {
        if (Gate::denies('produk-delete')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus produk.'];
        }

        $product = Product::find($id);
        if (! $product) {
            return ['success' => false, 'message' => 'Data produk tidak ditemukan.'];
        }

        // Safety check: verify if product is used in production, recipes, delivery, or invoice
        $hasRecipes = $product->recipes()->exists();
        $hasBatches = $product->productionBatches()->exists();
        $hasDeliveries = DeliveryItem::where('product_id', $product->id)->exists();
        $hasInvoices = InvoiceItem::where('product_id', $product->id)->exists();

        if ($hasRecipes || $hasBatches || $hasDeliveries || $hasInvoices) {
            return [
                'success' => false,
                'message' => "Produk '{$product->name}' tidak dapat dihapus karena sudah memiliki data formula resep, riwayat produksi, surat jalan, atau faktur tagihan. Silakan nonaktifkan status produk sebagai gantinya.",
            ];
        }

        $name = $product->name;
        $product->delete();

        return [
            'success' => true,
            'message' => "Produk '{$name}' berhasil dihapus.",
        ];
    }

    /**
     * Perform Stock Opname (Physical Inventory Adjustment) for a finished product.
     */
    public function adjustProductStock(int $id, int $physicalStock, string $reason, string $date): array
    {
        if (Gate::denies('produk-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menyesuaikan stok produk.'];
        }

        $product = Product::find($id);
        if (! $product) {
            return ['success' => false, 'message' => 'Data produk tidak ditemukan.'];
        }

        $currentStock = (int) $product->stock_ready;
        $diff = $physicalStock - $currentStock;

        if ($diff == 0) {
            return ['success' => false, 'message' => 'Stok fisik sama dengan stok sistem (tidak ada selisih).'];
        }

        DB::transaction(function () use ($product, $physicalStock, $diff, $reason, $date) {
            $cost = (float) $product->material_cost;
            if ($cost <= 0) {
                $cost = (float) $product->consignment_price;
            }

            // 1. Update stock
            $product->stock_ready = $physicalStock;
            $product->save();

            // 2. Record accounting journal
            AccountingService::recordStockAdjustment(
                $product,
                (float) $diff,
                $cost,
                $reason ?: 'Stock Opname Produk Jadi',
                $date ?: now()->toDateString()
            );
        });

        $diffStr = ($diff > 0 ? '+' : '').$diff.' '.($product->unitModel?->short_name ?? $product->unit ?? 'pcs');

        return [
            'success' => true,
            'message' => "Stok produk '{$product->name}' berhasil disesuaikan ({$diffStr}) dan diposting ke Jurnal Akuntansi.",
        ];
    }
};
