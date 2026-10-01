<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'raw_material_id',
        'quantity_required',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'decimal:2',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function calculateSubtotalCost(): float
    {
        $costPerUnit = (float) ($this->rawMaterial?->average_cost ?? 0);
        return $costPerUnit * (float) $this->quantity_required;
    }

    public function formattedSubtotalCost(): string
    {
        return 'Rp ' . number_format($this->calculateSubtotalCost(), 0, ',', '.');
    }
}
