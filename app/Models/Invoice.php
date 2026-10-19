<?php

namespace App\Models;

use App\Services\ActivityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Invoice $invoice) {
            ActivityService::markInvoiceDeleted($invoice);
        });
    }

    protected $fillable = [
        'invoice_number',
        'delivery_id',
        'store_id',
        'courier_id',
        'created_by',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount',
        'total_amount',
        'paid_amount',
        'remaining_balance',
        'status',
        'notes',
    ];

    protected $appends = [
        'status_label',
        'is_overdue',
        'invoice_date_formatted',
        'due_date_formatted',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
        ];
    }

    public function getInvoiceDateFormattedAttribute(): ?string
    {
        return $this->invoice_date?->translatedFormat('d M Y');
    }

    public function getDueDateFormattedAttribute(): ?string
    {
        return $this->due_date?->translatedFormat('d M Y');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'belum_dibayar' => 'Belum Dibayar',
            'sebagian' => 'Dibayar Sebagian',
            'lunas' => 'Lunas',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'lunas' || $this->status === 'dibatalkan') {
            return false;
        }

        return $this->due_date ? $this->due_date->endOfDay()->isPast() : false;
    }

    public function recalculateStatusAndBalance(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $totalAmount = (float) $this->total_amount;
        $remaining = max(0.0, $totalAmount - $totalPaid);

        $newStatus = $this->status;
        if ($newStatus !== 'dibatalkan') {
            if ($remaining <= 0 && $totalAmount > 0) {
                $newStatus = 'lunas';
            } elseif ($totalPaid > 0) {
                $newStatus = 'sebagian';
            } else {
                $newStatus = 'belum_dibayar';
            }
        }

        $this->update([
            'paid_amount' => $totalPaid,
            'remaining_balance' => $remaining,
            'status' => $newStatus,
        ]);
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $latest = static::where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($latest) {
            $lastSequence = (int) substr($latest, -4);
            $nextSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '0001';
        }

        return $prefix.$nextSequence;
    }

    /**
     * Scope query to only include invoices accessible by the user.
     * Kurir is strictly limited to invoices assigned to them (courier_id == user->id).
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        /** @var User|null $currentUser */
        $currentUser = $user ?? Auth::user() ?? auth('api')->user();
        if (! $currentUser instanceof User) {
            return $query;
        }

        if ($currentUser->hasRole('kurir') && ! $currentUser->hasAnyRole(['dev', 'manager'])) {
            return $query->where('courier_id', $currentUser->id);
        }

        return $query;
    }

    /**
     * Check if this invoice is accessible by the specified user.
     * Kurir can only access invoices assigned to themselves.
     */
    public function isAccessibleBy(?User $user = null): bool
    {
        /** @var User|null $currentUser */
        $currentUser = $user ?? Auth::user() ?? auth('api')->user();
        if (! $currentUser instanceof User) {
            return false;
        }

        if ($currentUser->hasRole('kurir') && ! $currentUser->hasAnyRole(['dev', 'manager'])) {
            return $this->courier_id !== null && (int) $this->courier_id === (int) $currentUser->id;
        }

        return true;
    }
}
