<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_number',
        'store_id',
        'courier_id',
        'created_by',
        'delivery_date',
        'status',
        'dispatched_at',
        'delivered_at',
        'recipient_name',
        'recipient_role',
        'recipient_phone',
        'proof_image',
        'signature_data',
        'notes',
        'total_items',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'total_items' => 'integer',
            'total_amount' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Generate an invoice for this delivery if not already exists.
     */
    public function generateInvoice(?int $userId = null): ?Invoice
    {
        $existingInvoice = $this->invoice()->first();
        if ($existingInvoice) {
            return $existingInvoice;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($userId) {
            $this->loadMissing(['items', 'store']);

            $subtotal = (float) ($this->total_amount ?: $this->items->sum('subtotal'));
            $discount = 0.00;
            $totalAmount = max(0.0, $subtotal - $discount);

            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'delivery_id' => $this->id,
                'store_id' => $this->store_id,
                'created_by' => $userId ?: (\Illuminate\Support\Facades\Auth::id() ?: $this->created_by),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'remaining_balance' => $totalAmount,
                'status' => 'belum_dibayar',
                'notes' => "Faktur otomatis dari Surat Jalan {$this->delivery_number}",
            ]);

            foreach ($this->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'delivered_quantity' => $item->quantity,
                    'remaining_quantity' => 0,
                    'damaged_quantity' => 0,
                    'returned_quantity' => 0,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'notes' => $item->notes,
                ]);
            }

            return $invoice;
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'diproses' => 'Menunggu Pengambilan',
            'dikirim' => 'Sedang Dikirim',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['diproses']);
    }

    public static function generateDeliveryNumber(): string
    {
        $prefix = 'SJ-' . now()->format('Ymd') . '-';
        $latest = static::where('delivery_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('delivery_number');

        if ($latest) {
            $lastSequence = (int) substr($latest, -4);
            $nextSequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '0001';
        }

        return $prefix . $nextSequence;
    }

    /**
     * Validate base64 photo data URL format, mime, and size.
     */
    public static function validatePhotoBase64(?string $photoData): ?string
    {
        if (empty($photoData)) {
            return null;
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
     * Store and update proof photo from base64 DataURL (WebP or JPEG).
     */
    public function updateProofPhotoFromBase64(?string $photoData): bool
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
                if ($this->proof_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->proof_image)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($this->proof_image);
                }

                $date = now()->format('Y-m-d');
                $deliverySlug = \Illuminate\Support\Str::slug($this->delivery_number ?: 'sj');
                $randomCode = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
                $filename = "{$date}_{$deliverySlug}_{$randomCode}.{$ext}";
                $path = 'delivery-proofs/' . $filename;

                \Illuminate\Support\Facades\Storage::disk('public')->put($path, $decoded);

                $this->proof_image = $path;
                $this->save();

                return true;
            }
        }

        return false;
    }
}
