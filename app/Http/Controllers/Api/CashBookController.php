<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CashBookController extends Controller
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
     * Get list of cash transactions, summary metrics, and cash accounts.
     * Permission: buku-kas-view
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->checkPermission('buku-kas-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat buku kas.',
            ], 403);
        }

        $accounts = Account::orderBy('name')->get();

        $query = CashTransaction::with([
            'account',
            'purchase.items.rawMaterial.unitModel',
            'invoicePayment.invoice.store',
        ]);

        // Filter pencarian kategori, keterangan, atau nama bahan baku
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($sub) use ($search) {
                $sub->where('category', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere(function ($pq) use ($search) {
                        $pq->whereIn('reference_type', ['purchase', 'purchase_payment'])
                            ->whereHas('purchase.items.rawMaterial', function ($mq) use ($search) {
                                $mq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Filter jenis transaksi (income, expense, prive)
        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        // Filter akun / rekening kas
        if ($request->filled('account_id') && $request->input('account_id') !== '') {
            $query->where('account_id', $request->input('account_id'));
        }

        // Filter tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->input('end_date'));
        }

        // Hitung ringkasan terfilter (filtered summary metrics)
        $filteredIncome = (float) (clone $query)->where('type', 'income')->sum('amount');
        $filteredExpense = (float) (clone $query)->where('type', 'expense')->sum('amount');
        $filteredPrive = (float) (clone $query)->where('type', 'prive')->sum('amount');

        $perPage = (int) $request->input('per_page', 15);
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $totalCashBalance = (float) $accounts->sum('balance');

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
                'accounts' => $accounts,
                'total_cash_balance' => $totalCashBalance,
                'filtered_income' => $filteredIncome,
                'filtered_expense' => $filteredExpense,
                'filtered_prive' => $filteredPrive,
                'permissions' => [
                    'can_create' => $this->checkPermission('buku-kas-create'),
                    'can_delete' => $this->checkPermission('buku-kas-delete'),
                ],
            ],
        ]);
    }

    /**
     * Get detail of a single cash transaction with its resolution relations.
     * Permission: buku-kas-view
     */
    public function show(int $id): JsonResponse
    {
        if (! $this->checkPermission('buku-kas-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat detail transaksi kas.',
            ], 403);
        }

        $transaction = CashTransaction::with([
            'account',
            'purchase.items.rawMaterial.unitModel',
            'purchase.creator',
            'invoicePayment.user',
            'invoicePayment.invoice.store',
            'invoicePayment.invoice.delivery.courier',
            'invoicePayment.invoice.items.product',
            'fixedAsset',
        ])->find($id);

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi kas tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => $transaction,
                'materials_summary' => $transaction->materials_summary,
                'permissions' => [
                    'can_delete' => $this->checkPermission('buku-kas-delete'),
                ],
            ],
        ]);
    }

    /**
     * Record a new cash transaction.
     * Permission: buku-kas-create
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->checkPermission('buku-kas-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mencatat transaksi kas.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $amount = (float) $validated['amount'];

        $transaction = DB::transaction(function () use ($validated, $amount) {
            $account = Account::findOrFail($validated['account_id']);

            // Update balance akun
            if ($validated['type'] === 'income') {
                $account->increment('balance', $amount);
            } else {
                $account->decrement('balance', $amount);
            }

            // Buat catatan transaksi kas
            $tx = CashTransaction::create([
                'transaction_date' => $validated['transaction_date'],
                'account_id' => $validated['account_id'],
                'type' => $validated['type'],
                'category' => $validated['category'],
                'amount' => $amount,
                'description' => $validated['description'] ?? null,
            ]);

            // Auto-journal di buku besar akuntansi
            AccountingService::recordCashTransaction($tx);

            return $tx->load('account');
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kas berhasil dicatat dan diposting ke jurnal akuntansi.',
            'data' => $transaction,
        ], 201);
    }

    /**
     * Delete a cash transaction and reverse its balance and journal entry.
     * Permission: buku-kas-delete
     */
    public function destroy(int $id): JsonResponse
    {
        if (! $this->checkPermission('buku-kas-delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus transaksi kas.',
            ], 403);
        }

        $tx = CashTransaction::with('account')->find($id);

        if (! $tx) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi kas tidak ditemukan.',
            ], 404);
        }

        DB::transaction(function () use ($tx) {
            // Reverse saldo akun
            if ($tx->type === 'income') {
                $tx->account->decrement('balance', $tx->amount);
            } else {
                $tx->account->increment('balance', $tx->amount);
            }

            // Hapus entri jurnal umum terkait
            JournalEntry::where('reference_type', 'cash_transaction')
                ->where('reference_id', $tx->id)
                ->delete();

            $tx->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Catatan transaksi kas berhasil dibatalkan dan saldo dikembalikan.',
        ]);
    }

    /**
     * Create a new cash/bank account.
     * Permission: buku-kas-create
     */
    public function storeAccount(Request $request): JsonResponse
    {
        if (! $this->checkPermission('buku-kas-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menambah akun kas.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $initialBalance = (float) $validated['initial_balance'];

        $account = DB::transaction(function () use ($validated, $initialBalance) {
            $acc = Account::create([
                'name' => $validated['account_name'],
                'type' => $validated['account_type'],
                'balance' => $initialBalance,
                'description' => $validated['account_description'] ?? null,
            ]);

            if ($initialBalance > 0) {
                AccountingService::recordOpeningBalance($acc, $initialBalance);
            }

            return $acc;
        });

        return response()->json([
            'success' => true,
            'message' => "Akun kas '{$account->name}' berhasil ditambahkan.",
            'data' => $account,
        ], 201);
    }
}
