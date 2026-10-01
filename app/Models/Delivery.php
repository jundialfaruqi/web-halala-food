<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_number',
        'partner_id',
        'courier_id',
        'status',
        'total_amount',
        'receiver_name',
        'receiver_phone',
        'notes',
        'dispatched_at',
        'delivered_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public static function generateDeliveryNumber(): string
    {
        $today = Carbon::now()->format('Ymd');
        $prefix = "SJ-{$today}-";

        $lastDelivery = static::where('delivery_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastDelivery) {
            $lastSeq = (int) substr($lastDelivery->delivery_number, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft (Siap Dikemas)',
            'on_the_way' => 'Dalam Perjalanan (Kurir)',
            'delivered' => 'Telah Diterima Toko',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function formattedTotalAmount(): string
    {
        return 'Rp ' . number_format((float) $this->total_amount, 0, ',', '.');
    }

    public function totalQuantitySent(): int
    {
        return (int) $this->items()->sum('qty_sent');
    }

    public function totalQuantityAccepted(): int
    {
        return (int) $this->items()->sum('qty_accepted');
    }

    public function totalQuantityReturned(): int
    {
        return (int) $this->items()->sum('qty_returned');
    }
}
