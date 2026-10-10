<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\JournalEntry;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseItem;
use App\Models\StockMutation;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    /**
     * Check user permission with support for roles (dev, manager, admin) and Spatie permissions.
     */
    private function checkPermission(string $permission): bool
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user() ?? Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('dev') || $user->hasRole('manager') || $user->hasRole('admin') ||
            $user->roles->pluck('name')->intersect(['dev', 'manager', 'admin'])->isNotEmpty()) {
            return true;
        }

        return $user->hasPermissionTo($permission, 'web') || $user->can($permission);
    }

    /**
     * Menampilkan daftar transaksi pembelian bahan baku dengan filter & statistik.
     * Permission: pembelian-view
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->checkPermission('pembelian-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data pembelian bahan baku.',
            ], 403);
        }

        $query = RawMaterialPurchase::query()
            ->with(['items.rawMaterial.unitModel', 'creator', 'paidAccount'])
            ->latest('purchase_date')
            ->latest('id');

        // Filter Pencarian
        if ($request->filled('search')) {
            $term = '%'.trim($request->string('search')).'%';
            $query->where(function ($q) use ($term) {
                $q->where('purchase_number', 'like', $term)
                    ->orWhere('supplier_name', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('items.rawMaterial', function ($mq) use ($term) {
                        $mq->where('name', 'like', $term);
                    });
            });
        }

        // Filter Metode Pembayaran
        if ($request->filled('payment_method') && $request->string('payment_method') !== 'all') {
            $query->where('payment_method', $request->string('payment_method'));
        }

        // Filter Status Pembayaran
        if ($request->filled('payment_status') && $request->string('payment_status') !== 'all') {
            $query->where('payment_status', $request->string('payment_status'));
        }

        $perPage = (int) $request->input('per_page', 15);
        $purchases = $query->paginate($perPage);

        // Ringkasan Statistik Belanja Bulan Berjalan
        $thisMonth = now()->startOfMonth();
        $monthlyTotalAmount = (float) RawMaterialPurchase::where('purchase_date', '>=', $thisMonth)->sum('total_amount');
        $monthlyTotalCount = RawMaterialPurchase::where('purchase_date', '>=', $thisMonth)->count();

        // Total Sisa Hutang Pembelian Belum Lunas (Tempo)
        $totalUnpaidDebt = (float) RawMaterialPurchase::where('payment_method', 'tempo')
            ->where('payment_status', 'belum_lunas')
            ->get()
            ->sum(fn ($p) => $p->remaining_debt);

        return response()->json([
            'success' => true,
            'data' => [
                'purchases' => $purchases->items(),
                'pagination' => [
                    'current_page' => $purchases->currentPage(),
                    'last_page' => $purchases->lastPage(),
                    'per_page' => $purchases->perPage(),
                    'total' => $purchases->total(),
                ],
                'stats' => [
                    'monthly_total_amount' => $monthlyTotalAmount,
                    'monthly_total_count' => $monthlyTotalCount,
                    'total_unpaid_debt' => $totalUnpaidDebt,
                ],
                'permissions' => [
                    'can_create' => $this->checkPermission('pembelian-create'),
                    'can_edit' => $this->checkPermission('pembelian-edit') || $this->checkPermission('pembelian-create'),
                    'can_delete' => $this->checkPermission('pembelian-delete'),
                ],
            ],
        ]);
    }

    /**
     * Mengambil opsi dan data referensi untuk formulir pembelian baru.
     * Permission: pembelian-create
     */
    public function options(): JsonResponse
    {
        if (! $this->checkPermission('pembelian-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mencatat pembelian bahan baku.',
            ], 403);
        }

        $rawMaterials = RawMaterial::with('unitModel')
            ->orderBy('name')
            ->get()
            ->map(function ($rm) {
                return [
                    'id' => $rm->id,
                    'name' => $rm->name,
                    'stock' => (float) $rm->stock,
                    'display_unit' => $rm->display_unit,
                    'unit_name' => $rm->unitModel?->name ?? $rm->display_unit,
                    'cost_per_unit' => (float) $rm->cost_per_unit,
                ];
            });

        $accounts = Account::orderBy('name')
            ->get()
            ->map(function ($acc) {
                return [
                    'id' => $acc->id,
                    'name' => $acc->name,
                    'type' => $acc->type,
                    'balance' => (float) $acc->balance,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'purchase_number' => RawMaterialPurchase::generatePurchaseNumber(),
                'purchase_date' => now()->toDateString(),
                'raw_materials' => $rawMaterials,
                'accounts' => $accounts,
            ],
        ]);
    }

    /**
     * Menampilkan detail lengkap transaksi pembelian bahan baku.
     * Permission: pembelian-view
     */
    public function show(int $id): JsonResponse
    {
        if (! $this->checkPermission('pembelian-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data pembelian bahan baku.',
            ], 403);
        }

        $purchase = RawMaterialPurchase::with([
            'items.rawMaterial.unitModel',
            'creator',
            'paidAccount',
        ])->findOrFail($id);

        // Jika transaksi memiliki pelunasan hutang, ambil riwayat transaksi kas pelunasan
        $debtPayments = CashTransaction::where('reference_type', 'purchase_payment')
            ->where('reference_id', $purchase->id)
            ->with('account')
            ->latest('transaction_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'purchase' => $purchase,
                'debt_payments' => $debtPayments,
                'permissions' => [
                    'can_edit' => $this->checkPermission('pembelian-edit') || $this->checkPermission('pembelian-create'),
                    'can_delete' => $this->checkPermission('pembelian-delete'),
                ],
            ],
        ]);
    }

    /**
     * Menyimpan transaksi pembelian bahan baku baru (+ sinkronisasi stok, buku kas, dan akuntansi).
     * Permission: pembelian-create
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->checkPermission('pembelian-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mencatat pembelian bahan baku.',
            ], 403);
        }

        $rules = [
            'purchase_number' => ['nullable', 'string', 'max:50'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:tunai,transfer_bank,tempo'],
            'account_id' => [
                $request->input('payment_method') !== 'tempo' ? 'required' : 'nullable',
                'nullable',
                'exists:accounts,id',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.raw_material_id' => ['required', 'exists:raw_materials,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.cost_per_unit' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];

        $messages = [
            'supplier_name.required' => 'Nama supplier / toko penyedia wajib diisi.',
            'purchase_date.required' => 'Tanggal pembelian wajib dipilih.',
            'account_id.required' => 'Pilih akun kas atau rekening pembayaran.',
            'account_id.exists' => 'Akun kas atau rekening yang dipilih tidak valid.',
            'items.min' => 'Minimal masukkan 1 baris bahan baku.',
            'items.*.raw_material_id.required' => 'Pilih bahan baku untuk tiap baris.',
            'items.*.quantity.gt' => 'Jumlah kuantitas harus lebih dari 0.',
            'items.*.cost_per_unit.min' => 'Harga satuan tidak boleh negatif.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (empty($validated['purchase_number']) || RawMaterialPurchase::where('purchase_number', $validated['purchase_number'])->exists()) {
            $validated['purchase_number'] = RawMaterialPurchase::generatePurchaseNumber();
        }

        $purchase = DB::transaction(function () use ($validated) {
            $total = 0.0;
            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $cost = (float) $item['cost_per_unit'];
                $total += round($qty * $cost, 2);
            }

            $paymentMethod = $validated['payment_method'];
            $isTempo = $paymentMethod === 'tempo';
            $user = Auth::guard('api')->user() ?? Auth::user();

            $purchase = RawMaterialPurchase::create([
                'purchase_number' => $validated['purchase_number'],
                'supplier_name' => trim($validated['supplier_name']),
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'payment_status' => $isTempo ? 'belum_lunas' : 'lunas',
                'paid_amount' => $isTempo ? 0.00 : $total,
                'paid_at' => $isTempo ? null : $validated['purchase_date'],
                'paid_account_id' => $isTempo ? null : ($validated['account_id'] ?? null),
                'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
                'created_by' => $user?->id,
            ]);

            $itemDetails = [];
            foreach ($validated['items'] as $item) {
                $rawMat = RawMaterial::lockForUpdate()->with('unitModel')->find($item['raw_material_id']);
                if (! $rawMat) {
                    continue;
                }

                $qty = (float) $item['quantity'];
                $cost = (float) $item['cost_per_unit'];
                $subtotal = round($qty * $cost, 2);

                $unitStr = $rawMat->unitModel?->short_name ?? $rawMat->display_unit ?? '';
                $formattedQty = number_format($qty, (floor($qty) == $qty ? 0 : 2), ',', '.');
                $itemDetails[] = "{$rawMat->name} ({$formattedQty} {$unitStr})";

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

                // Catat mutasi stok masuk
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
                    'notes' => 'Pembelian dari '.$purchase->supplier_name,
                    'user_id' => $user?->id,
                ]);

                // Update stok bahan baku dan harga beli satuan terakhir
                $rawMat->stock = $stockAfter;
                if ($cost > 0) {
                    $rawMat->cost_per_unit = $cost;
                }
                $rawMat->save();
            }

            // Catat pengeluaran di Buku Kas jika pembayaran tunai atau transfer bank
            if (! $isTempo && $total > 0 && ! empty($validated['account_id'])) {
                $account = Account::find($validated['account_id']) ?? Account::first();
                if ($account) {
                    $account->decrement('balance', $total);

                    $itemsString = implode(', ', $itemDetails);
                    $description = "Pembelian Bahan Baku ({$purchase->purchase_number}): ".($itemsString ?: 'Item bahan')." - Supplier: {$purchase->supplier_name}";

                    CashTransaction::create([
                        'transaction_date' => $validated['purchase_date'],
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
            AccountingService::recordPurchase($purchase);

            return $purchase->load(['items.rawMaterial.unitModel', 'creator', 'paidAccount']);
        });

        return response()->json([
            'success' => true,
            'message' => "Transaksi pembelian {$purchase->purchase_number} berhasil dicatat dan stok bahan otomatis diperbarui.",
            'data' => $purchase,
        ], 201);
    }

    /**
     * Mencatat pembayaran pelunasan hutang pembelian (tempo).
     * Permission: pembelian-edit || pembelian-create
     */
    public function payDebt(Request $request, int $id): JsonResponse
    {
        if (! $this->checkPermission('pembelian-edit') && ! $this->checkPermission('pembelian-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mencatat pembayaran hutang.',
            ], 403);
        }

        $purchase = RawMaterialPurchase::findOrFail($id);
        $remaining = (float) $purchase->remaining_debt;

        if ($remaining <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi pembelian ini sudah lunas.',
            ], 422);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1', "max:{$remaining}"],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'account_id.required' => 'Pilih akun kas / bank pembayaran.',
            'account_id.exists' => 'Akun pembayaran tidak valid.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.min' => 'Nominal pembayaran minimal Rp 1.',
            'amount.max' => 'Nominal pembayaran melebihi sisa hutang (Rp '.number_format($remaining, 0, ',', '.').').',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        $amount = (float) $validated['amount'];
        $notes = $validated['notes'] ?? "Pelunasan Hutang {$purchase->purchase_number} ke {$purchase->supplier_name}";

        DB::transaction(function () use ($purchase, $account, $amount, $validated, $notes) {
            AccountingService::recordPurchaseDebtPayment(
                $purchase,
                $account,
                $amount,
                $validated['payment_date'],
                $notes
            );
        });

        $purchase->refresh()->load(['items.rawMaterial.unitModel', 'creator', 'paidAccount']);

        return response()->json([
            'success' => true,
            'message' => "Pembayaran hutang pembelian {$purchase->purchase_number} sebesar Rp ".number_format($amount, 0, ',', '.').' berhasil dicatat.',
            'data' => $purchase,
        ]);
    }

    /**
     * Membatalkan transaksi pembelian bahan baku (+ kembalikan stok, saldo kas, dan jurnal akuntansi).
     * Permission: pembelian-delete
     */
    public function destroy(int $id): JsonResponse
    {
        if (! $this->checkPermission('pembelian-delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membatalkan pembelian bahan baku.',
            ], 403);
        }

        $purchase = RawMaterialPurchase::with('items.rawMaterial')->findOrFail($id);

        // Periksa apakah pembatalan akan membuat stok menjadi negatif
        foreach ($purchase->items as $item) {
            $currentStock = (float) $item->rawMaterial->stock;
            if ($currentStock < (float) $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Pembelian {$purchase->purchase_number} tidak dapat dihapus karena sisa stok '{$item->rawMaterial->name}' ({$currentStock}) lebih kecil dari jumlah yang dibeli ({$item->quantity}). Sebagian bahan telah terpakai dalam proses masak dapur.",
                ], 422);
            }
        }

        DB::transaction(function () use ($purchase) {
            $user = Auth::guard('api')->user() ?? Auth::user();

            foreach ($purchase->items as $item) {
                $rawMat = $item->rawMaterial;
                $stockBefore = (float) $rawMat->stock;
                $stockAfter = $stockBefore - (float) $item->quantity;

                // Catat mutasi stok keluar pembatalan
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
                    'user_id' => $user?->id,
                ]);

                $rawMat->decrement('stock', $item->quantity);
            }

            // Kembalikan saldo kas jika ada transaksi kas pembelian
            $cashTx = CashTransaction::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->first();
            if ($cashTx) {
                $cashTx->account?->increment('balance', $cashTx->amount);
                $cashTx->delete();
            }

            // Kembalikan saldo kas jika ada transaksi kas pembayaran hutang
            $debtCashTxs = CashTransaction::where('reference_type', 'purchase_payment')
                ->where('reference_id', $purchase->id)
                ->get();
            foreach ($debtCashTxs as $dtx) {
                $dtx->account?->increment('balance', $dtx->amount);
                $dtx->delete();
            }

            // Hapus mutasi stok asal
            StockMutation::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->delete();

            // Hapus jurnal akuntansi terkait
            JournalEntry::whereIn('reference_type', ['purchase', 'purchase_payment'])
                ->where('reference_id', $purchase->id)
                ->delete();

            $purchase->items()->delete();
            $purchase->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Transaksi pembelian {$purchase->purchase_number} berhasil dibatalkan dan stok bahan dikembalikan.",
        ]);
    }
}
