<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionBatchMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_batch_id',
        'raw_material_id',
        'unit_name',
        'planned_qty',
        'actual_used_qty',
        'cost_per_unit',
        'subtotal_cost',
    ];

    protected $casts = [
        'planned_qty' => 'float',
        'actual_used_qty' => 'float',
        'cost_per_unit' => 'float',
        'subtotal_cost' => 'float',
    ];

    public function productionBatch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp '.number_format($this->subtotal_cost, 0, ',', '.');
    }
}
