<?php

namespace App\Livewire\Admin;

use App\Models\Partner;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Picqer\Barcode\BarcodeGeneratorSVG;

new #[Layout('layouts.admin'), Title('Mitra Toko & Barcode - Halala Food')] class extends Component
{
    /**
     * Save or Update a Partner
     */
    public function savePartner(?int $id, array $data): array
    {
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $type = trim($data['type'] ?? 'grocery_store');
        $contactPerson = !empty($data['contact_person']) ? trim($data['contact_person']) : null;
        $phone = !empty($data['phone']) ? Partner::normalizePhone(trim($data['phone'])) : null;
        $whatsappNumber = !empty($data['whatsapp_number']) ? Partner::normalizePhone(trim($data['whatsapp_number'])) : ($phone ?: null);
        $address = !empty($data['address']) ? trim($data['address']) : null;
        $city = !empty($data['city']) ? trim($data['city']) : null;
        $paymentTermDays = isset($data['payment_term_days']) ? (int) $data['payment_term_days'] : 0;
        $creditLimit = isset($data['credit_limit']) ? (float) $data['credit_limit'] : 0.00;
        $currentReceivable = isset($data['current_receivable']) ? (float) $data['current_receivable'] : 0.00;
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;
        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        if (empty($code) && !$id) {
            $code = Partner::generatePartnerCode();
        }

        $rules = [
            'code' => ['required', 'string', 'max:30', Rule::unique('partners', 'code')->ignore($id)],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'type' => ['required', 'string', Rule::in(['supermarket', 'grocery_store', 'distributor', 'retail_reseller'])],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:25'],
            'whatsapp_number' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'payment_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'current_receivable' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ];

        $validator = validator(
            [
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'contact_person' => $contactPerson,
                'phone' => $phone,
                'whatsapp_number' => $whatsappNumber,
                'address' => $address,
                'city' => $city,
                'payment_term_days' => $paymentTermDays,
                'credit_limit' => $creditLimit,
                'current_receivable' => $currentReceivable,
                'notes' => $notes,
                'is_active' => $isActive,
            ],
            $rules,
            [
                'code.required' => 'Kode mitra wajib diisi.',
                'code.unique' => 'Kode mitra sudah digunakan oleh mitra lain.',
                'name.required' => 'Nama mitra toko wajib diisi.',
                'name.min' => 'Nama mitra minimal 3 karakter.',
                'type.required' => 'Pilih kategori tipe mitra toko.',
                'type.in' => 'Kategori tipe mitra tidak valid.',
                'payment_term_days.min' => 'Jatuh tempo pembayaran minimal 0 hari (Tunai).',
                'credit_limit.min' => 'Batas limit piutang tidak boleh bernilai negatif.',
                'current_receivable.min' => 'Piutang berjalan tidak boleh bernilai negatif.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ];
        }

        try {
            DB::transaction(function () use (
                $id, $code, $name, $type, $contactPerson, $phone, $whatsappNumber,
                $address, $city, $paymentTermDays, $creditLimit, $currentReceivable, $notes, $isActive
            ) {
                Partner::updateOrCreate(
                    ['id' => $id],
                    [
                        'code' => $code,
                        'name' => $name,
                        'type' => $type,
                        'contact_person' => $contactPerson,
                        'phone' => $phone,
                        'whatsapp_number' => $whatsappNumber,
                        'address' => $address,
                        'city' => $city,
                        'payment_term_days' => $paymentTermDays,
                        'credit_limit' => $creditLimit,
                        'current_receivable' => $currentReceivable,
                        'notes' => $notes,
                        'is_active' => $isActive,
                    ]
                );
            });

            return [
                'success' => true,
                'message' => $id ? 'Data mitra toko berhasil diperbarui.' : 'Mitra toko baru berhasil ditambahkan.',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Gagal menyimpan data mitra: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Delete a Partner
     */
    public function deletePartner(int $id): array
    {
        try {
            $partner = Partner::findOrFail($id);

            if ((float) $partner->current_receivable > 0) {
                return [
                    'success' => false,
                    'message' => 'Tidak dapat menghapus mitra yang masih memiliki saldo piutang berjalan (' . $partner->formattedReceivable() . ').',
                ];
            }

            $partner->delete();

            return [
                'success' => true,
                'message' => 'Mitra toko berhasil dihapus.',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Gagal menghapus mitra: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Update Variant Barcode directly from Generator Tab
     */
    public function updateVariantBarcode(int $variantId, string $barcode): array
    {
        $barcode = trim($barcode);

        if (empty($barcode)) {
            return [
                'success' => false,
                'message' => 'Kode barcode tidak boleh kosong.',
            ];
        }

        try {
            $variant = ProductVariant::findOrFail($variantId);
            $variant->update(['barcode' => $barcode]);

            return [
                'success' => true,
                'message' => "Kode barcode untuk varian '{$variant->name}' berhasil disimpan.",
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Gagal memperbarui barcode: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Helper to render SVG Barcode
     */
    public function getBarcodeSvg(string $code): string
    {
        $code = trim($code);
        if (empty($code)) {
            return '';
        }

        try {
            $generator = new BarcodeGeneratorSVG();
            // If numeric and 12 or 13 digits, use EAN-13, otherwise CODE-128
            $type = (strlen($code) === 13 && ctype_digit($code)) ? $generator::TYPE_EAN_13 : $generator::TYPE_CODE_128;
            return $generator->getBarcode($code, $type, 2, 45);
        } catch (\Throwable $e) {
            // Fallback to CODE-128 if EAN checksum fails
            try {
                $generator = new BarcodeGeneratorSVG();
                return $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 45);
            } catch (\Throwable $e2) {
                return '';
            }
        }
    }

    public function with(): array
    {
        $partners = Partner::orderBy('name')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'type' => $p->type,
                'type_label' => $p->typeLabel(),
                'contact_person' => $p->contact_person,
                'phone' => $p->phone,
                'formatted_phone' => $p->formattedPhone(),
                'whatsapp_number' => $p->whatsapp_number,
                'whatsapp_url' => $p->whatsappUrl(),
                'address' => $p->address,
                'city' => $p->city,
                'payment_term_days' => $p->payment_term_days,
                'payment_term_label' => $p->paymentTermLabel(),
                'credit_limit' => (float) $p->credit_limit,
                'formatted_credit_limit' => $p->formattedCreditLimit(),
                'current_receivable' => (float) $p->current_receivable,
                'formatted_receivable' => $p->formattedReceivable(),
                'is_over_limit' => $p->isOverLimit(),
                'notes' => $p->notes,
                'is_active' => (bool) $p->is_active,
                'created_at' => $p->created_at?->format('d M Y'),
            ];
        });

        $productVariants = ProductVariant::with('product')
            ->where('is_active', true)
            ->orderBy('product_id')
            ->orderBy('weight_grams')
            ->get()
            ->map(function ($v) {
                $barcodeSvg = $v->barcode ? $this->getBarcodeSvg($v->barcode) : '';
                return [
                    'id' => $v->id,
                    'product_id' => $v->product_id,
                    'product_name' => $v->product?->name ?? 'Produk',
                    'variant_name' => $v->name,
                    'full_name' => ($v->product?->name ?? 'Produk') . ' - ' . $v->name,
                    'packaging_type' => $v->packaging_type,
                    'pcs_per_package' => $v->pcs_per_package,
                    'weight_grams' => (float) $v->weight_grams,
                    'barcode' => $v->barcode ?: ('899' . str_pad((string) $v->id, 10, '0', STR_PAD_LEFT)),
                    'barcode_svg' => $barcodeSvg ?: $this->getBarcodeSvg('899' . str_pad((string) $v->id, 10, '0', STR_PAD_LEFT)),
                    'sku_code' => $v->sku_code,
                    'wholesale_price' => (float) $v->wholesale_price,
                    'formatted_wholesale_price' => $v->formattedWholesalePrice(),
                    'retail_price' => (float) $v->retail_price,
                    'formatted_retail_price' => $v->formattedRetailPrice(),
                    'stock_qty' => $v->stock_qty,
                ];
            });

        // Summary Metrics
        $totalPartners = $partners->count();
        $supermarketCount = $partners->where('type', 'supermarket')->count();
        $groceryCount = $partners->where('type', 'grocery_store')->count();
        $totalReceivables = $partners->sum('current_receivable');

        return [
            'partners' => $partners,
            'productVariants' => $productVariants,
            'totalPartners' => $totalPartners,
            'supermarketCount' => $supermarketCount,
            'groceryCount' => $groceryCount,
            'totalReceivables' => $totalReceivables,
            'formattedTotalReceivables' => 'Rp ' . number_format($totalReceivables, 0, ',', '.'),
        ];
    }
};
