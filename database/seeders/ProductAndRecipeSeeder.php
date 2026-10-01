<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Database\Seeder;

class ProductAndRecipeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Master Bahan Baku (Raw Materials)
        $materials = [
            [
                'code' => 'BB-SSU-01',
                'name' => 'Susu Bubuk Full Cream',
                'category' => 'ingredient',
                'unit' => 'gram',
                'stock_qty' => 25000,
                'min_stock_alert' => 5000,
                'average_cost' => 95.00, // Rp 95.000 / kg
                'notes' => 'Bahan utama pembuatan Ting-Ting Susu',
            ],
            [
                'code' => 'BB-WJN-01',
                'name' => 'Biji Wijen Sangrai Pilihan',
                'category' => 'ingredient',
                'unit' => 'gram',
                'stock_qty' => 30000,
                'min_stock_alert' => 5000,
                'average_cost' => 65.00, // Rp 65.000 / kg
                'notes' => 'Bahan utama pembuatan Marie Wijen',
            ],
            [
                'code' => 'BB-TPG-01',
                'name' => 'Tepung Terigu Segitiga Biru',
                'category' => 'ingredient',
                'unit' => 'gram',
                'stock_qty' => 50000,
                'min_stock_alert' => 10000,
                'average_cost' => 14.00, // Rp 14.000 / kg
                'notes' => 'Tepung terigu serbaguna',
            ],
            [
                'code' => 'BB-GLA-01',
                'name' => 'Gula Pasir Murni Gulaku',
                'category' => 'ingredient',
                'unit' => 'gram',
                'stock_qty' => 40000,
                'min_stock_alert' => 10000,
                'average_cost' => 17.50, // Rp 17.500 / kg
                'notes' => 'Gula pasir premium untuk karamelisasi adonan',
            ],
            [
                'code' => 'BB-MNT-01',
                'name' => 'Mentega Baker Premium',
                'category' => 'ingredient',
                'unit' => 'gram',
                'stock_qty' => 20000,
                'min_stock_alert' => 3000,
                'average_cost' => 45.00, // Rp 45.000 / kg
                'notes' => 'Mentega wangi gurih',
            ],
            [
                'code' => 'BB-MKY-01',
                'name' => 'Minyak Kelapa Barco',
                'category' => 'ingredient',
                'unit' => 'ml',
                'stock_qty' => 30000,
                'min_stock_alert' => 5000,
                'average_cost' => 22.00, // Rp 22.000 / liter
                'notes' => 'Minyak kelapa untuk penggorengan higienis',
            ],
            [
                'code' => 'KM-PCH-200',
                'name' => 'Standing Pouch Zipper 200g Food Grade',
                'category' => 'packaging',
                'unit' => 'pcs',
                'stock_qty' => 500,
                'min_stock_alert' => 100,
                'average_cost' => 850.00,
                'notes' => 'Kemasan pouch untuk penjualan supermarket',
            ],
            [
                'code' => 'KM-PCH-250',
                'name' => 'Standing Pouch Zipper 250g Food Grade',
                'category' => 'packaging',
                'unit' => 'pcs',
                'stock_qty' => 500,
                'min_stock_alert' => 100,
                'average_cost' => 950.00,
                'notes' => 'Kemasan pouch 250g untuk Marie Wijen',
            ],
            [
                'code' => 'KM-TPL-01',
                'name' => 'Toples Tabung Plastik 800ml + Tutup Ulir',
                'category' => 'packaging',
                'unit' => 'pcs',
                'stock_qty' => 300,
                'min_stock_alert' => 50,
                'average_cost' => 2500.00,
                'notes' => 'Kemasan toples untuk toko kelontong harian',
            ],
            [
                'code' => 'KM-LBL-01',
                'name' => 'Stiker Label Halala Food Gold Foil',
                'category' => 'packaging',
                'unit' => 'lembar',
                'stock_qty' => 1000,
                'min_stock_alert' => 200,
                'average_cost' => 350.00,
                'notes' => 'Stiker branding label depan kemasan',
            ],
        ];

        $rawMaterialMap = [];
        foreach ($materials as $mat) {
            $createdMat = RawMaterial::updateOrCreate(
                ['code' => $mat['code']],
                $mat
            );
            $rawMaterialMap[$mat['code']] = $createdMat;
        }

        // 2. Seed Categories
        $catTingTing = Category::updateOrCreate(
            ['slug' => 'ting-ting-susu'],
            [
                'name' => 'Ting-Ting Susu',
                'description' => 'Camilan manis renyah olahan susu murni khas Halala Food dengan cita rasa gurih dan lumer di mulut.',
                'is_active' => true,
            ]
        );

        $catMarieWijen = Category::updateOrCreate(
            ['slug' => 'marie-wijen'],
            [
                'name' => 'Marie Wijen',
                'description' => 'Biskuit renyah berpadu balutan wijen sangrai gurih dan karamel istimewa khas Halala Food.',
                'is_active' => true,
            ]
        );

        // 3. Seed Products & Packaging Variants
        // Product A: Ting-Ting Susu
        $prodTingTing = Product::updateOrCreate(
            ['code' => 'TTS-01'],
            [
                'category_id' => $catTingTing->id,
                'name' => 'Ting-Ting Susu Halala Original',
                'slug' => 'ting-ting-susu-halala-original',
                'description' => 'Ting-Ting Susu legendaris renyah dengan kelezatan susu asli, dibuat higienis tanpa bahan pengawet.',
                'image' => 'assets/images/ting_ting_susu.webp',
                'is_active' => true,
            ]
        );

        // Variants for Ting-Ting Susu
        $ttsPouch = ProductVariant::updateOrCreate(
            ['sku_code' => 'TTS-PCH-200G'],
            [
                'product_id' => $prodTingTing->id,
                'name' => 'Kemasan Pouch 200g (Isi 10 Pcs)',
                'packaging_type' => 'pouch',
                'pcs_per_package' => 10,
                'weight_grams' => 200.00,
                'barcode' => '8991234001011',
                'base_cost' => 11200.00,
                'wholesale_price' => 18000.00, // Harga supermarket
                'retail_price' => 22000.00,    // Harga eceran
                'stock_qty' => 150,
                'min_stock_alert' => 20,
                'is_active' => true,
            ]
        );

        $ttsToples = ProductVariant::updateOrCreate(
            ['sku_code' => 'TTS-TPL-20PCS'],
            [
                'product_id' => $prodTingTing->id,
                'name' => 'Kemasan Toples (Isi 20 Pcs)',
                'packaging_type' => 'jar',
                'pcs_per_package' => 20,
                'weight_grams' => 400.00,
                'barcode' => '8991234001028',
                'base_cost' => 21800.00,
                'wholesale_price' => 34000.00, // Harga grosir kelontong
                'retail_price' => 40000.00,    // Harga jual toples
                'stock_qty' => 80,
                'min_stock_alert' => 15,
                'is_active' => true,
            ]
        );

        $ttsEcer = ProductVariant::updateOrCreate(
            ['sku_code' => 'TTS-ECER-1PC'],
            [
                'product_id' => $prodTingTing->id,
                'name' => 'Satuan Eceran (1 Pcs)',
                'packaging_type' => 'piece',
                'pcs_per_package' => 1,
                'weight_grams' => 20.00,
                'barcode' => '8991234001035',
                'base_cost' => 1050.00,
                'wholesale_price' => 1700.00,
                'retail_price' => 2000.00,     // Harga ecer warung
                'stock_qty' => 500,
                'min_stock_alert' => 50,
                'is_active' => true,
            ]
        );

        // Product B: Marie Wijen
        $prodMarieWijen = Product::updateOrCreate(
            ['code' => 'MRW-01'],
            [
                'category_id' => $catMarieWijen->id,
                'name' => 'Marie Wijen Halala Renyah',
                'slug' => 'marie-wijen-halala-renyah',
                'description' => 'Camilan marie lapis wijen sangrai manis legit khas keluarga Halala Food.',
                'image' => 'assets/images/marie_wijen.webp',
                'is_active' => true,
            ]
        );

        // Variants for Marie Wijen
        $mrwPouch = ProductVariant::updateOrCreate(
            ['sku_code' => 'MRW-PCH-250G'],
            [
                'product_id' => $prodMarieWijen->id,
                'name' => 'Kemasan Pouch 250g (Isi 12 Pcs)',
                'packaging_type' => 'pouch',
                'pcs_per_package' => 12,
                'weight_grams' => 250.00,
                'barcode' => '8991234002018',
                'base_cost' => 12300.00,
                'wholesale_price' => 20000.00, // Supermarket
                'retail_price' => 25000.00,    // Eceran
                'stock_qty' => 120,
                'min_stock_alert' => 20,
                'is_active' => true,
            ]
        );

        $mrwToples = ProductVariant::updateOrCreate(
            ['sku_code' => 'MRW-TPL-24PCS'],
            [
                'product_id' => $prodMarieWijen->id,
                'name' => 'Kemasan Toples (Isi 24 Pcs)',
                'packaging_type' => 'jar',
                'pcs_per_package' => 24,
                'weight_grams' => 500.00,
                'barcode' => '8991234002025',
                'base_cost' => 23500.00,
                'wholesale_price' => 38000.00,
                'retail_price' => 45000.00,
                'stock_qty' => 60,
                'min_stock_alert' => 15,
                'is_active' => true,
            ]
        );

        $mrwEcer = ProductVariant::updateOrCreate(
            ['sku_code' => 'MRW-ECER-1PC'],
            [
                'product_id' => $prodMarieWijen->id,
                'name' => 'Satuan Eceran (1 Pcs)',
                'packaging_type' => 'piece',
                'pcs_per_package' => 1,
                'weight_grams' => 21.00,
                'barcode' => '8991234002032',
                'base_cost' => 950.00,
                'wholesale_price' => 1600.00,
                'retail_price' => 2000.00,
                'stock_qty' => 400,
                'min_stock_alert' => 50,
                'is_active' => true,
            ]
        );

        // 4. Seed Standard Recipes (Bill of Materials)
        // Recipe 1: Ting-Ting Susu Kemasan Pouch (Batch output: 50 Pouch = 500 pcs)
        $recipeTTSPouch = Recipe::updateOrCreate(
            ['product_variant_id' => $ttsPouch->id],
            [
                'name' => 'Formula Standar Ting-Ting Susu Pouch 200g (Batch: 50 Pouch)',
                'batch_output_qty' => 50,
                'labor_cost' => 50000.00,
                'overhead_cost' => 25000.00,
                'notes' => 'Kocok susu dan gula hingga kalis, masak dengan api sedang selama 35 menit, lalu cetak dan kemas 10 pcs per pouch.',
                'is_active' => true,
            ]
        );

        $recipeTTSPouch->items()->delete();
        $recipeTTSPouch->items()->createMany([
            ['raw_material_id' => $rawMaterialMap['BB-SSU-01']->id, 'quantity_required' => 2500, 'notes' => 'Susu Bubuk 2.5 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-GLA-01']->id, 'quantity_required' => 4000, 'notes' => 'Gula Pasir 4.0 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-MNT-01']->id, 'quantity_required' => 1000, 'notes' => 'Mentega 1.0 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-TPG-01']->id, 'quantity_required' => 1500, 'notes' => 'Tepung Terigu 1.5 kg'],
            ['raw_material_id' => $rawMaterialMap['KM-PCH-200']->id, 'quantity_required' => 50, 'notes' => '50 Standing Pouch 200g'],
            ['raw_material_id' => $rawMaterialMap['KM-LBL-01']->id, 'quantity_required' => 50, 'notes' => '50 Lembar Stiker Label'],
        ]);

        // Recipe 2: Ting-Ting Susu Kemasan Toples (Batch output: 25 Toples = 500 pcs)
        $recipeTTSToples = Recipe::updateOrCreate(
            ['product_variant_id' => $ttsToples->id],
            [
                'name' => 'Formula Standar Ting-Ting Susu Toples (Batch: 25 Toples)',
                'batch_output_qty' => 25,
                'labor_cost' => 50000.00,
                'overhead_cost' => 25000.00,
                'notes' => 'Kemas 20 pcs per toples, pastikan segel rapat anti melempem.',
                'is_active' => true,
            ]
        );

        $recipeTTSToples->items()->delete();
        $recipeTTSToples->items()->createMany([
            ['raw_material_id' => $rawMaterialMap['BB-SSU-01']->id, 'quantity_required' => 2500, 'notes' => 'Susu Bubuk 2.5 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-GLA-01']->id, 'quantity_required' => 4000, 'notes' => 'Gula Pasir 4.0 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-MNT-01']->id, 'quantity_required' => 1000, 'notes' => 'Mentega 1.0 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-TPG-01']->id, 'quantity_required' => 1500, 'notes' => 'Tepung Terigu 1.5 kg'],
            ['raw_material_id' => $rawMaterialMap['KM-TPL-01']->id, 'quantity_required' => 25, 'notes' => '25 Toples Plastik'],
            ['raw_material_id' => $rawMaterialMap['KM-LBL-01']->id, 'quantity_required' => 25, 'notes' => '25 Lembar Stiker Label'],
        ]);

        // Recipe 3: Marie Wijen Pouch 250g (Batch output: 50 Pouch = 600 pcs)
        $recipeMRWPouch = Recipe::updateOrCreate(
            ['product_variant_id' => $mrwPouch->id],
            [
                'name' => 'Formula Standar Marie Wijen Pouch 250g (Batch: 50 Pouch)',
                'batch_output_qty' => 50,
                'labor_cost' => 50000.00,
                'overhead_cost' => 25000.00,
                'notes' => 'Sangrai wijen dengan kematangan merata, balut pada adonan biskuit marie, kemas 12 pcs per pouch.',
                'is_active' => true,
            ]
        );

        $recipeMRWPouch->items()->delete();
        $recipeMRWPouch->items()->createMany([
            ['raw_material_id' => $rawMaterialMap['BB-WJN-01']->id, 'quantity_required' => 3500, 'notes' => 'Biji Wijen 3.5 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-TPG-01']->id, 'quantity_required' => 5000, 'notes' => 'Tepung Terigu 5.0 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-GLA-01']->id, 'quantity_required' => 2500, 'notes' => 'Gula Pasir 2.5 kg'],
            ['raw_material_id' => $rawMaterialMap['BB-MKY-01']->id, 'quantity_required' => 1500, 'notes' => 'Minyak Kelapa 1.5 Liter'],
            ['raw_material_id' => $rawMaterialMap['KM-PCH-250']->id, 'quantity_required' => 50, 'notes' => '50 Standing Pouch 250g'],
            ['raw_material_id' => $rawMaterialMap['KM-LBL-01']->id, 'quantity_required' => 50, 'notes' => '50 Lembar Stiker Label'],
        ]);
    }
}
