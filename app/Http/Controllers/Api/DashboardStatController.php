<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Delivery;
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
     * Mengambil data mutasi kas (Pemasukan, Biaya Usaha, dan Prive) dari Buku Kas untuk periode berjalan.
     */
    private function getBusinessStat(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // Ambil mutasi kas untuk periode bulan ini
        $income = (float) CashTransaction::where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $expense = (float) CashTransaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $prive = (float) CashTransaction::where('type', 'prive')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Fallback jika belum ada transaksi di bulan berjalan, ambil total keseluruhan mutasi kas
        if ($income === 0.0 && $expense === 0.0 && $prive === 0.0) {
            $income = (float) CashTransaction::where('type', 'income')->sum('amount');
            $expense = (float) CashTransaction::where('type', 'expense')->sum('amount');
            $prive = (float) CashTransaction::where('type', 'prive')->sum('amount');
        }

        // Hitung Surplus / Defisit kas bersih
        $net = $income - $expense - $prive;
        $totalOutflow = $expense + $prive;

        $percentage = 0;
        $centerLabel = '0%';
        $centerSublabel = 'Surplus';

        if ($income > 0) {
            $ratio = ($net / $income) * 100;
            $percentage = (int) max(0, min(100, round($ratio)));
            if ($net >= 0) {
                $centerLabel = round($ratio).'%';
                $centerSublabel = 'Surplus';
            } else {
                $centerLabel = abs(round($ratio)).'%';
                $centerSublabel = 'Defisit';
            }
        } elseif ($totalOutflow > 0) {
            $percentage = 0;
            $centerLabel = '0%';
            $centerSublabel = 'Defisit';
        } else {
            $percentage = 100;
            $centerLabel = '100%';
            $centerSublabel = 'Stabil';
        }

        // Donut Chart Slices (Pemasukan, Biaya Usaha, Prive)
        $slices = [];
        if ($income > 0) {
            $slices[] = [
                'label' => 'Pemasukan',
                'value' => (int) round($income),
                'color' => '#10B981', // Emerald / Green
            ];
        }
        if ($expense > 0) {
            $slices[] = [
                'label' => 'Biaya Usaha',
                'value' => (int) round($expense),
                'color' => '#EF4444', // Red / Rose
            ];
        }
        if ($prive > 0) {
            $slices[] = [
                'label' => 'Prive',
                'value' => (int) round($prive),
                'color' => '#8B5CF6', // Purple
            ];
        }

        if (empty($slices)) {
            $slices[] = [
                'label' => 'Kas Usaha',
                'value' => 1,
                'color' => '#10B981',
            ];
        }

        return [
            'type' => 'bisnis',
            'title' => 'Ringkasan Bisnis',
            'date_formatted' => Carbon::now()->locale('id')->translatedFormat('l, d M Y'),
            'donut' => [
                'percentage' => $percentage,
                'center_label' => $centerLabel,
                'center_sublabel' => $centerSublabel,
                'slices' => $slices,
            ],
            'metrics' => [
                [
                    'label' => 'Pemasukan Periode ini',
                    'value' => 'Rp '.number_format($income, 0, ',', '.'),
                    'color' => '#10B981',
                ],
                [
                    'label' => 'Biaya Usaha Periode ini',
                    'value' => 'Rp '.number_format($expense, 0, ',', '.'),
                    'color' => '#EF4444',
                ],
                [
                    'label' => 'Total Prive',
                    'value' => 'Rp '.number_format($prive, 0, ',', '.'),
                    'color' => '#8B5CF6',
                ],
            ],
        ];
    }

    /**
     * Ringkasan Pengantaran untuk Kurir (Akumulasi Keseluruhan)
     */
    private function getCourierStat(User $user): array
    {
        // Ambil akumulasi seluruh surat jalan yang ditugaskan ke kurir ini
        $deliveries = Delivery::where('courier_id', $user->id)->get();

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
                'label' => 'Belum Ada Tugas',
                'value' => 1,
                'color' => '#CBD5E1',
            ];
        }

        return [
            'type' => 'pengantaran',
            'title' => 'Tugas Pengantaran',
            'date_formatted' => Carbon::now()->locale('id')->translatedFormat('l, d M Y'),
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
