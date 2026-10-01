<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'phone',
        'whatsapp_number',
        'email',
        'address',
        'city',
        'payment_terms_days',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public static function generateSupplierCode(): string
    {
        $count = static::count() + 1;
        return sprintf('SPL-%03d', $count);
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    public function formattedPhone(): ?string
    {
        if (empty($this->phone)) {
            return null;
        }

        $phone = $this->phone;
        if (str_starts_with($phone, '62')) {
            $prefix = '+62';
            $number = substr($phone, 2);
        } else {
            $prefix = '';
            $number = $phone;
        }

        if (strlen($number) >= 9) {
            $part1 = substr($number, 0, 3);
            $part2 = substr($number, 3, 4);
            $part3 = substr($number, 7);
            return trim("{$prefix} {$part1}-{$part2}-{$part3}");
        }

        return $prefix ? "{$prefix} {$number}" : $number;
    }

    public function whatsappUrl(): ?string
    {
        $wa = $this->whatsapp_number ?: $this->phone;
        if (empty($wa)) {
            return null;
        }

        $normalized = static::normalizePhone($wa);
        return "https://wa.me/{$normalized}";
    }

    public function paymentTermLabel(): string
    {
        if ($this->payment_terms_days <= 0) {
            return 'Tunai (COD)';
        }
        return "Tempo {$this->payment_terms_days} Hari";
    }

    public function totalPurchasesAmount(): float
    {
        return (float) $this->purchaseOrders()->where('status', 'received')->sum('total_amount');
    }

    public function formattedTotalPurchases(): string
    {
        return 'Rp ' . number_format($this->totalPurchasesAmount(), 0, ',', '.');
    }
}
