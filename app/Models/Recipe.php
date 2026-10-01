<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'name',
        'batch_output_qty',
        'labor_cost',
        'overhead_cost',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'batch_output_qty' => 'integer',
            'labor_cost' => 'decimal:2',
            'overhead_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    /**
     * Calculate total cost of raw materials in one batch
     */
    public function calculateTotalMaterialCost(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $costPerUnit = (float) ($item->rawMaterial?->average_cost ?? 0);
            $total += ($costPerUnit * (float) $item->quantity_required);
        }
        return $total;
    }

    /**
     * Calculate estimated COGM / HPP per unit finished goods
     */
    public function calculateEstimatedHpp(): float
    {
        if ($this->batch_output_qty <= 0) {
            return 0;
        }

        $totalCost = $this->calculateTotalMaterialCost() + (float) $this->labor_cost + (float) $this->overhead_cost;
        return $totalCost / $this->batch_output_qty;
    }

    public function formattedEstimatedHpp(): string
    {
        return 'Rp ' . number_format($this->calculateEstimatedHpp(), 0, ',', '.');
    }
}
