<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'contact_person',
        'phone',
        'whatsapp_number',
        'address',
        'city',
        'payment_term_days',
        'credit_limit',
        'current_receivable',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
            'credit_limit' => 'decimal:2',
            'current_receivable' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function generatePartnerCode(): string
    {
        $count = static::count() + 1;
        return sprintf('MTR-%03d', $count);
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

    public function typeLabel(): string
    {
        return match ($this->type) {
            'supermarket' => 'Supermarket B2B',
            'grocery_store' => 'Toko Kelontong Harian',
            'distributor' => 'Distributor / Agen',
            'retail_reseller' => 'Reseller Eceran',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function paymentTermLabel(): string
    {
        if ($this->payment_term_days <= 0) {
            return 'Tunai (COD)';
        }
        return "Tempo {$this->payment_term_days} Hari";
    }

    public function formattedCreditLimit(): string
    {
        return 'Rp ' . number_format((float) $this->credit_limit, 0, ',', '.');
    }

    public function formattedReceivable(): string
    {
        return 'Rp ' . number_format((float) $this->current_receivable, 0, ',', '.');
    }

    public function isOverLimit(): bool
    {
        return (float) $this->credit_limit > 0 && (float) $this->current_receivable >= (float) $this->credit_limit;
    }
}
