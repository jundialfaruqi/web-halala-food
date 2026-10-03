<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_name',
        'phone',
        'address',
        'latitude',
        'longitude',
        'route',
        'is_active',
        'notes',
        'photo',
    ];

    protected $appends = ['photo_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function ($store) {
            if ($store->photo && Storage::disk('public')->exists($store->photo)) {
                Storage::disk('public')->delete($store->photo);
            }
        });
    }

    /**
     * Get public URL for the store photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
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
            return null; // Foto toko mitra opsional
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

                // Folder sesuai nama fitur: foto-toko
                // Nama file: tanggal, nama toko (slug), dan kode random acak
                $date = now()->format('Y-m-d');
                $storeSlug = Str::slug($this->name ?: 'toko');
                $randomCode = Str::lower(Str::random(8));
                $filename = "{$date}_{$storeSlug}_{$randomCode}.{$ext}";
                $path = 'foto-toko/' . $filename;

                Storage::disk('public')->put($path, $decoded);

                $this->photo = $path;
                $this->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Scope a query to only include active stores.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
