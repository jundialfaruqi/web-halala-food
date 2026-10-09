<?php

namespace App\Models;

use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit_id',
        'unit',
        'stock',
        'min_stock',
        'cost_per_unit',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'float',
            'min_stock' => 'float',
            'cost_per_unit' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function ($material) {
            AccountingService::syncMaterialAccount($material);
        });

        static::deleted(function ($material) {
            AccountingService::removeMaterialAccount($material);
        });
    }

    public function unitModel(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }

    public function batchMaterials(): HasMany
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(RawMaterialPurchaseItem::class);
    }

    /**
     * Get the active display unit short name.
     */
    public function getDisplayUnitAttribute(): string
    {
        return $this->unitModel?->short_name ?? $this->unit ?? 'gram';
    }

    /**
     * Stock status indicator: safe, warning, danger
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'danger'; // Habis
        }

        if ($this->stock <= $this->min_stock) {
            return 'warning'; // Menipis
        }

        return 'safe'; // Aman
    }
}
