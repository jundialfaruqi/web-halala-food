<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'packaging_type',
        'pcs_per_package',
        'weight_grams',
        'barcode',
        'sku_code',
        'base_cost',
        'wholesale_price',
        'retail_price',
        'stock_qty',
        'min_stock_alert',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pcs_per_package' => 'integer',
            'weight_grams' => 'decimal:2',
            'base_cost' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'stock_qty' => 'integer',
            'min_stock_alert' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function defaultRecipe(): HasOne
    {
        return $this->hasOne(Recipe::class)->where('is_active', true)->latestOfMany();
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->min_stock_alert;
    }

    public function formattedBaseCost(): string
    {
        return 'Rp ' . number_format((float) $this->base_cost, 0, ',', '.');
    }

    public function formattedWholesalePrice(): string
    {
        return 'Rp ' . number_format((float) $this->wholesale_price, 0, ',', '.');
    }

    public function formattedRetailPrice(): string
    {
        return 'Rp ' . number_format((float) $this->retail_price, 0, ',', '.');
    }
}
