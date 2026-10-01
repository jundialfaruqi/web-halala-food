<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'due_date',
        'status',
        'payment_status',
        'total_amount',
        'received_at',
        'receiver_user_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public static function generatePoNumber(): string
    {
        $today = Carbon::now()->format('Ymd');
        $prefix = "PO-{$today}-";

        $lastPo = static::where('po_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPo) {
            $lastSeq = (int) substr($lastPo->po_number, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'ordered' => 'Dipesan (Menunggu Barang)',
            'received' => 'Diterima di Gudang',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'Belum Lunas (Utang)',
            'partial' => 'Sebagian',
            'paid' => 'Lunas',
            default => ucfirst($this->payment_status),
        };
    }

    public function formattedTotalAmount(): string
    {
        return 'Rp ' . number_format((float) $this->total_amount, 0, ',', '.');
    }

    public function totalItemsCount(): int
    {
        return (int) $this->items()->count();
    }
}
