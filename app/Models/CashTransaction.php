<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'account_id',
        'type',
        'category',
        'amount',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $appends = [
        'materials_summary',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(RawMaterialPurchase::class, 'reference_id');
    }

    public function invoicePayment(): BelongsTo
    {
        return $this->belongsTo(InvoicePayment::class, 'reference_id');
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'reference_id');
    }

    public function getResolvedPurchaseAttribute(): ?RawMaterialPurchase
    {
        if (! in_array($this->reference_type, ['purchase', 'purchase_payment'])) {
            return null;
        }

        return $this->purchase;
    }

    public function getResolvedInvoicePaymentAttribute(): ?InvoicePayment
    {
        if ($this->reference_type !== 'invoice_payment') {
            return null;
        }

        return $this->invoicePayment;
    }

    public function getResolvedFixedAssetAttribute(): ?FixedAsset
    {
        if ($this->reference_type !== 'fixed_asset_purchase') {
            return null;
        }

        return $this->fixedAsset;
    }

    /**
     * Get a formatted string listing all raw material names and quantities for purchase-related cash transactions.
     */
    public function getMaterialsSummaryAttribute(): ?string
    {
        if (! in_array($this->reference_type, ['purchase', 'purchase_payment'])) {
            return null;
        }

        $purchase = $this->relationLoaded('purchase')
            ? $this->purchase
            : ($this->reference_id ? RawMaterialPurchase::with('items.rawMaterial.unitModel')->find($this->reference_id) : null);

        if (! $purchase || $purchase->items->isEmpty()) {
            return null;
        }

        return $purchase->items->map(function ($item) {
            $name = $item->rawMaterial?->name ?? 'Bahan';
            $unit = $item->rawMaterial?->display_unit ?? '';
            $qty = (float) $item->quantity;
            $formattedQty = number_format($qty, (floor($qty) == $qty ? 0 : 2), ',', '.');

            return "{$name} ({$formattedQty} {$unit})";
        })->filter()->implode(', ');
    }
}
