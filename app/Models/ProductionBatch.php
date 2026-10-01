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
        'batch_number',
        'recipe_id',
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

    protected function casts(): array
    {
        return [
            'planned_qty' => 'integer',
            'actual_qty_good' => 'integer',
            'actual_qty_bad' => 'integer',
            'total_material_cost' => 'decimal:2',
            'unit_cost_produced' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function generateBatchNumber(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', now()->toDateString())->count() + 1;
        return sprintf('PRD-%s-%04d', $date, $count);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    public function formattedTotalCost(): string
    {
        return 'Rp ' . number_format((float) $this->total_material_cost, 0, ',', '.');
    }

    public function formattedUnitCost(): string
    {
        return 'Rp ' . number_format((float) $this->unit_cost_produced, 0, ',', '.');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }
}
