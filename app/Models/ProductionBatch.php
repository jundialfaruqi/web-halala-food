<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'product_id',
        'user_id',
        'planned_qty',
        'actual_qty_good',
        'actual_qty_bad',
        'total_material_cost',
        'unit_cost_produced',
        'status',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'planned_qty' => 'integer',
        'actual_qty_good' => 'integer',
        'actual_qty_bad' => 'integer',
        'total_material_cost' => 'float',
        'unit_cost_produced' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batchMaterials(): HasMany
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    /**
     * Generate unique batch code format: PRD-YYYYMMDD-0001
     */
    public static function generateBatchCode(): string
    {
        $today = now()->format('Ymd');
        $prefix = "PRD-{$today}-";

        $lastBatch = static::where('batch_code', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        if (! $lastBatch) {
            return "{$prefix}0001";
        }

        $lastNumber = (int) substr($lastBatch->batch_code, -4);
        $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$nextNumber}";
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return 'Rp '.number_format($this->total_material_cost, 0, ',', '.');
    }

    public function getFormattedUnitCostAttribute(): string
    {
        return 'Rp '.number_format($this->unit_cost_produced, 2, ',', '.');
    }
}
