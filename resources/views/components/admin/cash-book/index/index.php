<?php

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] #[Title('Buku Kas & Keuangan - Halala Food')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $accountFilter = '';

    #[Url]
    public string $dateRange = '';

    // Modals
    public bool $showTransactionModal = false;

    public bool $showConfirmTransactionModal = false;

    public bool $showAccountModal = false;

    public ?int $deletingTransactionId = null;

    // Transaction Form fields
    public string $transaction_date = '';

    public ?int $account_id = null;

    public string $type = 'expense'; // 'income', 'expense', 'prive'

    public string $category = '';

    public int|float|string $amount = 0;

    public string $description = '';

    // Account Form fields
    public string $account_name = '';

    public string $account_type = 'business';

    public int|float|string $initial_balance = 0;

    public string $account_description = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAccountFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateRange(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'accountFilter', 'dateRange']);
        $this->resetPage();
    }

    public function setQuickDate(string $period): void
    {
        if ($period === 'today') {
            $today = Carbon::today()->format('Y-m-d');
            $this->dateRange = $today.' - '.$today;
        } elseif ($period === 'this_month') {
            $start = Carbon::now()->startOfMonth()->format('Y-m-d');
            $end = Carbon::now()->endOfMonth()->format('Y-m-d');
            $this->dateRange = $start.' - '.$end;
        } elseif ($period === 'all') {
            $this->dateRange = '';
        }
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->transaction_date = Carbon::now()->format('Y-m-d');
        $firstAccount = Account::first();
        $this->account_id = $firstAccount?->id;
    }

    public function openAccountModal(): void
    {
        if (Gate::denies('buku-kas-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah akun kas.');
        }

        $this->reset(['account_name', 'account_type', 'initial_balance', 'account_description']);
        $this->account_type = 'business';
        $this->showAccountModal = true;
    }

    public function saveAccount(): void
    {
        if (Gate::denies('buku-kas-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah akun kas.');
        }

        if (is_string($this->initial_balance)) {
            $cleaned = preg_replace('/[^0-9]/', '', $this->initial_balance);
            $this->initial_balance = $cleaned !== '' ? (float) $cleaned : 0.0;
        }

        $this->validate([
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:business,personal',
            'initial_balance' => 'required|numeric|min:0',
            'account_description' => 'nullable|string|max:500',
        ], [
            'account_name.required' => 'Nama akun kas / rekening wajib diisi.',
            'account_name.max' => 'Nama akun kas maksimal 255 karakter.',
            'account_type.required' => 'Jenis akun kas wajib dipilih.',
            'account_type.in' => 'Pilihan jenis akun kas tidak valid.',
            'initial_balance.required' => 'Saldo awal wajib diisi (bisa 0).',
            'initial_balance.numeric' => 'Saldo awal harus berupa angka.',
            'initial_balance.min' => 'Saldo awal tidak boleh kurang dari 0.',
        ]);

        Account::create([
            'name' => $this->account_name,
            'type' => $this->account_type,
            'balance' => $this->initial_balance,
            'description' => $this->account_description ?: null,
        ]);

        $this->showAccountModal = false;
        session()->flash('success', "Akun kas '{$this->account_name}' berhasil ditambahkan.");
    }

    public function openTransactionModal(string $type = 'expense'): void
    {
        if (Gate::denies('buku-kas-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat transaksi kas.');
        }

        $this->transaction_date = Carbon::now()->format('Y-m-d');
        $this->type = in_array($type, ['income', 'expense', 'prive']) ? $type : 'expense';
        $this->category = $this->getDefaultCategory($this->type);
        $this->amount = 0;
        $this->description = '';
        $this->showConfirmTransactionModal = false;
        $this->showTransactionModal = true;
    }

    public function getDefaultCategory(string $type): string
    {
        return match ($type) {
            'income' => 'Setoran Modal',
            'expense' => 'Belanja Bahan Baku',
            'prive' => 'Prive',
            default => 'Operasional Lainnya',
        };
    }

    public function updatedType(string $value): void
    {
        $this->category = $this->getDefaultCategory($value);
    }

    public function prepareTransactionConfirmation(): void
    {
        if (is_string($this->amount)) {
            $cleaned = preg_replace('/[^0-9]/', '', $this->amount);
            $this->amount = $cleaned !== '' ? (float) $cleaned : 0.0;
        }

        $this->validate([
            'transaction_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense,prive',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ], [
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Format tanggal tidak valid.',
            'account_id.required' => 'Silakan pilih rekening atau kas.',
            'account_id.exists' => 'Akun kas tidak ditemukan.',
            'type.required' => 'Jenis transaksi wajib dipilih.',
            'category.required' => 'Kategori pos transaksi wajib diisi.',
            'amount.required' => 'Nominal transaksi wajib diisi.',
            'amount.min' => 'Nominal transaksi minimal Rp 1.',
        ]);

        $this->showConfirmTransactionModal = true;
    }

    public function saveTransaction(): void
    {
        if (Gate::denies('buku-kas-create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat transaksi kas.');
        }

        if (is_string($this->amount)) {
            $cleaned = preg_replace('/[^0-9]/', '', $this->amount);
            $this->amount = $cleaned !== '' ? (float) $cleaned : 0.0;
        }

        $this->validate([
            'transaction_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense,prive',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () {
            $account = Account::findOrFail($this->account_id);

            // Update balance
            if ($this->type === 'income') {
                $account->increment('balance', $this->amount);
            } else {
                $account->decrement('balance', $this->amount);
            }

            // Save transaction
            $tx = CashTransaction::create([
                'transaction_date' => $this->transaction_date,
                'account_id' => $this->account_id,
                'type' => $this->type,
                'category' => $this->category,
                'amount' => $this->amount,
                'description' => $this->description ?: null,
            ]);

            // Auto-journal in general ledger
            AccountingService::recordCashTransaction($tx);
        });

        $this->showConfirmTransactionModal = false;
        $this->showTransactionModal = false;
        session()->flash('success', 'Transaksi kas berhasil dicatat dan diposting ke jurnal akuntansi.');
    }

    public function confirmDeleteTransaction(int $id): void
    {
        if (Gate::denies('buku-kas-delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus transaksi kas.');
        }

        $this->deletingTransactionId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingTransactionId = null;
    }

    public function deleteTransaction(): void
    {
        if (Gate::denies('buku-kas-delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus transaksi kas.');
        }

        if (! $this->deletingTransactionId) {
            return;
        }

        $tx = CashTransaction::with('account')->findOrFail($this->deletingTransactionId);

        DB::transaction(function () use ($tx) {
            // Reverse account balance
            if ($tx->type === 'income') {
                $tx->account->decrement('balance', $tx->amount);
            } else {
                $tx->account->increment('balance', $tx->amount);
            }

            // Delete associated general journal entry
            JournalEntry::where('reference_type', 'cash_transaction')
                ->where('reference_id', $tx->id)
                ->delete();

            $tx->delete();
        });

        $this->deletingTransactionId = null;
        session()->flash('success', 'Catatan transaksi kas berhasil dibatalkan dan saldo dikembalikan.');
    }

    public function with(): array
    {
        $accounts = Account::orderBy('name')->get();

        $startDate = '';
        $endDate = '';
        if ($this->dateRange) {
            $dates = explode(' - ', $this->dateRange);
            $startDate = trim($dates[0] ?? '');
            $endDate = trim($dates[1] ?? $startDate);
        }

        $query = CashTransaction::with('account')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('category', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->typeFilter && $this->typeFilter !== 'all', function ($q) {
                $q->where('type', $this->typeFilter);
            })
            ->when($this->accountFilter, function ($q) {
                $q->where('account_id', $this->accountFilter);
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('transaction_date', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('transaction_date', '<=', $endDate);
            });

        // Filtered summary metrics
        $filteredIncome = (clone $query)->where('type', 'income')->sum('amount');
        $filteredExpense = (clone $query)->where('type', 'expense')->sum('amount');
        $filteredPrive = (clone $query)->where('type', 'prive')->sum('amount');

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $totalCashBalance = (float) $accounts->sum('balance');

        $hasActiveFilters = $this->search !== '' || $this->typeFilter !== 'all' || $this->accountFilter !== '' || $this->dateRange !== '';

        return [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'totalCashBalance' => $totalCashBalance,
            'filteredIncome' => $filteredIncome,
            'filteredExpense' => $filteredExpense,
            'filteredPrive' => $filteredPrive,
            'hasActiveFilters' => $hasActiveFilters,
        ];
    }
};
