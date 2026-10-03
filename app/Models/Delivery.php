<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_number',
        'store_id',
        'courier_id',
        'created_by',
        'delivery_date',
        'status',
        'dispatched_at',
        'delivered_at',
        'recipient_name',
        'recipient_role',
        'recipient_phone',
        'proof_image',
        'signature_data',
        'notes',
        'total_items',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'total_items' => 'integer',
            'total_amount' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'diproses' => 'Menunggu Pengambilan',
            'dikirim' => 'Sedang Dikirim',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['diproses']);
    }

    public static function generateDeliveryNumber(): string
    {
        $prefix = 'SJ-' . now()->format('Ymd') . '-';
        $latest = static::where('delivery_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('delivery_number');

        if ($latest) {
            $lastSequence = (int) substr($latest, -4);
            $nextSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '0001';
        }

        return $prefix . $nextSequence;
    }
}
