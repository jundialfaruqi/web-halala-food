<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseItem;
use App\Models\StockMutation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Catat Pembelian Bahan Baku - Halala Food')] class extends Component
{
    public string $purchase_number = '';

    public string $supplier_name = '';

    public string $purchase_date = '';

    public string $payment_method = 'tunai';

    public string $notes = '';

    /**
     * @var array<int, array{raw_material_id: string|int, quantity: string|float, cost_per_unit: string|float, subtotal: float, notes: string}>
     */
    public array $items = [];

    public function mount(): void
    {
        if (Gate::denies('pembelian-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat pembelian bahan baku.');
        }

        $this->purchase_number = RawMaterialPurchase::generatePurchaseNumber();
        $this->purchase_date = now()->toDateString();
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'raw_material_id' => '',
            'quantity' => '',
            'cost_per_unit' => '',
            'subtotal' => 0.0,
            'notes' => '',
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

            if ($field === 'raw_material_id' && ! empty($this->items[$index]['raw_material_id'])) {
                $material = RawMaterial::find($this->items[$index]['raw_material_id']);
                if ($material && empty($this->items[$index]['cost_per_unit'])) {
                    $this->items[$index]['cost_per_unit'] = (float) $material->cost_per_unit;
                }
            }

            $qty = (float) ($this->items[$index]['quantity'] ?? 0);
            $cost = (float) ($this->items[$index]['cost_per_unit'] ?? 0);
            $this->items[$index]['subtotal'] = round($qty * $cost, 2);
        }
    }

    public function getTotalAmountProperty(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $cost = (float) ($item['cost_per_unit'] ?? 0);
            $total += ($qty * $cost);
        }

        return round($total, 2);
    }

    public function save(): mixed
    {
        if (Gate::denies('pembelian-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat pembelian bahan baku.');
        }

        $this->validate([
            'purchase_number' => ['required', 'string', 'max:50', 'unique:raw_material_purchases,purchase_number'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:tunai,transfer_bank,tempo'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.raw_material_id' => ['required', 'exists:raw_materials,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.cost_per_unit' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'purchase_number.required' => 'Nomor transaksi pembelian wajib diisi.',
            'purchase_number.unique' => 'Nomor transaksi pembelian sudah pernah digunakan.',
            'supplier_name.required' => 'Nama supplier / toko penyedia wajib diisi.',
            'purchase_date.required' => 'Tanggal pembelian wajib dipilih.',
            'items.min' => 'Minimal masukkan 1 baris bahan baku.',
            'items.*.raw_material_id.required' => 'Pilih bahan baku untuk tiap baris.',
            'items.*.quantity.gt' => 'Jumlah kuantitas harus lebih dari 0.',
            'items.*.cost_per_unit.min' => 'Harga satuan tidak boleh negatif.',
        ]);

        DB::transaction(function () {
            $total = $this->totalAmount;

            $purchase = RawMaterialPurchase::create([
                'purchase_number' => $this->purchase_number,
                'supplier_name' => trim($this->supplier_name),
                'purchase_date' => $this->purchase_date,
                'total_amount' => $total,
                'payment_method' => $this->payment_method,
                'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->items as $item) {
                $rawMat = RawMaterial::lockForUpdate()->find($item['raw_material_id']);
                if (! $rawMat) {
                    continue;
                }

                $qty = (float) $item['quantity'];
                $cost = (float) $item['cost_per_unit'];
                $subtotal = round($qty * $cost, 2);

                RawMaterialPurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'raw_material_id' => $rawMat->id,
                    'quantity' => $qty,
                    'cost_per_unit' => $cost,
                    'subtotal' => $subtotal,
                    'notes' => ! empty($item['notes']) ? trim($item['notes']) : null,
                ]);

                $stockBefore = (float) $rawMat->stock;
                $stockAfter = $stockBefore + $qty;

                StockMutation::create([
                    'raw_material_id' => $rawMat->id,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'reference_number' => $purchase->purchase_number,
                    'type' => 'in',
                    'quantity' => $qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'cost_per_unit' => $cost,
                    'notes' => 'Pembelian dari ' . $purchase->supplier_name,
                    'user_id' => auth()->id(),
                ]);

                // Update stock and update cost_per_unit to latest purchase price
                $rawMat->stock = $stockAfter;
                if ($cost > 0) {
                    $rawMat->cost_per_unit = $cost;
                }
                $rawMat->save();
            }

            // Catat ke Jurnal Akuntansi otomatis
            \App\Services\AccountingService::recordPurchase($purchase);
        });

        session()->flash('success', "Transaksi pembelian {$this->purchase_number} berhasil dicatat dan stok bahan otomatis diperbarui.");

        return redirect()->route('admin.purchases');
    }

    public function with(): array
    {
        return [
            'rawMaterials' => RawMaterial::with('unitModel')->orderBy('name')->get(),
        ];
    }
};
