<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'legal_name',
        'tagline',
        'phone',
        'email',
        'address',
        'bank_accounts',
        'invoice_notes',
        'delivery_notes',
    ];

    protected function casts(): array
    {
        return [
            'bank_accounts' => 'array',
        ];
    }

    /**
     * Retrieve the current business settings, or create a default record if none exists.
     */
    public static function getSettings(): self
    {
        $settings = static::first();

        if (! $settings) {
            $settings = static::create([
                'company_name' => 'HALALA FOOD',
                'legal_name' => 'CV Halala Food Berkah',
                'tagline' => 'Produksi & Distribusi Makanan Ringan Halal',
                'phone' => '0812-9988-7766',
                'email' => 'kontak@halala-food.id',
                'address' => 'Malang, Jawa Timur',
                'bank_accounts' => [
                    [
                        'bank_name' => 'BCA',
                        'account_number' => '816-1234-5678',
                        'account_name' => 'Halala Food CV',
                    ],
                    [
                        'bank_name' => 'Mandiri',
                        'account_number' => '144-00-9876-5432',
                        'account_name' => 'Halala Food CV',
                    ],
                ],
                'invoice_notes' => 'Pembayaran dapat ditransfer ke rekening resmi terdaftar atau diserahkan tunai kepada petugas penagihan resmi.',
                'delivery_notes' => 'Mohon periksa fisik kemasan dan jumlah produk saat serah terima dilakukan.',
            ]);
        }

        return $settings;
    }
}
