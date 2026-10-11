<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Check user permission with support for roles (dev, manager) and Spatie permissions.
     */
    private function checkPermission(string $permission): bool
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('dev') || $user->hasRole('manager')) {
            return true;
        }

        return $user->hasPermissionTo($permission, 'web') || $user->can($permission);
    }

    /**
     * Get business report & recap data with tabs and period filters.
     * Permission: laporan-view
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->checkPermission('laporan-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat laporan bisnis.',
            ], 403);
        }

        $period = $request->input('period', 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Resolusi Tanggal berdasarkan Periode
        if ($period === 'this_month') {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
        } elseif ($period === 'last_month') {
            $startDate = now()->subMonth()->startOfMonth()->toDateString();
            $endDate = now()->subMonth()->endOfMonth()->toDateString();
        } elseif ($period === 'this_year') {
            $startDate = now()->startOfYear()->toDateString();
            $endDate = now()->endOfYear()->toDateString();
        } elseif ($period === 'custom') {
            if (empty($startDate)) {
                $startDate = now()->startOfMonth()->toDateString();
            }
            if (empty($endDate)) {
                $endDate = now()->toDateString();
            }
        } else {
            $period = 'this_month';
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Overview Financials & Cash Flow
        $invoicesQuery = Invoice::whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()]);
        $totalInvoiced = (float) $invoicesQuery->sum('total_amount');
        $totalPaidOnInvoices = (float) $invoicesQuery->sum('paid_amount');
        $totalReceivables = (float) $invoicesQuery->sum('remaining_balance');

        $cashIn = (float) InvoicePayment::whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $purchasesCost = (float) RawMaterialPurchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount');

        // HPP of completed batches in period
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

            $status = 'belum_ada_transaksi';
            if ($balance <= 0 && $totalBill > 0) {
                $status = 'lunas';
            } elseif ($balance > 0) {
                $status = 'piutang';
            }

            return [
                'id' => $store->id,
                'name' => $store->name,
                'owner' => $store->owner_name,
                'phone' => $store->phone,
                'route' => $store->route,
                'deliveries_count' => $deliveriesCount,
                'total_bill' => $totalBill,
                'total_paid' => $totalPaid,
                'balance' => $balance,
                'status' => $status,
            ];
        });

        $storesSummary = [
            'total_stores' => $stores->count(),
            'total_deliveries' => (int) $stores->sum('deliveries_count'),
            'total_bill' => (float) $stores->sum('total_bill'),
            'total_paid' => (float) $stores->sum('total_paid'),
            'total_balance' => (float) $stores->sum('balance'),
        ];

        // 3. Stock & Valuation
        $rawMaterials = RawMaterial::with('unitModel')->get()->map(function ($mat) {
            $stock = (float) $mat->stock;
            $costPerUnit = (float) $mat->cost_per_unit;
            $valuation = $stock * $costPerUnit;

            return [
                'id' => $mat->id,
                'name' => $mat->name,
                'code' => $mat->code,
                'stock' => $stock,
                'display_unit' => $mat->display_unit,
                'cost_per_unit' => $costPerUnit,
                'valuation' => $valuation,
            ];
        });
        $totalRawMaterialValue = (float) $rawMaterials->sum('valuation');

        $products = Product::where('is_active', true)->with('unitModel')->get()->map(function ($prod) {
            $stockReady = (int) $prod->stock_ready;
            $consignmentPrice = (float) $prod->consignment_price;
            $valuation = $stockReady * $consignmentPrice;

            return [
                'id' => $prod->id,
                'name' => $prod->name,
                'code' => $prod->code,
                'stock_ready' => $stockReady,
                'unit' => $prod->unit,
                'consignment_price' => $consignmentPrice,
                'valuation' => $valuation,
            ];
        });
        $totalProductStockValue = (float) $products->sum('valuation');

        $settings = BusinessSetting::getSettings();

        return response()->json([
            'success' => true,
            'message' => 'Laporan bisnis berhasil dimuat.',
            'data' => [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'overview' => [
                    'total_invoiced' => $totalInvoiced,
                    'total_paid_on_invoices' => $totalPaidOnInvoices,
                    'total_receivables' => $totalReceivables,
                    'cash_in' => $cashIn,
                    'purchases_cost' => $purchasesCost,
                    'production_hpp' => $productionHpp,
                    'gross_profit' => $grossProfit,
                ],
                'stores' => [
                    'summary' => $storesSummary,
                    'items' => $stores->values(),
                ],
                'stock_waste' => [
                    'total_raw_material_value' => $totalRawMaterialValue,
                    'total_product_stock_value' => $totalProductStockValue,
                    'total_inventory_value' => $totalRawMaterialValue + $totalProductStockValue,
                    'raw_materials' => $rawMaterials->values(),
                    'products' => $products->values(),
                ],
                'settings' => [
                    'company_name' => $settings->company_name ?? 'Halala Food',
                    'company_address' => $settings->company_address ?? '',
                    'company_phone' => $settings->company_phone ?? '',
                    'company_email' => $settings->company_email ?? '',
                ],
            ],
        ]);
    }
}
