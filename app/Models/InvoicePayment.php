<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'payment_number',
        'user_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected $appends = [
        'method_label',
        'payment_date_formatted',
    ];

    public function getPaymentDateFormattedAttribute(): string
    {
        return $this->payment_date ? $this->payment_date->translatedFormat('d M Y') : '-';
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'tunai' => 'Tunai (Cash)',
            'transfer_bank' => 'Transfer Bank',
            'qris' => 'QRIS',
            default => ucfirst(str_replace('_', ' ', $this->payment_method)),
        };
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'PAY-' . now()->format('Ymd') . '-';
        $latest = static::where('payment_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('payment_number');

        if ($latest) {
            $lastSequence = (int) substr($latest, -4);
            $nextSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '0001';
        }

        return $prefix . $nextSequence;
    }
}
