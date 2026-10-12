<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardStatController extends Controller
{
    /**
     * Dapatkan ringkasan statistik dan data chart donat berdasarkan permission pengguna.
     * Permission:
     * - dashboard-stat-bisnis: Data omzet, stok siap, pengantaran keseluruhan.
     * - dashboard-stat-pengantaran: Data tugas surat jalan dan rute kurir pribadi.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // 1. Prioritaskan Ringkasan Bisnis jika user memiliki permission bisnis
        if ($user->can('dashboard-stat-bisnis')) {
            return response()->json([
                'success' => true,
                'message' => 'Data statistik ringkasan bisnis berhasil dimuat.',
                'data' => $this->getBusinessStat(),
            ]);
        }

        // 2. Jika user memiliki permission pengantaran (misal: Kurir)
        if ($user->can('dashboard-stat-pengantaran')) {
            return response()->json([
                'success' => true,
                'message' => 'Data statistik pengantaran kurir berhasil dimuat.',
                'data' => $this->getCourierStat($user),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki hak akses untuk melihat statistik dashboard.',
        ], 403);
    }

    /**
     * Ringkasan Bisnis untuk Manager / Pemilik Usaha
     */
    private function getBusinessStat(): array
    {
        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // 1. Penjualan Hari Ini (jika ada), jika 0 ambil total bulan ini
        $salesToday = (float) Invoice::whereDate('invoice_date', $today)->sum('total_amount');
        $isMonthly = false;

        if ($salesToday <= 0) {
            $salesToday = (float) Invoice::whereBetween('invoice_date', [$startOfMonth, $endOfMonth])->sum('total_amount');
            $isMonthly = true;
        }

        $formattedSales = $this->formatCurrencyCompact($salesToday);

        // 2. Stok Barang Jadi Siap Jual
        $readyStock = (int) Product::sum('stock_ready');

        // 3. Toko Terlayani / Pengantaran Hari ini
        $deliveriesToday = Delivery::whereDate('delivery_date', $today)->get();
        if ($deliveriesToday->isEmpty()) {
            // Ambil pengantaran aktif non-dibatalkan terbaru
            $deliveriesToday = Delivery::whereIn('status', ['diproses', 'dikirim', 'selesai'])
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->get();
        }

        $completedCount = $deliveriesToday->where('status', 'selesai')->count();
        $dispatchedCount = $deliveriesToday->where('status', 'dikirim')->count();
        $processingCount = $deliveriesToday->where('status', 'diproses')->count();
        $totalDeliveries = $completedCount + $dispatchedCount + $processingCount;

        $activeStoresCount = Store::where('is_active', true)->count();

        // Donut Chart Slices
        $slices = [];
        $percentage = 0;

        if ($totalDeliveries > 0) {
            $percentage = (int) round(($completedCount / $totalDeliveries) * 100);

            if ($completedCount > 0) {
                $slices[] = [
                    'label' => 'Selesai',
                    'value' => $completedCount,
                    'color' => '#10B981', // Emerald / Green
                ];
            }
            if ($dispatchedCount > 0) {
                $slices[] = [
                    'label' => 'Dalam Rute',
                    'value' => $dispatchedCount,
                    'color' => '#F59E0B', // Amber / Orange
                ];
            }
            if ($processingCount > 0) {
                $slices[] = [
                    'label' => 'Diproses',
                    'value' => $processingCount,
                    'color' => '#3B82F6', // Blue
                ];
            }
        } else {
            // Fallback jika belum ada delivery
            $percentage = 100;
            $slices[] = [
                'label' => 'Toko Aktif',
                'value' => max(1, $activeStoresCount),
                'color' => '#10B981',
            ];
        }

        return [
            'type' => 'bisnis',
            'title' => 'Ringkasan Bisnis',
            'date_formatted' => Carbon::now()->translatedFormat('l, d M Y'),
            'donut' => [
                'percentage' => $percentage,
                'center_label' => $percentage.'%',
                'center_sublabel' => 'Selesai',
                'slices' => $slices,
            ],
            'metrics' => [
                [
                    'label' => $isMonthly ? 'Penjualan Bln Ini' : 'Penjualan Hari Ini',
                    'value' => $formattedSales,
                    'color' => '#10B981',
                ],
                [
                    'label' => 'Produksi Siap',
                    'value' => number_format($readyStock, 0, ',', '.').' Box',
                    'color' => '#3B82F6',
                ],
                [
                    'label' => 'Toko Mitra',
                    'value' => $activeStoresCount.' Toko',
                    'color' => '#F59E0B',
                ],
            ],
        ];
    }

    /**
     * Ringkasan Pengantaran untuk Kurir
     */
    private function getCourierStat(User $user): array
    {
        $today = Carbon::today()->toDateString();

        // Ambil surat jalan yang ditugaskan ke kurir ini hari ini atau yang sedang aktif
        $deliveries = Delivery::where('courier_id', $user->id)
            ->where(function ($q) use ($today) {
                $q->whereDate('delivery_date', $today)
                    ->orWhereIn('status', ['diproses', 'dikirim']);
            })
            ->get();

        // Jika hari ini belum ada dan tidak ada aktif, ambil 10 pengantaran terakhir untuk demo/tampilan
        if ($deliveries->isEmpty()) {
            $deliveries = Delivery::where('courier_id', $user->id)
                ->latest('id')
                ->take(10)
                ->get();
        }

        $completedCount = $deliveries->where('status', 'selesai')->count();
        $dispatchedCount = $deliveries->where('status', 'dikirim')->count();
        $processingCount = $deliveries->where('status', 'diproses')->count();
        $totalTasks = $completedCount + $dispatchedCount + $processingCount;

        $totalStoresCount = $deliveries->pluck('store_id')->unique()->count();

        // Donut Chart Slices
        $slices = [];
        $percentage = 0;

        if ($totalTasks > 0) {
            $percentage = (int) round(($completedCount / $totalTasks) * 100);

            if ($completedCount > 0) {
                $slices[] = [
                    'label' => 'Selesai',
                    'value' => $completedCount,
                    'color' => '#10B981', // Hijau
                ];
            }
            if ($dispatchedCount > 0) {
                $slices[] = [
                    'label' => 'Dalam Rute',
                    'value' => $dispatchedCount,
                    'color' => '#F59E0B', // Oranye
                ];
            }
            if ($processingCount > 0) {
                $slices[] = [
                    'label' => 'Belum Diantar',
                    'value' => $processingCount,
                    'color' => '#64748B', // Abu-abu Slate
                ];
            }
        } else {
            $percentage = 0;
            $slices[] = [
                'label' => 'Tidak Ada Tugas',
                'value' => 1,
                'color' => '#CBD5E1',
            ];
        }

        return [
            'type' => 'pengantaran',
            'title' => 'Tugas Pengantaran Hari Ini',
            'date_formatted' => Carbon::now()->translatedFormat('l, d M Y'),
            'donut' => [
                'percentage' => $percentage,
                'center_label' => $percentage.'%',
                'center_sublabel' => 'Selesai',
                'slices' => $slices,
            ],
            'metrics' => [
                [
                    'label' => 'Total Toko',
                    'value' => $totalStoresCount.' Toko',
                    'color' => '#3B82F6',
                ],
                [
                    'label' => 'Sudah Terkirim',
                    'value' => $completedCount.' SJ Selesai',
                    'color' => '#10B981',
                ],
                [
                    'label' => 'Sisa Rute',
                    'value' => ($dispatchedCount + $processingCount).' Belum Selesai',
                    'color' => '#F59E0B',
                ],
            ],
        ];
    }

    /**
     * Helper pemformatan nominal ringkas (contoh: Rp 4,8 Jt, Rp 500 Rb)
     */
    private function formatCurrencyCompact(float $amount): string
    {
        if ($amount >= 1_000_000_000) {
            return 'Rp '.rtrim(rtrim(number_format($amount / 1_000_000_000, 1, ',', '.'), '0'), ',').' M';
        }

        if ($amount >= 1_000_000) {
            return 'Rp '.rtrim(rtrim(number_format($amount / 1_000_000, 1, ',', '.'), '0'), ',').' Jt';
        }

        if ($amount >= 1_000) {
            return 'Rp '.number_format($amount / 1_000, 0, ',', '.').' Rb';
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
