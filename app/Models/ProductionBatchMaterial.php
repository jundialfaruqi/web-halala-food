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
        'planned_qty',
        'actual_used_qty',
        'cost_per_unit',
        'subtotal_cost',
    ];

    protected function casts(): array
    {
        return [
            'planned_qty' => 'decimal:2',
            'actual_used_qty' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'subtotal_cost' => 'decimal:2',
        ];
    }

    public function productionBatch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function formattedSubtotalCost(): string
    {
        return 'Rp ' . number_format((float) $this->subtotal_cost, 0, ',', '.');
    }
}
