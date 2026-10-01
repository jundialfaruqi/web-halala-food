<?php

namespace Database\Seeders;

use App\Models\Delivery;
use App\Models\Partner;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        $kurir = User::where('email', 'kurir@halala-food.id')->first();
        $manager = User::where('email', 'manager@halala-food.id')->first();
        $tipTop = Partner::where('code', 'MTR-001')->first() ?? Partner::first();
        $naga = Partner::where('code', 'MTR-002')->first();
        $tokoBerkah = Partner::where('code', 'MTR-003')->first();

        $ttsPouch = ProductVariant::where('sku_code', 'TTS-PCH-200G')->first() ?? ProductVariant::first();
        $ttsToples = ProductVariant::where('sku_code', 'TTS-TPL-20PCS')->first();
        $mrwPouch = ProductVariant::where('sku_code', 'MRW-PCH-200G')->first() ?? $ttsPouch;
        $mrwToples = ProductVariant::where('sku_code', 'MRW-TPL-20PCS')->first() ?? $ttsPouch;

        if (!$tipTop || !$ttsPouch) {
            return;
        }

        // Delivery 1: Delivered to Tip Top Supermarket
        $deliv1 = Delivery::updateOrCreate(
            ['delivery_number' => 'SJ-20261001-0001'],
            [
                'partner_id' => $tipTop->id,
                'courier_id' => $kurir?->id,
                'status' => 'delivered',
                'total_amount' => (40 * (float) $ttsPouch->wholesale_price) + (30 * (float) $mrwPouch->wholesale_price),
                'receiver_name' => 'Bpk. Hendra Wijaya (Procurement)',
                'receiver_phone' => '081211223344',
                'notes' => 'Pengiriman rutin batch awal bulan via motor boks.',
                'dispatched_at' => Carbon::now()->subHours(4),
                'delivered_at' => Carbon::now()->subHours(2),
                'created_by' => $manager?->id,
            ]
        );

        $deliv1->items()->delete();
        $deliv1->items()->create([
            'product_variant_id' => $ttsPouch->id,
            'qty_sent' => 40,
            'qty_accepted' => 40,
            'qty_returned' => 0,
            'unit_price' => $ttsPouch->wholesale_price,
            'subtotal' => 40 * (float) $ttsPouch->wholesale_price,
        ]);
        $deliv1->items()->create([
            'product_variant_id' => $mrwPouch->id,
            'qty_sent' => 30,
            'qty_accepted' => 30,
            'qty_returned' => 0,
            'unit_price' => $mrwPouch->wholesale_price,
            'subtotal' => 30 * (float) $mrwPouch->wholesale_price,
        ]);

        // Delivery 2: On The Way to Naga Swalayan
        $targetNaga = $naga ?: $tipTop;
        $deliv2 = Delivery::updateOrCreate(
            ['delivery_number' => 'SJ-20261001-0002'],
            [
                'partner_id' => $targetNaga->id,
                'courier_id' => $kurir?->id,
                'status' => 'on_the_way',
                'total_amount' => (25 * (float) $ttsPouch->wholesale_price) + (20 * (float) ($ttsToples?->wholesale_price ?? $ttsPouch->wholesale_price)),
                'receiver_name' => null,
                'receiver_phone' => null,
                'notes' => 'Kurir sedang dalam perjalanan menuju lokasi Pondok Gede.',
                'dispatched_at' => Carbon::now()->subMinutes(30),
                'delivered_at' => null,
                'created_by' => $manager?->id,
            ]
        );

        $deliv2->items()->delete();
        $deliv2->items()->create([
            'product_variant_id' => $ttsPouch->id,
            'qty_sent' => 25,
            'qty_accepted' => 0,
            'qty_returned' => 0,
            'unit_price' => $ttsPouch->wholesale_price,
            'subtotal' => 25 * (float) $ttsPouch->wholesale_price,
        ]);
        $deliv2->items()->create([
            'product_variant_id' => $ttsToples ? $ttsToples->id : $ttsPouch->id,
            'qty_sent' => 20,
            'qty_accepted' => 0,
            'qty_returned' => 0,
            'unit_price' => $ttsToples ? $ttsToples->wholesale_price : $ttsPouch->wholesale_price,
            'subtotal' => 20 * (float) ($ttsToples ? $ttsToples->wholesale_price : $ttsPouch->wholesale_price),
        ]);

        // Delivery 3: Draft for Toko Berkah Kelontong
        $targetBerkah = $tokoBerkah ?: $tipTop;
        $deliv3 = Delivery::updateOrCreate(
            ['delivery_number' => 'SJ-20261001-0003'],
            [
                'partner_id' => $targetBerkah->id,
                'courier_id' => $kurir?->id,
                'status' => 'draft',
                'total_amount' => 15 * (float) ($mrwToples ? $mrwToples->wholesale_price : $ttsPouch->wholesale_price),
                'receiver_name' => null,
                'receiver_phone' => null,
                'notes' => 'Pesanan kemasan toples toko kelontong harian Cempaka Putih.',
                'dispatched_at' => null,
                'delivered_at' => null,
                'created_by' => $manager?->id,
            ]
        );

        $deliv3->items()->delete();
        $deliv3->items()->create([
            'product_variant_id' => $mrwToples ? $mrwToples->id : $ttsPouch->id,
            'qty_sent' => 15,
            'qty_accepted' => 0,
            'qty_returned' => 0,
            'unit_price' => $mrwToples ? $mrwToples->wholesale_price : $ttsPouch->wholesale_price,
            'subtotal' => 15 * (float) ($mrwToples ? $mrwToples->wholesale_price : $ttsPouch->wholesale_price),
        ]);
    }
}
