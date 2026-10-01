<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'stock_qty',
        'min_stock_alert',
        'average_cost',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stock_qty' => 'decimal:2',
            'min_stock_alert' => 'decimal:2',
            'average_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->min_stock_alert;
    }

    public function formattedAverageCost(): string
    {
        return 'Rp ' . number_format((float) $this->average_cost, 0, ',', '.');
    }
}
