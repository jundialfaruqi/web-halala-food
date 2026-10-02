<?php

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public Delivery $delivery;
    public string $delivery_number = '';
    public ?int $store_id = null;
    public ?int $courier_id = null;
    public string $delivery_date = '';
    public ?string $notes = '';

    /**
     * @var array<int, array{product_id: int|string, quantity: int|string, unit_price: float|int, stock_ready: int, subtotal: float|int}>
     */
    public array $items = [];

    public function mount(Delivery $delivery)
    {
        if (Gate::denies('pengantaran-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah surat jalan.');
        }

        if (! $delivery->canBeEdited()) {
            session()->flash('toast', [
                'message' => 'Surat jalan yang sedang dikirim atau telah selesai tidak dapat diedit.',
                'type' => 'error',
            ]);
            return redirect()->route('admin.deliveries.show', $delivery);
        }

        $this->delivery = $delivery->load(['items.product']);
        $this->delivery_number = $delivery->delivery_number;
        $this->store_id = $delivery->store_id;
        $this->courier_id = $delivery->courier_id;
        $this->delivery_date = $delivery->delivery_date?->format('Y-m-d') ?? now()->toDateString();
        $this->notes = $delivery->notes;

        $this->items = [];
        foreach ($delivery->items as $item) {
            $currentAvailable = (int) ($item->product?->stock_ready ?? 0);
            $this->items[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                // Total available includes what this delivery currently reserves
                'stock_ready' => $currentAvailable + $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ];
        }

        if (empty($this->items)) {
            $this->items[] = [
                'product_id' => '',
                'quantity' => 1,
                'unit_price' => 0,
                'stock_ready' => 0,
                'subtotal' => 0,
            ];
        }
    }

    public function title(): string
    {
        return "Edit Surat Jalan {$this->delivery->delivery_number} - Halala Food";
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'stock_ready' => 0,
            'subtotal' => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function updatedItems($value, $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            if ($field === 'product_id') {
                $productId = $this->items[$index]['product_id'] ?? null;
                if ($productId) {
                    $product = Product::find($productId);
                    if ($product) {
                        $this->items[$index]['unit_price'] = (float) $product->consignment_price;
                        // Check if previously reserved in this delivery
                        $prevItem = $this->delivery->items->firstWhere('product_id', $productId);
                        $reservedQty = $prevItem ? $prevItem->quantity : 0;
                        $this->items[$index]['stock_ready'] = (int) $product->stock_ready + $reservedQty;
                        $this->calculateSubtotal($index);
                    }
                }
            } elseif ($field === 'quantity' || $field === 'unit_price') {
                $this->calculateSubtotal($index);
            }
        }
    }

    public function calculateSubtotal(int $index): void
    {
        if (isset($this->items[$index])) {
            $qty = max(1, (int) ($this->items[$index]['quantity'] ?? 1));
            $price = (float) ($this->items[$index]['unit_price'] ?? 0);
            $this->items[$index]['subtotal'] = $qty * $price;
        }
    }

    public function getTotalQuantityProperty(): int
    {
        return (int) collect($this->items)->sum(fn ($i) => (int) ($i['quantity'] ?? 0));
    }

    public function getTotalAmountProperty(): float
    {
        return (float) collect($this->items)->sum(fn ($i) => (float) ($i['subtotal'] ?? 0));
    }

    public function save()
    {
        if (Gate::denies('pengantaran-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah surat jalan.');
        }

        if (! $this->delivery->canBeEdited()) {
            session()->flash('toast', [
                'message' => 'Surat jalan tidak dapat diedit karena sudah dalam perjalanan atau selesai.',
                'type' => 'error',
            ]);
            return redirect()->route('admin.deliveries.show', $this->delivery);
        }

        $this->validate([
            'delivery_number' => ['required', 'string', 'max:50', 'unique:deliveries,delivery_number,' . $this->delivery->id],
            'store_id' => ['required', 'exists:stores,id'],
            'courier_id' => ['nullable', 'exists:users,id'],
            'delivery_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'store_id.required' => 'Pilih toko mitra tujuan pengantaran.',
            'delivery_date.required' => 'Tanggal pengantaran wajib diisi.',
            'items.*.product_id.required' => 'Pilih produk untuk setiap baris.',
            'items.*.product_id.distinct' => 'Produk tidak boleh ganda pada surat jalan yang sama.',
            'items.*.quantity.min' => 'Jumlah barang minimal 1.',
        ]);

        // Validate stock sufficiency for each item considering currently reserved stock
        $hasStockError = false;
        foreach ($this->items as $i => $item) {
            $product = Product::find($item['product_id']);
            $qty = (int) $item['quantity'];

            $prevItem = $this->delivery->items->firstWhere('product_id', $item['product_id']);
            $currentAvailable = (int) ($product?->stock_ready ?? 0) + ($prevItem ? $prevItem->quantity : 0);

            if ($product && $qty > $currentAvailable) {
                $this->addError("items.{$i}.quantity", "Stok tidak mencukupi (tersedia: {$currentAvailable} {$product->unit}).");
                $hasStockError = true;
            }
        }

        if ($hasStockError) {
            return;
        }

        DB::transaction(function () {
            // 1. Restore previous reserved stock
            foreach ($this->delivery->items as $oldItem) {
                Product::where('id', $oldItem->product_id)->increment('stock_ready', $oldItem->quantity);
            }

            // 2. Delete old items
            $this->delivery->items()->delete();

            // 3. Insert new items and decrement stock
            foreach ($this->items as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];

                DeliveryItem::create([
                    'delivery_id' => $this->delivery->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $qty * $price,
                ]);

                Product::where('id', $item['product_id'])->decrement('stock_ready', $qty);
            }

            // 4. Update delivery metadata
            $this->delivery->update([
                'delivery_number' => $this->delivery_number,
                'store_id' => $this->store_id,
                'courier_id' => $this->courier_id,
                'delivery_date' => $this->delivery_date,
                'notes' => $this->notes,
                'total_items' => $this->totalQuantity,
                'total_amount' => $this->totalAmount,
            ]);
        });

        session()->flash('toast', [
            'message' => "Surat jalan {$this->delivery->delivery_number} berhasil diperbarui.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.deliveries.show', $this->delivery);
    }

    public function with(): array
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $couriers = User::role('kurir')->orderBy('name')->get();

        if ($couriers->isEmpty()) {
            $couriers = User::orderBy('name')->get();
        }

        $selectedStore = $this->store_id ? Store::find($this->store_id) : null;

        return [
            'stores' => $stores,
            'products' => $products,
            'couriers' => $couriers,
            'selectedStore' => $selectedStore,
        ];
    }
};
