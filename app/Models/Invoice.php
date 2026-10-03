<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'delivery_id',
        'store_id',
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

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
        ];
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
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $latest = static::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($latest) {
            $lastSequence = (int) substr($latest, -4);
            $nextSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '0001';
        }

        return $prefix . $nextSequence;
    }
}
