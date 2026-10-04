<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit_id',
        'unit',
        'consignment_price',
        'retail_price',
        'stock_ready',
        'description',
        'photo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'consignment_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'stock_ready' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function unitModel(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    /**
     * Calculate Bill of Material (BOM) material cost per 1 unit product.
     */
    public function getMaterialCostAttribute(): float
    {
        return (float) $this->recipes->sum(function ($r) {
            $costPerUnit = $r->rawMaterial?->cost_per_unit ?? 0;
            return (float) $r->quantity_needed * (float) $costPerUnit;
        });
    }

    /**
     * Calculate Gross Margin per unit based on consignment price.
     */
    public function getConsignmentMarginAttribute(): float
    {
        $cost = $this->material_cost;
        $price = (float) $this->consignment_price;
        return $price > 0 ? round((($price - $cost) / $price) * 100, 1) : 0.0;
    }

    /**
     * Get the full URL to the product photo, or null if no photo exists.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo)) {
            return null;
        }

        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        return asset('storage/' . $this->photo);
    }

    /**
     * Validate photo base64 data for maximum upload size and allowed formats.
     *
     * @param string|null $photoData
     * @return string|null Error message if invalid, null if valid
     */
    public static function validatePhotoBase64(?string $photoData): ?string
    {
        if (empty($photoData)) {
            return null; // Foto produk opsional
        }

        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/i', $photoData, $matches)) {
            return 'Format file foto tidak didukung. Hanya file JPG, JPEG, PNG, atau WEBP yang diperbolehkan.';
        }

        $base64 = substr($photoData, strpos($photoData, ',') + 1);
        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            return 'Format data gambar tidak valid atau rusak.';
        }

        // Validasi maksimal upload 10MB di backend
        $maxBytes = 10 * 1024 * 1024; // 10MB
        if (strlen($decoded) > $maxBytes) {
            return 'Ukuran file foto melebihi batas maksimal 10MB.';
        }

        return null;
    }

    /**
     * Store and update photo from base64 DataURL (WebP or JPEG).
     */
    public function updatePhotoFromBase64(?string $photoData): bool
    {
        if (empty($photoData)) {
            return false;
        }

        $validationError = self::validatePhotoBase64($photoData);
        if ($validationError !== null) {
            return false;
        }

        if (preg_match('/^data:image\/(\w+);base64,/i', $photoData, $matches)) {
            $ext = strtolower($matches[1]);
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }

            $base64 = substr($photoData, strpos($photoData, ',') + 1);
            $decoded = base64_decode($base64, true);

            if ($decoded !== false) {
                if ($this->photo && Storage::disk('public')->exists($this->photo)) {
                    Storage::disk('public')->delete($this->photo);
                }

                // Folder sesuai nama fitur: foto-produk
                // Nama file: tanggal, nama produk (slug), dan kode random acak
                $date = now()->format('Y-m-d');
                $productSlug = Str::slug($this->name ?: 'produk');
                $randomCode = Str::lower(Str::random(8));
                $filename = "{$date}_{$productSlug}_{$randomCode}.{$ext}";
                $path = 'foto-produk/' . $filename;

                Storage::disk('public')->put($path, $decoded);

                $this->photo = $path;
                $this->save();

                return true;
            }
        }

        return false;
    }
}
