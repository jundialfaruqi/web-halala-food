<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterialPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'supplier_name',
        'purchase_date',
        'total_amount',
        'payment_method',
        'payment_status',
        'paid_amount',
        'paid_at',
        'paid_account_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paidAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'paid_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RawMaterialPurchaseItem::class, 'purchase_id');
    }

    public function isTempo(): bool
    {
        return strtolower($this->payment_method) === 'tempo';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'lunas';
    }

    public function getRemainingDebtAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    /**
     * Generate the next sequential purchase number.
     * Format: BELI-YYYYMMDD-XXXX
     */
    public static function generatePurchaseNumber(): string
    {
        $prefix = 'BELI-' . date('Ymd') . '-';
        $latest = static::where('purchase_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $latest) {
            return $prefix . '0001';
        }

        $sequence = (int) substr($latest->purchase_number, -4);

        return $prefix . str_pad((string) ($sequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
