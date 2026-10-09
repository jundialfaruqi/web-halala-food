<?php

use App\Models\Account;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.admin'), Title('Dashboard - Halala Food')] class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $selectedRole = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedRole(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedRole']);
        $this->resetPage();
    }

    public function with(): array
    {
        // 1. Core Financial & Operational Metrics
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $monthlyInvoiced = (float) Invoice::whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $totalReceivables = (float) Invoice::whereNotIn('status', ['lunas', 'dibatalkan'])
            ->sum('remaining_balance');

        $totalCashBalance = (float) Account::sum('balance');

        $user = Auth::user();
        $isCourier = $user && method_exists($user, 'hasRole') && $user->hasRole('kurir') && ! $user->hasAnyRole(['dev', 'manager']);

        $activeStoresCount = Store::where('is_active', true)->count();
        $pendingDeliveriesCount = Delivery::forUser($user)->whereIn('status', ['diproses', 'dikirim'])->count();
        $readyProductsStock = (int) Product::sum('stock_ready');

        $completedDeliveriesCount = Delivery::forUser($user)->where('status', 'selesai')->count();

        $canManageUsers = Gate::allows('user-manage');
        $canViewMaterials = Gate::allows('bahan-baku-view');

        // Low stock raw materials (<= min_stock)
        $lowStockMaterials = $canViewMaterials
            ? RawMaterial::whereColumn('stock', '<=', 'min_stock')->take(5)->get()
            : collect();

        // Recent deliveries (scoped for courier)
        $recentDeliveries = Delivery::forUser($user)
            ->with(['store', 'courier'])
            ->latest('id')
            ->take(5)
            ->get();

        // Recent unpaid / partial invoices
        $pendingInvoices = Invoice::with('store')
            ->whereNotIn('status', ['lunas', 'dibatalkan'])
            ->latest('id')
            ->take(5)
            ->get();

        // User management query (only if user has 'user-manage' permission)
        if ($canManageUsers) {
            $usersQuery = User::query()
                ->with('roles')
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('email', 'like', '%'.$this->search.'%');
                    });
                })
                ->when($this->selectedRole, function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->where('name', $this->selectedRole);
                    });
                })
                ->latest();

            $users = $usersQuery->paginate(5);
            $roles = class_exists(Role::class) ? Role::withCount('users')->get() : collect();
            $totalUsers = User::count();
            $totalRoles = class_exists(Role::class) ? Role::count() : 0;
            $totalPermissions = class_exists(Permission::class) ? Permission::count() : 0;
        } else {
            $users = User::whereRaw('1 = 0')->paginate(5);
            $roles = collect();
            $totalUsers = 0;
            $totalRoles = 0;
            $totalPermissions = 0;
        }

        return [
            'monthlyInvoiced' => $monthlyInvoiced,
            'totalReceivables' => $totalReceivables,
            'totalCashBalance' => $totalCashBalance,
            'activeStoresCount' => $activeStoresCount,
            'pendingDeliveriesCount' => $pendingDeliveriesCount,
            'completedDeliveriesCount' => $completedDeliveriesCount,
            'readyProductsStock' => $readyProductsStock,
            'lowStockMaterials' => $lowStockMaterials,
            'recentDeliveries' => $recentDeliveries,
            'pendingInvoices' => $pendingInvoices,

            'users' => $users,
            'totalUsers' => $totalUsers,
            'totalRoles' => $totalRoles,
            'totalPermissions' => $totalPermissions,
            'roles' => $roles,
        ];
    }
};
