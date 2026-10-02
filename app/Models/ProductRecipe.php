<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'raw_material_id',
        'quantity_needed',
    ];

    protected function casts(): array
    {
        return [
            'quantity_needed' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * Subtotal cost of this ingredient per unit product.
     */
    public function getSubtotalCostAttribute(): float
    {
        $cost = $this->rawMaterial?->cost_per_unit ?? 0;
        return round((float) $this->quantity_needed * (float) $cost, 2);
    }
}
