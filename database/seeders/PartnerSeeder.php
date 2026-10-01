<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            [
                'code' => 'MTR-001',
                'name' => 'Supermarket Tip Top Rawamangun',
                'type' => 'supermarket',
                'contact_person' => 'Bpk. Hendra Wijaya (Procurement Manager)',
                'phone' => '081211223344',
                'whatsapp_number' => '081211223344',
                'address' => 'Jl. Balai Pustaka Timur No. 35, Rawamangun, Pulo Gadung',
                'city' => 'Jakarta Timur',
                'payment_term_days' => 30,
                'credit_limit' => 15000000.00,
                'current_receivable' => 4500000.00,
                'notes' => 'Mitra supermarket jaringan. Pengiriman rutin tiap hari Selasa & Kamis. Invoice tempo 30 hari.',
                'is_active' => true,
            ],
            [
                'code' => 'MTR-002',
                'name' => 'Supermarket Naga Swalayan Pondok Gede',
                'type' => 'supermarket',
                'contact_person' => 'Ibu Ratna Susanti (Purchasing)',
                'phone' => '081388776655',
                'whatsapp_number' => '081388776655',
                'address' => 'Jl. Raya Pondok Gede No. 88, Jatiwaringin',
                'city' => 'Bekasi',
                'payment_term_days' => 14,
                'credit_limit' => 10000000.00,
                'current_receivable' => 2200000.00,
                'notes' => 'Supermarket grosir & eceran. Pengiriman berkala kemasan pouch 200g.',
                'is_active' => true,
            ],
            [
                'code' => 'MTR-003',
                'name' => 'Toko Berkah Kelontong Harian',
                'type' => 'grocery_store',
                'contact_person' => 'H. Ahmad Syukri',
                'phone' => '085699887711',
                'whatsapp_number' => '085699887711',
                'address' => 'Jl. Percetakan Negara IX No. 12, Cempaka Putih',
                'city' => 'Jakarta Pusat',
                'payment_term_days' => 7,
                'credit_limit' => 3000000.00,
                'current_receivable' => 650000.00,
                'notes' => 'Toko kelontong langganan ramai warga sekitar. Pembelian rutin toples & eceran.',
                'is_active' => true,
            ],
            [
                'code' => 'MTR-004',
                'name' => 'Toko Sumber Rezeki Pasar Minggu',
                'type' => 'grocery_store',
                'contact_person' => 'Ibu Yanti',
                'phone' => '087822334455',
                'whatsapp_number' => '087822334455',
                'address' => 'Kios Blok B No. 14 Pasar Minggu',
                'city' => 'Jakarta Selatan',
                'payment_term_days' => 0,
                'credit_limit' => 2000000.00,
                'current_receivable' => 0.00,
                'notes' => 'Pembayaran selalu Tunai (COD) saat kurir antar barang.',
                'is_active' => true,
            ],
            [
                'code' => 'MTR-005',
                'name' => 'CV Maju Jaya Distribusi',
                'type' => 'distributor',
                'contact_person' => 'Bpk. Bambang Pamungkas',
                'phone' => '081199882233',
                'whatsapp_number' => '081199882233',
                'address' => 'Kawasan Pergudangan Cipondoh Blok C5 No. 9',
                'city' => 'Tangerang',
                'payment_term_days' => 30,
                'credit_limit' => 25000000.00,
                'current_receivable' => 8000000.00,
                'notes' => 'Distributor utama wilayah Banten dan Tangerang Raya. Order per karton besar.',
                'is_active' => true,
            ],
            [
                'code' => 'MTR-006',
                'name' => 'Warung Cemilan Bu Siti',
                'type' => 'retail_reseller',
                'contact_person' => 'Ibu Siti Khodijah',
                'phone' => '081900112233',
                'whatsapp_number' => '081900112233',
                'address' => 'Jl. Bangka Raya No. 42, Kemang',
                'city' => 'Jakarta Selatan',
                'payment_term_days' => 0,
                'credit_limit' => 1000000.00,
                'current_receivable' => 0.00,
                'notes' => 'Reseller eceran untuk acara kantor dan hampers arisan.',
                'is_active' => true,
            ],
        ];

        foreach ($partners as $partnerData) {
            Partner::updateOrCreate(
                ['code' => $partnerData['code']],
                [
                    'name' => $partnerData['name'],
                    'type' => $partnerData['type'],
                    'contact_person' => $partnerData['contact_person'],
                    'phone' => Partner::normalizePhone($partnerData['phone']),
                    'whatsapp_number' => Partner::normalizePhone($partnerData['whatsapp_number']),
                    'address' => $partnerData['address'],
                    'city' => $partnerData['city'],
                    'payment_term_days' => $partnerData['payment_term_days'],
                    'credit_limit' => $partnerData['credit_limit'],
                    'current_receivable' => $partnerData['current_receivable'],
                    'notes' => $partnerData['notes'],
                    'is_active' => $partnerData['is_active'],
                ]
            );
        }
    }
}
