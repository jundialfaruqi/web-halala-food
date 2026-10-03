<?php

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseItem;
use App\Models\StockMutation;
use Illuminate\Support\Facades\Auth;
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

    public ?int $account_id = null;

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
        $this->setDefaultAccount();
        $this->addItem();
    }

    public function updatedPaymentMethod(): void
    {
        $this->setDefaultAccount();
    }

    protected function setDefaultAccount(): void
    {
        if ($this->payment_method === 'tunai') {
            $this->account_id = Account::where('name', 'like', '%kas%')->orWhere('name', 'like', '%tunai%')->first()?->id ?? Account::first()?->id;
        } elseif ($this->payment_method === 'transfer_bank') {
            $this->account_id = Account::where('name', 'like', '%bank%')->orWhere('name', 'like', '%bca%')->orWhere('name', 'like', '%bri%')->orWhere('name', 'like', '%mandiri%')->first()?->id ?? Account::first()?->id;
        } else {
            $this->account_id = null;
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'raw_material_id' => '',
            'input_mode' => 'package',
            'package_count' => '',
            'content_per_package' => '',
            'price_per_package' => '',
            'quantity' => '',
            'cost_per_unit' => '',
            'subtotal' => '',
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

    public function toggleInputMode(int $index, string $mode): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['input_mode'] = $mode;
            if ($mode === 'package') {
                $this->recalculatePackageRow($index);
            } else {
                $this->recalculateDirectRow($index);
            }
        }
    }

    protected function recalculatePackageRow(int $index, ?string $changedField = null): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $rawPkgCount = $this->items[$index]['package_count'] ?? '';
        $rawContent = $this->items[$index]['content_per_package'] ?? '';
        $rawPrice = $this->items[$index]['price_per_package'] ?? '';
        $rawSubtotal = $this->items[$index]['subtotal'] ?? '';

        $pkgCount = (float) $rawPkgCount;
        $content = (float) $rawContent;

        // 1. Hitung total stok fisik (quantity dalam base unit)
        if ($pkgCount > 0 && $content > 0) {
            $totalQty = round($pkgCount * $content, 4);
            $this->items[$index]['quantity'] = $totalQty;
        } elseif ($pkgCount > 0 && empty($rawContent)) {
            $totalQty = $pkgCount;
            $this->items[$index]['quantity'] = $pkgCount;
        } else {
            $totalQty = 0.0;
            if ($rawPkgCount === '' || $pkgCount <= 0) {
                $this->items[$index]['quantity'] = '';
            }
        }

        $totalQty = (float) ($this->items[$index]['quantity'] ?? 0);

        // 2. Kalkulasi harga dua arah (bi-directional)
        if ($changedField === 'price_per_package') {
            if ($rawPrice === '' || $rawPrice === null) {
                // Pengguna mengosongkan harga per kemasan -> reset subtotal dan HPP
                $this->items[$index]['price_per_package'] = '';
                $this->items[$index]['subtotal'] = '';
                $this->items[$index]['cost_per_unit'] = '';
            } else {
                $pricePerPkg = (float) $rawPrice;
                if ($pkgCount > 0) {
                    $subtotal = round($pkgCount * $pricePerPkg, 2);
                    $this->items[$index]['subtotal'] = $subtotal;
                    if ($totalQty > 0) {
                        $this->items[$index]['cost_per_unit'] = round($subtotal / $totalQty, 4);
                    } else {
                        $this->items[$index]['cost_per_unit'] = '';
                    }
                } else {
                    $this->items[$index]['subtotal'] = '';
                    $this->items[$index]['cost_per_unit'] = '';
                }
            }
        } elseif ($changedField === 'subtotal') {
            if ($rawSubtotal === '' || $rawSubtotal === null) {
                // Pengguna mengosongkan total belanja -> reset harga per kemasan dan HPP
                $this->items[$index]['subtotal'] = '';
                $this->items[$index]['price_per_package'] = '';
                $this->items[$index]['cost_per_unit'] = '';
            } else {
                $subtotal = (float) $rawSubtotal;
                if ($pkgCount > 0) {
                    $this->items[$index]['price_per_package'] = round($subtotal / $pkgCount, 2);
                } else {
                    $this->items[$index]['price_per_package'] = '';
                }
                if ($totalQty > 0) {
                    $this->items[$index]['cost_per_unit'] = round($subtotal / $totalQty, 4);
                } else {
                    $this->items[$index]['cost_per_unit'] = '';
                }
            }
        } else {
            // Field lain yang berubah: package_count atau content_per_package
            $pricePerPkg = (float) $rawPrice;
            $subtotal = (float) $rawSubtotal;

            if ($rawPrice !== '' && $pricePerPkg > 0 && $pkgCount > 0) {
                $subtotal = round($pkgCount * $pricePerPkg, 2);
                $this->items[$index]['subtotal'] = $subtotal;
                if ($totalQty > 0) {
                    $this->items[$index]['cost_per_unit'] = round($subtotal / $totalQty, 4);
                }
            } elseif ($rawSubtotal !== '' && $subtotal > 0 && $pkgCount > 0) {
                $this->items[$index]['price_per_package'] = round($subtotal / $pkgCount, 2);
                if ($totalQty > 0) {
                    $this->items[$index]['cost_per_unit'] = round($subtotal / $totalQty, 4);
                }
            } elseif ($pkgCount <= 0) {
                $this->items[$index]['cost_per_unit'] = '';
            }
        }
    }

    protected function recalculateDirectRow(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $qty = (float) ($this->items[$index]['quantity'] ?? 0);
        $cost = (float) ($this->items[$index]['cost_per_unit'] ?? 0);
        $this->items[$index]['subtotal'] = round($qty * $cost, 2);
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

            $mode = $this->items[$index]['input_mode'] ?? 'package';

            if ($field === 'raw_material_id' && ! empty($this->items[$index]['raw_material_id'])) {
                $material = RawMaterial::find($this->items[$index]['raw_material_id']);
                if ($material && empty($this->items[$index]['cost_per_unit']) && empty($this->items[$index]['price_per_package'])) {
                    $this->items[$index]['cost_per_unit'] = (float) $material->cost_per_unit;
                }
            }

            if ($mode === 'package') {
                $this->recalculatePackageRow($index, $field);
            } else {
                $this->recalculateDirectRow($index);
            }
        }
    }

    public function getTotalAmountProperty(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += (float) ($item['subtotal'] ?? 0);
        }

        return round($total, 2);
    }

    public function save(): mixed
    {
        if (Gate::denies('pembelian-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat pembelian bahan baku.');
        }

        $rules = [
            'purchase_number' => ['required', 'string', 'max:50', 'unique:raw_material_purchases,purchase_number'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:tunai,transfer_bank,tempo'],
            'account_id' => [$this->payment_method !== 'tempo' ? 'required' : 'nullable', 'nullable', 'exists:accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.raw_material_id' => ['required', 'exists:raw_materials,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.cost_per_unit' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];

        $messages = [
            'purchase_number.required' => 'Nomor transaksi pembelian wajib diisi.',
            'purchase_number.unique' => 'Nomor transaksi pembelian sudah pernah digunakan.',
            'supplier_name.required' => 'Nama supplier / toko penyedia wajib diisi.',
            'purchase_date.required' => 'Tanggal pembelian wajib dipilih.',
            'account_id.required' => 'Pilih akun kas atau rekening pembayaran.',
            'account_id.exists' => 'Akun kas atau rekening yang dipilih tidak valid.',
            'items.min' => 'Minimal masukkan 1 baris bahan baku.',
            'items.*.raw_material_id.required' => 'Pilih bahan baku untuk tiap baris.',
            'items.*.quantity.gt' => 'Jumlah kuantitas harus lebih dari 0.',
            'items.*.cost_per_unit.min' => 'Harga satuan tidak boleh negatif.',
        ];

        $this->validate($rules, $messages);

        DB::transaction(function () {
            $total = $this->totalAmount;

            $purchase = RawMaterialPurchase::create([
                'purchase_number' => $this->purchase_number,
                'supplier_name' => trim($this->supplier_name),
                'purchase_date' => $this->purchase_date,
                'total_amount' => $total,
                'payment_method' => $this->payment_method,
                'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
                'created_by' => Auth::id(),
            ]);

            $itemDetails = [];
            foreach ($this->items as $item) {
                $rawMat = RawMaterial::lockForUpdate()->with('unitModel')->find($item['raw_material_id']);
                if (! $rawMat) {
                    continue;
                }

                $qty = (float) $item['quantity'];
                $cost = (float) $item['cost_per_unit'];
                $subtotal = (float) ($item['subtotal'] ?? round($qty * $cost, 2));

                $unitStr = $rawMat->unitModel?->short_name ?? $rawMat->display_unit ?? '';
                $formattedQty = number_format($qty, (floor($qty) == $qty ? 0 : 2), ',', '.');
                $itemDetails[] = "{$rawMat->name} ({$formattedQty} {$unitStr})";

                $notes = ! empty($item['notes']) ? trim($item['notes']) : null;
                if (! $notes && ($item['input_mode'] ?? '') === 'package' && ! empty($item['package_count'])) {
                    $pkgCount = $item['package_count'];
                    $content = $item['content_per_package'] ?? '';
                    $notes = $content ? "{$pkgCount} kemasan (@ {$content} {$unitStr})" : "{$pkgCount} kemasan";
                }

                RawMaterialPurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'raw_material_id' => $rawMat->id,
                    'quantity' => $qty,
                    'cost_per_unit' => $cost,
                    'subtotal' => $subtotal,
                    'notes' => $notes,
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
                    'user_id' => Auth::id(),
                ]);

                // Update stock and update cost_per_unit to latest purchase price
                $rawMat->stock = $stockAfter;
                if ($cost > 0) {
                    $rawMat->cost_per_unit = $cost;
                }
                $rawMat->save();
            }

            // Catat pengeluaran di Buku Kas jika pembayaran tunai atau transfer bank
            if ($this->payment_method !== 'tempo' && $total > 0) {
                $account = Account::find($this->account_id) ?? Account::first();
                if ($account) {
                    $account->decrement('balance', $total);

                    $itemsString = implode(', ', $itemDetails);
                    $description = "Pembelian Bahan Baku ({$purchase->purchase_number}): " . ($itemsString ?: 'Item bahan') . " - Supplier: {$purchase->supplier_name}";

                    CashTransaction::create([
                        'transaction_date' => $this->purchase_date,
                        'account_id' => $account->id,
                        'type' => 'expense',
                        'category' => 'Belanja Bahan Baku',
                        'amount' => $total,
                        'reference_type' => 'purchase',
                        'reference_id' => $purchase->id,
                        'description' => $description,
                    ]);
                }
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
            'accounts' => Account::orderBy('name')->get(),
        ];
    }
};
