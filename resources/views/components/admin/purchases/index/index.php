<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\StockMutation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] #[Title('Pengadaan & Pembelian Bahan Baku - Halala Food')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $paymentMethod = '';

    #[Url]
    public string $paymentStatus = '';

    public ?int $viewingPurchaseId = null;

    // Debt Payment Modal State
    public bool $showPaymentModal = false;
    public ?int $payingPurchaseId = null;
    public ?int $paymentAccountId = null;
    public string $paymentDate = '';
    public int|float|string $paymentAmount = 0;
    public string $paymentNotes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function openPaymentModal(int $id): void
    {
        $purchase = RawMaterialPurchase::findOrFail($id);
        $this->payingPurchaseId = $id;
        $this->paymentDate = now()->toDateString();
        $this->paymentAmount = $purchase->remaining_debt;
        $this->paymentAccountId = \App\Models\Account::first()?->id;
        $this->paymentNotes = "Pelunasan Hutang {$purchase->purchase_number} ke {$purchase->supplier_name}";
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->payingPurchaseId = null;
    }

    public function saveDebtPayment(): void
    {
        if (Gate::denies('pembelian-edit') && Gate::denies('pembelian-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat pembayaran hutang.');
        }

        if (is_string($this->paymentAmount)) {
            $cleaned = preg_replace('/[^0-9]/', '', $this->paymentAmount);
            $this->paymentAmount = $cleaned !== '' ? (float) $cleaned : 0.0;
        }

        $purchase = RawMaterialPurchase::findOrFail($this->payingPurchaseId);
        $remaining = (float) $purchase->remaining_debt;

        $this->validate([
            'paymentAccountId' => ['required', 'exists:accounts,id'],
            'paymentDate' => ['required', 'date'],
            'paymentAmount' => ['required', 'numeric', 'min:1', "max:{$remaining}"],
            'paymentNotes' => ['nullable', 'string', 'max:500'],
        ], [
            'paymentAccountId.required' => 'Pilih akun kas / bank pembayaran.',
            'paymentAccountId.exists' => 'Akun pembayaran tidak valid.',
            'paymentDate.required' => 'Tanggal pembayaran wajib diisi.',
            'paymentAmount.required' => 'Nominal pembayaran wajib diisi.',
            'paymentAmount.min' => 'Nominal pembayaran minimal Rp 1.',
            'paymentAmount.max' => 'Nominal pembayaran melebihi sisa hutang (Rp ' . number_format($remaining, 0, ',', '.') . ').',
        ]);

        $account = \App\Models\Account::findOrFail($this->paymentAccountId);

        DB::transaction(function () use ($purchase, $account) {
            \App\Services\AccountingService::recordPurchaseDebtPayment(
                $purchase,
                $account,
                (float) $this->paymentAmount,
                $this->paymentDate,
                $this->paymentNotes
            );
        });

        $this->showPaymentModal = false;
        $this->payingPurchaseId = null;

        session()->flash('success', "Pembayaran hutang pembelian {$purchase->purchase_number} sebesar Rp " . number_format((float) $this->paymentAmount, 0, ',', '.') . " berhasil dicatat dan diposting ke Buku Kas & Jurnal.");
    }

    public function viewDetails(int $id): void
    {
        $this->viewingPurchaseId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingPurchaseId = null;
    }

    public function deletePurchase(int $id): void
    {
        if (Gate::denies('pembelian-delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membatalkan pembelian bahan baku.');
        }

        $purchase = RawMaterialPurchase::with('items.rawMaterial')->findOrFail($id);

        // Check if rollback will cause negative stock
        foreach ($purchase->items as $item) {
            $currentStock = (float) $item->rawMaterial->stock;
            if ($currentStock < (float) $item->quantity) {
                session()->flash('error', "Pembelian {$purchase->purchase_number} tidak dapat dihapus karena sisa stok '{$item->rawMaterial->name}' ({$currentStock}) lebih kecil dari jumlah yang dibeli ({$item->quantity}). Sebagian bahan telah terpakai dalam proses masak dapur.");
                return;
            }
        }

        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $rawMat = $item->rawMaterial;
                $stockBefore = (float) $rawMat->stock;
                $stockAfter = $stockBefore - (float) $item->quantity;

                // Create reverse mutation
                StockMutation::create([
                    'raw_material_id' => $rawMat->id,
                    'reference_type' => 'purchase_reversal',
                    'reference_id' => $purchase->id,
                    'reference_number' => $purchase->purchase_number,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'cost_per_unit' => $item->cost_per_unit,
                    'notes' => "Pembatalan pembelian {$purchase->purchase_number}",
                    'user_id' => Auth::id(),
                ]);

                $rawMat->decrement('stock', $item->quantity);
            }

            // Revert and delete associated cash transaction if exists
            $cashTx = \App\Models\CashTransaction::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->first();
            if ($cashTx) {
                $cashTx->account?->increment('balance', $cashTx->amount);
                $cashTx->delete();
            }

            // Revert and delete associated debt payment cash transactions if exist
            $debtCashTxs = \App\Models\CashTransaction::where('reference_type', 'purchase_payment')
                ->where('reference_id', $purchase->id)
                ->get();
            foreach ($debtCashTxs as $dtx) {
                $dtx->account?->increment('balance', $dtx->amount);
                $dtx->delete();
            }

            // Remove associated original stock mutations
            StockMutation::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->delete();

            // Remove associated journal entries
            \App\Models\JournalEntry::whereIn('reference_type', ['purchase', 'purchase_payment'])
                ->where('reference_id', $purchase->id)
                ->delete();

            $purchase->items()->delete();
            $purchase->delete();
        });

        if ($this->viewingPurchaseId === $id) {
            $this->viewingPurchaseId = null;
        }

        session()->flash('success', "Transaksi pembelian {$purchase->purchase_number} berhasil dibatalkan dan stok bahan dikembalikan.");
    }

    public function with(): array
    {
        $query = RawMaterialPurchase::query()
            ->with(['items.rawMaterial', 'creator', 'paidAccount'])
            ->latest('purchase_date')
            ->latest('id');

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('purchase_number', 'like', $term)
                    ->orWhere('supplier_name', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('items.rawMaterial', function ($mq) use ($term) {
                        $mq->where('name', 'like', $term);
                    });
            });
        }

        if ($this->paymentMethod !== '') {
            $query->where('payment_method', $this->paymentMethod);
        }

        if ($this->paymentStatus !== '') {
            $query->where('payment_status', $this->paymentStatus);
        }

        $purchases = $query->paginate(15);

        // Stats summary for current month
        $thisMonth = now()->startOfMonth();
        $monthlyTotalAmount = (float) RawMaterialPurchase::where('purchase_date', '>=', $thisMonth)->sum('total_amount');
        $monthlyTotalCount = RawMaterialPurchase::where('purchase_date', '>=', $thisMonth)->count();

        $totalUnpaidDebt = (float) RawMaterialPurchase::where('payment_method', 'tempo')
            ->where('payment_status', 'belum_lunas')
            ->get()
            ->sum(fn ($p) => $p->remaining_debt);

        $activePurchase = null;
        if ($this->viewingPurchaseId) {
            $activePurchase = RawMaterialPurchase::with(['items.rawMaterial.unitModel', 'creator', 'paidAccount'])->find($this->viewingPurchaseId);
        }

        $payingPurchase = null;
        if ($this->payingPurchaseId) {
            $payingPurchase = RawMaterialPurchase::find($this->payingPurchaseId);
        }

        $accounts = \App\Models\Account::orderBy('name')->get();

        return [
            'purchases' => $purchases,
            'monthlyTotalAmount' => $monthlyTotalAmount,
            'monthlyTotalCount' => $monthlyTotalCount,
            'totalUnpaidDebt' => $totalUnpaidDebt,
            'activePurchase' => $activePurchase,
            'payingPurchase' => $payingPurchase,
            'accounts' => $accounts,
        ];
    }
};
