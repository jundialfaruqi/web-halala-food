<?php

use App\Models\BusinessSetting;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('components.layouts.admin')] #[Title('Laporan & Rekapitulasi Bisnis - Halala Food')] class extends Component
{
    #[Url]
    public string $tab = 'overview'; // overview, stores, stock_waste

    #[Url]
    public string $period = 'this_month'; // this_month, last_month, this_year, custom

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        if (Gate::denies('laporan-view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat laporan bisnis.');
        }

        $this->resolveDates();
    }

    public function updatedPeriod(): void
    {
        $this->resolveDates();
    }

    private function resolveDates(): void
    {
        if ($this->period === 'this_month') {
            $this->startDate = now()->startOfMonth()->toDateString();
            $this->endDate = now()->endOfMonth()->toDateString();
        } elseif ($this->period === 'last_month') {
            $this->startDate = now()->subMonth()->startOfMonth()->toDateString();
            $this->endDate = now()->subMonth()->endOfMonth()->toDateString();
        } elseif ($this->period === 'this_year') {
            $this->startDate = now()->startOfYear()->toDateString();
            $this->endDate = now()->endOfYear()->toDateString();
        } elseif ($this->period === 'custom' && empty($this->startDate)) {
            $this->startDate = now()->startOfMonth()->toDateString();
            $this->endDate = now()->toDateString();
        }
    }

    public function with(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // 1. Overview Financials
        $invoicesQuery = Invoice::whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()]);
        $totalInvoiced = (float) $invoicesQuery->sum('total_amount');
        $totalPaidOnInvoices = (float) $invoicesQuery->sum('paid_amount');
        $totalReceivables = (float) $invoicesQuery->sum('remaining_balance');

        $cashIn = (float) InvoicePayment::whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $purchasesCost = (float) RawMaterialPurchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount');

        // HPP of batches executed in period
        $productionHpp = (float) ProductionBatch::where('status', 'completed')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('completed_at', [$start, $end])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->whereNull('completed_at')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->sum('total_material_cost');

        $grossProfit = $totalInvoiced - $productionHpp;

        // 2. Store Accounts Breakdown
        $stores = Store::orderBy('name')->get()->map(function ($store) use ($start, $end) {
            $storeInvoices = Invoice::where('store_id', $store->id)
                ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()]);

            $totalBill = (float) $storeInvoices->sum('total_amount');
            $totalPaid = (float) $storeInvoices->sum('paid_amount');
            $balance = (float) $storeInvoices->sum('remaining_balance');

            $deliveriesCount = Delivery::where('store_id', $store->id)
                ->whereBetween('delivery_date', [$start->toDateString(), $end->toDateString()])
                ->where('status', 'selesai')
                ->count();

            return [
                'id' => $store->id,
                'name' => $store->name,
                'owner' => $store->owner_name,
                'phone' => $store->phone,
                'deliveries_count' => $deliveriesCount,
                'total_bill' => $totalBill,
                'total_paid' => $totalPaid,
                'balance' => $balance,
            ];
        });

        // 3. Stock & Valuation
        $rawMaterials = RawMaterial::with('unitModel')->get();
        $totalRawMaterialValue = $rawMaterials->sum(function ($mat) {
            return (float) $mat->stock * (float) $mat->cost_per_unit;
        });

        $products = Product::where('is_active', true)->get();
        $totalProductStockValue = $products->sum(function ($prod) {
            return (int) $prod->stock_ready * (float) $prod->consignment_price;
        });

        return [
            'totalInvoiced' => $totalInvoiced,
            'totalPaidOnInvoices' => $totalPaidOnInvoices,
            'totalReceivables' => $totalReceivables,
            'cashIn' => $cashIn,
            'purchasesCost' => $purchasesCost,
            'productionHpp' => $productionHpp,
            'grossProfit' => $grossProfit,
            'stores' => $stores,
            'rawMaterials' => $rawMaterials,
            'totalRawMaterialValue' => $totalRawMaterialValue,
            'products' => $products,
            'totalProductStockValue' => $totalProductStockValue,
            'settings' => BusinessSetting::getSettings(),
        ];
    }
};
