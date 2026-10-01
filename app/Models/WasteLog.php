<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'raw_material_id',
        'quantity',
        'cost_loss',
        'reason',
        'notes',
        'reported_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost_loss' => 'decimal:2',
        ];
    }

    public static function generateWasteCode(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', now()->toDateString())->count() + 1;
        return sprintf('WST-%s-%04d', $date, $count);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function formattedCostLoss(): string
    {
        return 'Rp ' . number_format((float) $this->cost_loss, 0, ',', '.');
    }
}
