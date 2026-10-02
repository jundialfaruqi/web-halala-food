<?php

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Buat Surat Jalan Baru - Halala Food')] class extends Component
{
    public string $delivery_number = '';
    public ?int $store_id = null;
    public ?int $courier_id = null;
    public string $delivery_date = '';
    public ?string $notes = '';

    /**
     * @var array<int, array{product_id: int|string, quantity: int|string, unit_price: float|int, stock_ready: int, subtotal: float|int}>
     */
    public array $items = [];

    public function mount()
    {
        if (Gate::denies('pengantaran-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat surat jalan.');
        }

        $this->delivery_number = Delivery::generateDeliveryNumber();
        $this->delivery_date = now()->toDateString();

        // Assign courier automatically if current user has courier role
        if (Auth::user()?->hasRole('kurir')) {
            $this->courier_id = Auth::id();
        }

        $firstProduct = Product::where('is_active', true)->where('stock_ready', '>', 0)->first()
            ?? Product::where('is_active', true)->first();

        $this->items = [
            [
                'product_id' => $firstProduct?->id ?? '',
                'quantity' => 1,
                'unit_price' => (float) ($firstProduct?->consignment_price ?? 0),
                'stock_ready' => (int) ($firstProduct?->stock_ready ?? 0),
                'subtotal' => (float) ($firstProduct?->consignment_price ?? 0),
            ],
        ];
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
                        $this->items[$index]['stock_ready'] = (int) $product->stock_ready;
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
        if (Gate::denies('pengantaran-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat surat jalan.');
        }

        $this->validate([
            'delivery_number' => ['required', 'string', 'max:50', 'unique:deliveries,delivery_number'],
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

        // Validate stock sufficiency for each item
        $hasStockError = false;
        foreach ($this->items as $i => $item) {
            $product = Product::find($item['product_id']);
            $qty = (int) $item['quantity'];
            if ($product && $qty > $product->stock_ready) {
                $this->addError("items.{$i}.quantity", "Stok tidak mencukupi (sisa ready di gudang: {$product->stock_ready} {$product->unit}).");
                $hasStockError = true;
            }
        }

        if ($hasStockError) {
            return;
        }

        $delivery = DB::transaction(function () {
            $delivery = Delivery::create([
                'delivery_number' => $this->delivery_number,
                'store_id' => $this->store_id,
                'courier_id' => $this->courier_id,
                'created_by' => Auth::id(),
                'delivery_date' => $this->delivery_date,
                'status' => 'diproses',
                'notes' => $this->notes,
                'total_items' => $this->totalQuantity,
                'total_amount' => $this->totalAmount,
            ]);

            foreach ($this->items as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];

                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $qty * $price,
                ]);

                // Decrement ready stock from warehouse inventory
                Product::where('id', $item['product_id'])->decrement('stock_ready', $qty);
            }

            return $delivery;
        });

        session()->flash('toast', [
            'message' => "Surat jalan {$delivery->delivery_number} berhasil dibuat.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.deliveries.show', $delivery);
    }

    public function with(): array
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $couriers = User::role('kurir')->orderBy('name')->get();

        // If no user has role kurir, fallback to all users
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
