<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit_id',
        'unit',
        'consignment_price',
        'retail_price',
        'stock_ready',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'consignment_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'stock_ready' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function unitModel(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    /**
     * Calculate Bill of Material (BOM) material cost per 1 unit product.
     */
    public function getMaterialCostAttribute(): float
    {
        return (float) $this->recipes->sum(function ($r) {
            $costPerUnit = $r->rawMaterial?->cost_per_unit ?? 0;
            return (float) $r->quantity_needed * (float) $costPerUnit;
        });
    }

    /**
     * Calculate Gross Margin per unit based on consignment price.
     */
    public function getConsignmentMarginAttribute(): float
    {
        $cost = $this->material_cost;
        $price = (float) $this->consignment_price;
        return $price > 0 ? round((($price - $cost) / $price) * 100, 1) : 0.0;
    }
}
