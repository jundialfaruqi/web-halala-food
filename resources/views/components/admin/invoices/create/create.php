<?php

use App\Events\InvoiceCreated;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Buat Faktur Tagihan Baru - Halala Food')] class extends Component
{
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

    public function mount()
    {
        if (Gate::denies('faktur-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat faktur tagihan.');
        }

        $this->invoice_number = Invoice::generateInvoiceNumber();
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(14)->toDateString();

        $deliveryParam = request()->query('delivery');
        if ($deliveryParam) {
            $delivery = Delivery::with(['store', 'items.product', 'invoice'])->find($deliveryParam);
            if ($delivery) {
                $activeInvoice = $delivery->invoice()->where('status', '!=', 'dibatalkan')->first();
                if ($activeInvoice) {
                    session()->flash('toast', [
                        'message' => "Surat jalan {$delivery->delivery_number} sudah memiliki faktur ({$activeInvoice->invoice_number}).",
                        'type' => 'info',
                    ]);

                    return redirect()->route('admin.invoices.show', $activeInvoice);
                }

                $this->delivery_id = $delivery->id;
                $this->store_id = $delivery->store_id;
                $this->populateFromDelivery($delivery);

                return;
            }
        }

        $firstProduct = Product::where('is_active', true)->first();
        $this->items = [
            [
                'product_id' => $firstProduct?->id ?? '',
                'quantity' => 1,
                'unit_price' => (float) ($firstProduct?->consignment_price ?? 0),
                'subtotal' => (float) ($firstProduct?->consignment_price ?? 0),
            ],
        ];
    }

    public function updatedDeliveryId(?int $val): void
    {
        if ($val) {
            $delivery = Delivery::with(['store', 'items.product'])->find($val);
            if ($delivery) {
                $this->store_id = $delivery->store_id;
                $this->populateFromDelivery($delivery);
            }
        }
    }

    protected function populateFromDelivery(Delivery $delivery): void
    {
        $this->courier_id = $delivery->courier_id;
        $this->items = [];
        foreach ($delivery->items as $item) {
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
        if (Gate::denies('faktur-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat faktur tagihan.');
        }

        $this->validate([
            'invoice_number' => ['required', 'string', 'max:50', 'unique:invoices,invoice_number'],
            'store_id' => ['required', 'exists:stores,id'],
            'delivery_id' => [
                'nullable',
                'exists:deliveries,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $hasInvoice = Invoice::where('delivery_id', $value)
                            ->where('status', '!=', 'dibatalkan')
                            ->exists();
                        if ($hasInvoice) {
                            $fail('Surat jalan ini sudah memiliki faktur tagihan.');
                        }
                    }
                },
            ],
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

        $invoice = DB::transaction(function () {
            $subtotal = $this->subtotal;
            $discount = (float) $this->discount;
            $totalAmount = max(0.0, $subtotal - $discount);

            $invoice = Invoice::create([
                'invoice_number' => $this->invoice_number,
                'delivery_id' => $this->delivery_id,
                'store_id' => $this->store_id,
                'courier_id' => $this->courier_id,
                'created_by' => Auth::id(),
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'remaining_balance' => $totalAmount,
                'status' => 'belum_dibayar',
                'notes' => $this->notes,
            ]);

            foreach ($this->items as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $delivered = isset($item['delivered_quantity']) && $item['delivered_quantity'] !== '' ? (int) $item['delivered_quantity'] : $qty;
                $remaining = (int) ($item['remaining_quantity'] ?? 0);
                $damaged = (int) ($item['damaged_quantity'] ?? 0);
                $returned = (int) ($item['returned_quantity'] ?? 0);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'delivered_quantity' => $delivered,
                    'remaining_quantity' => $remaining,
                    'damaged_quantity' => $damaged,
                    'returned_quantity' => $returned,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $qty * $price,
                ]);

                // Increment warehouse stock for returned goods that were brought back in good condition
                if ($returned > 0) {
                    Product::where('id', $item['product_id'])->increment('stock_ready', $returned);
                }
            }

            return $invoice;
        });

        AccountingService::syncInvoiceAccounting($invoice);

        $invoice->load(['store', 'courier', 'delivery']);

        try {
            event(new InvoiceCreated($invoice));
        } catch (Throwable $e) {
            Log::warning('Broadcast InvoiceCreated error: '.$e->getMessage());
        }

        session()->flash('toast', [
            'message' => "Faktur tagihan {$invoice->invoice_number} berhasil dibuat.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.invoices.show', $invoice);
    }

    public function with(): array
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        $deliveries = Delivery::with('store')
            ->where('status', '!=', 'dibatalkan')
            ->where(function ($query) {
                $query->whereDoesntHave('invoice', function ($q) {
                    $q->where('status', '!=', 'dibatalkan');
                });
                if ($this->delivery_id) {
                    $query->orWhere('id', $this->delivery_id);
                }
            })
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->take(30)
            ->get();

        $selectedStore = $this->store_id ? Store::find($this->store_id) : null;

        $couriers = User::role('kurir')->orderBy('name')->get();
        if ($couriers->isEmpty()) {
            $couriers = User::orderBy('name')->get();
        }

        return [
            'stores' => $stores,
            'products' => $products,
            'deliveries' => $deliveries,
            'selectedStore' => $selectedStore,
            'couriers' => $couriers,
        ];
    }
};
