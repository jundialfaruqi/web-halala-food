<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'reference_type',
        'reference_id',
        'type',
        'quantity',
        'current_stock',
        'cost_per_unit',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
        ];
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedCost(): string
    {
        return 'Rp ' . number_format((float) $this->cost_per_unit, 0, ',', '.');
    }
}
