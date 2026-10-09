<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Edit Faktur Tagihan - Halala Food')] class extends Component
{
    public Invoice $invoice;

    public string $invoice_number = '';

    public ?int $delivery_id = null;

    public ?int $store_id = null;

    public ?int $courier_id = null;

    public string $invoice_date = '';

    public string $due_date = '';

    public float $discount = 0.00;

    public ?string $notes = '';

    /**
     * @var array<int, array{product_id: int|string, delivered_quantity: int|string, remaining_quantity: int|string, damaged_quantity: int|string, returned_quantity: int|string, quantity: int|string, unit_price: float|int, subtotal: float|int}>
     */
    public array $items = [];

    public function mount(Invoice $invoice)
    {
        if (Gate::denies('faktur-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit faktur tagihan.');
        }

        if ($invoice->status === 'lunas' || $invoice->status === 'dibatalkan') {
            session()->flash('toast', [
                'message' => 'Faktur yang sudah lunas atau dibatalkan tidak dapat diedit.',
                'type' => 'error',
            ]);

            return redirect()->route('admin.invoices.show', $invoice);
        }

        $this->invoice = $invoice->load(['items.product', 'store']);
        $this->invoice_number = $invoice->invoice_number;
        $this->delivery_id = $invoice->delivery_id;
        $this->store_id = $invoice->store_id;
        $this->courier_id = $invoice->courier_id;
        $this->invoice_date = $invoice->invoice_date->toDateString();
        $this->due_date = $invoice->due_date->toDateString();
        $this->discount = (float) $invoice->discount;
        $this->notes = $invoice->notes;

        $this->items = [];
        foreach ($invoice->items as $item) {
            $this->items[] = [
                'product_id' => $item->product_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
            ];
        }

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
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

    public function updatedItems(mixed $value, ?string $key = null): void
    {
        if (! $key) {
            return;
        }

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
                        $this->calculateSubtotal($index);
                    }
                }
            } elseif (in_array($field, ['quantity', 'unit_price'])) {
                $this->calculateSubtotal($index);
            }
        }
    }

    public function calculateSubtotal(int $index): void
    {
        if (isset($this->items[$index])) {
            $qty = max(0, (int) ($this->items[$index]['quantity'] ?? 0));
            $price = (float) ($this->items[$index]['unit_price'] ?? 0);
            $this->items[$index]['subtotal'] = $qty * $price;
        }
    }

    public function getSubtotalProperty(): float
    {
        return (float) collect($this->items)->sum(fn ($i) => (float) ($i['subtotal'] ?? 0));
    }

    public function getTotalAmountProperty(): float
    {
        return max(0.0, $this->subtotal - (float) $this->discount);
    }

    public function save()
    {
        if (Gate::denies('faktur-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit faktur tagihan.');
        }

        $this->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'courier_id' => ['nullable', 'exists:users,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.delivered_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.remaining_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.returned_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'store_id.required' => 'Pilih toko mitra tujuan penagihan.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal faktur.',
            'items.*.product_id.required' => 'Pilih produk untuk setiap baris faktur.',
            'items.*.product_id.distinct' => 'Produk tidak boleh ganda pada faktur yang sama.',
        ]);

        $newTotalAmount = $this->totalAmount;
        if ($newTotalAmount < (float) $this->invoice->paid_amount) {
            $this->addError('discount', 'Total tagihan akhir (Rp '.number_format($newTotalAmount, 0, ',', '.').') tidak boleh lebih kecil dari pembayaran yang sudah diterima (Rp '.number_format($this->invoice->paid_amount, 0, ',', '.').').');

            return;
        }

        DB::transaction(function () {
            $subtotal = $this->subtotal;
            $discount = (float) $this->discount;
            $totalAmount = max(0.0, $subtotal - $discount);

            $this->invoice->update([
                'store_id' => $this->store_id,
                'courier_id' => $this->courier_id,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'notes' => $this->notes,
            ]);

            // Revert previously returned items from warehouse inventory before deleting
            foreach ($this->invoice->items as $oldItem) {
                if ($oldItem->returned_quantity > 0) {
                    Product::where('id', $oldItem->product_id)->decrement('stock_ready', $oldItem->returned_quantity);
                }
            }

            // Replace line items
            $this->invoice->items()->delete();
            foreach ($this->items as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $delivered = isset($item['delivered_quantity']) && $item['delivered_quantity'] !== '' ? (int) $item['delivered_quantity'] : $qty;
                $remaining = (int) ($item['remaining_quantity'] ?? 0);
                $damaged = (int) ($item['damaged_quantity'] ?? 0);
                $returned = (int) ($item['returned_quantity'] ?? 0);

                InvoiceItem::create([
                    'invoice_id' => $this->invoice->id,
                    'product_id' => $item['product_id'],
                    'delivered_quantity' => $delivered,
                    'remaining_quantity' => $remaining,
                    'damaged_quantity' => $damaged,
                    'returned_quantity' => $returned,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $qty * $price,
                ]);

                // Increment warehouse stock for newly returned items
                if ($returned > 0) {
                    Product::where('id', $item['product_id'])->increment('stock_ready', $returned);
                }
            }

            $this->invoice->recalculateStatusAndBalance();
            AccountingService::syncInvoiceAccounting($this->invoice);
        });

        session()->flash('toast', [
            'message' => "Faktur tagihan {$this->invoice->invoice_number} berhasil diperbarui.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.invoices.show', $this->invoice);
    }

    public function with(): array
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $selectedStore = $this->store_id ? Store::find($this->store_id) : null;

        $couriers = User::role('kurir')->orderBy('name')->get();
        if ($couriers->isEmpty()) {
            $couriers = User::orderBy('name')->get();
        }

        return [
            'stores' => $stores,
            'products' => $products,
            'selectedStore' => $selectedStore,
            'couriers' => $couriers,
        ];
    }
};
