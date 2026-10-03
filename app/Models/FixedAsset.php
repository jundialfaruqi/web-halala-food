<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'asset_code',
        'purchase_date',
        'purchase_price',
        'book_value',
        'condition',
        'location',
        'notes',
        'journal_entry_id',
    ];

    /**
     * Auto-generate asset code: AST-001, AST-002, etc.
     */
    public static function generateAssetCode(): string
    {
        $last = static::orderByDesc('id')->value('asset_code');

        if ($last && preg_match('/AST-(\d+)$/', $last, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = static::count() + 1;
        }

        return 'AST-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'float',
        'book_value' => 'float',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public static function categories(): array
    {
        return [
            'mesin'      => 'Mesin Produksi',
            'peralatan'  => 'Peralatan Dapur & Alat',
            'kendaraan'  => 'Kendaraan',
            'inventaris' => 'Inventaris Kantor',
            'bangunan'   => 'Bangunan / Sewa Tempat',
            'lainnya'    => 'Lainnya',
        ];
    }

    public static function conditions(): array
    {
        return [
            'baik'         => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat'  => 'Rusak Berat',
            'tidak_aktif'  => 'Tidak Aktif / Disimpan',
        ];
    }
}
