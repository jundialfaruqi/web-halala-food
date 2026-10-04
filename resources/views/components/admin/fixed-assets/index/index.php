<?php

use App\Models\Account;
use App\Models\FixedAsset;
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

new #[Layout('components.layouts.admin')] #[Title('Aset Tetap Usaha - Halala Food')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    #[Url]
    public string $conditionFilter = '';

    // Modal states
    public bool $showAssetModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Form fields
    public string $name = '';
    public string $category = 'peralatan';
    public string $purchase_date = '';
    public float $purchase_price = 0;
    public int $useful_life_months = 36;
    public string $condition = 'baik';
    public string $location = '';
    public string $notes = '';
    public ?int $account_id = null; // cash/bank account used to pay (optional)

    // Depreciation modal state
    public bool $showDepreciationModal = false;
    public ?int $depreciatingAssetId = null;
    public string $depreciationDate = '';
    public float|int|string $depreciationAmount = 0;
    public string $depreciationNotes = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategoryFilter(): void { $this->resetPage(); }
    public function updatedConditionFilter(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'conditionFilter']);
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->purchase_date = Carbon::now()->format('Y-m-d');
        $this->account_id = Account::first()?->id;
    }

    public function openCreateModal(): void
    {
        if (Gate::denies('aset-create')) {
            abort(403);
        }
        $this->reset(['name', 'category', 'purchase_price', 'condition', 'location', 'notes']);
        $this->purchase_date = Carbon::now()->format('Y-m-d');
        $this->category = 'peralatan';
        $this->useful_life_months = 36;
        $this->condition = 'baik';
        $this->account_id = Account::first()?->id;
        $this->editingId = null;
        $this->showAssetModal = true;
    }

    public function openDepreciationModal(int $id): void
    {
        $asset = FixedAsset::findOrFail($id);
        $this->depreciatingAssetId = $id;
        $this->depreciationDate = Carbon::now()->format('Y-m-d');
        $this->depreciationAmount = min($asset->book_value, $asset->monthly_depreciation);
        $this->depreciationNotes = "Penyusutan Bulanan {$asset->name} ({$asset->asset_code})";
        $this->showDepreciationModal = true;
    }

    public function closeDepreciationModal(): void
    {
        $this->showDepreciationModal = false;
        $this->depreciatingAssetId = null;
    }

    public function saveDepreciation(): void
    {
        if (Gate::denies('aset-edit') && Gate::denies('aset-create')) {
            abort(403);
        }

        $asset = FixedAsset::findOrFail($this->depreciatingAssetId);
        if (is_string($this->depreciationAmount)) {
            $cleaned = preg_replace('/[^0-9]/', '', $this->depreciationAmount);
            $this->depreciationAmount = $cleaned !== '' ? (float) $cleaned : 0.0;
        }

        $maxAmount = (float) $asset->book_value;
        $this->validate([
            'depreciationDate' => 'required|date',
            'depreciationAmount' => ['required', 'numeric', 'min:1', "max:{$maxAmount}"],
            'depreciationNotes' => 'nullable|string|max:500',
        ], [
            'depreciationDate.required' => 'Tanggal penyusutan wajib diisi.',
            'depreciationAmount.required' => 'Nominal penyusutan wajib diisi.',
            'depreciationAmount.min' => 'Nominal penyusutan minimal Rp 1.',
            'depreciationAmount.max' => 'Nominal penyusutan tidak boleh melebihi sisa nilai buku (Rp ' . number_format($maxAmount, 0, ',', '.') . ').',
        ]);

        DB::transaction(function () use ($asset) {
            AccountingService::recordFixedAssetDepreciation(
                $asset,
                (float) $this->depreciationAmount,
                $this->depreciationDate,
                $this->depreciationNotes
            );
        });

        $this->showDepreciationModal = false;
        $this->depreciatingAssetId = null;
        session()->flash('success', "Penyusutan aset '{$asset->name}' sebesar Rp " . number_format((float) $this->depreciationAmount, 0, ',', '.') . " berhasil dicatat dan diposting ke Jurnal Akuntansi.");
    }

    public function openEditModal(int $id): void
    {
        if (Gate::denies('aset-edit')) {
            abort(403);
        }
        $asset = FixedAsset::findOrFail($id);
        $this->editingId = $id;
        $this->name = $asset->name;
        $this->category = $asset->category;
        $this->purchase_date = $asset->purchase_date->format('Y-m-d');
        $this->purchase_price = $asset->purchase_price;
        $this->condition = $asset->condition;
        $this->location = $asset->location ?? '';
        $this->notes = $asset->notes ?? '';
        $this->account_id = null;
        $this->showAssetModal = true;
    }

    public function saveAsset(): void
    {
        $this->validate([
            'name'           => 'required|string|max:255',
            'category'       => 'required|in:mesin,peralatan,kendaraan,inventaris,bangunan,lainnya',
            'purchase_date'  => 'required|date',
            'purchase_price' => 'required|numeric|min:1',
            'condition'      => 'required|in:baik,rusak_ringan,rusak_berat,tidak_aktif',
            'location'       => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ], [
            'name.required'           => 'Nama aset wajib diisi.',
            'purchase_price.min'      => 'Harga perolehan minimal Rp 1.',
            'purchase_price.required' => 'Harga perolehan wajib diisi.',
            'purchase_date.required'  => 'Tanggal perolehan wajib diisi.',
        ]);

        DB::transaction(function () {
            if ($this->editingId) {
                // Edit mode — update non-financial fields only
                $asset = FixedAsset::findOrFail($this->editingId);
                $asset->update([
                    'name'      => $this->name,
                    'category'  => $this->category,
                    'condition' => $this->condition,
                    'location'  => $this->location ?: null,
                    'notes'     => $this->notes ?: null,
                ]);
            } else {
                // Create mode — auto-generate asset code, save and auto-journal
                $asset = FixedAsset::create([
                    'name'           => $this->name,
                    'category'       => $this->category,
                    'asset_code'     => FixedAsset::generateAssetCode(),
                    'purchase_date'  => $this->purchase_date,
                    'purchase_price' => $this->purchase_price,
                    'useful_life_months' => max(1, $this->useful_life_months ?: 36),
                    'accumulated_depreciation' => 0.00,
                    'book_value'     => $this->purchase_price,
                    'condition'      => $this->condition,
                    'location'       => $this->location ?: null,
                    'notes'          => $this->notes ?: null,
                ]);

                $journal = AccountingService::recordFixedAssetPurchase($asset, $this->account_id ?: null);

                if ($journal) {
                    $asset->update(['journal_entry_id' => $journal->id]);
                }
            }
        });

        $this->showAssetModal = false;
        $label = $this->editingId ? 'diperbarui' : 'dicatat dan dijurnal';
        session()->flash('success', "Aset '{$this->name}' berhasil {$label}.");
    }

    public function confirmDelete(int $id): void
    {
        if (Gate::denies('aset-delete')) {
            abort(403);
        }
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteAsset(): void
    {
        if (Gate::denies('aset-delete')) {
            abort(403);
        }

        if (! $this->deletingId) return;

        $asset = FixedAsset::findOrFail($this->deletingId);

        DB::transaction(function () use ($asset) {
            // Delete associated purchase journal entry if exists
            if ($asset->journal_entry_id) {
                JournalEntry::find($asset->journal_entry_id)?->delete();
            }

            // Delete associated depreciation journal entries if exist
            JournalEntry::where('reference_type', 'fixed_asset_depreciation')
                ->where('reference_id', $asset->id)
                ->delete();

            $name = $asset->name;
            $asset->delete();
            session()->flash('success', "Aset '{$name}' dihapus dari daftar inventaris.");
        });

        $this->deletingId = null;
    }

    public function with(): array
    {
        $categories = FixedAsset::categories();
        $conditions = FixedAsset::conditions();

        $assets = FixedAsset::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('asset_code', 'like', '%' . $this->search . '%')
                ->orWhere('location', 'like', '%' . $this->search . '%'))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->conditionFilter, fn ($q) => $q->where('condition', $this->conditionFilter))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(12);

        $totalValue  = FixedAsset::whereIn('condition', ['baik', 'rusak_ringan'])->sum('book_value');
        $totalDepreciation = FixedAsset::sum('accumulated_depreciation');
        $totalAssets = FixedAsset::count();
        $activeCount = FixedAsset::whereIn('condition', ['baik', 'rusak_ringan'])->count();
        $accounts    = Account::orderBy('name')->get();
        $depreciatingAsset = $this->depreciatingAssetId ? FixedAsset::find($this->depreciatingAssetId) : null;

        $hasActiveFilters = $this->search !== '' || $this->categoryFilter !== '' || $this->conditionFilter !== '';

        return compact('assets', 'categories', 'conditions', 'totalValue', 'totalDepreciation', 'totalAssets', 'activeCount', 'accounts', 'hasActiveFilters', 'depreciatingAsset');
    }
};
