<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'raw_material_id',
        'qty_ordered',
        'qty_received',
        'unit_price',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty_ordered' => 'decimal:2',
            'qty_received' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function formattedUnitPrice(): string
    {
        return 'Rp ' . number_format((float) $this->unit_price, 0, ',', '.');
    }

    public function formattedSubtotal(): string
    {
        return 'Rp ' . number_format((float) $this->subtotal, 0, ',', '.');
    }
}
