<?php

use App\Models\Category;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
});

it('seeds all permission groups and permissions correctly without obsolete entries', function () {
    $expectedPermissions = [
        // Dashboard
        'dashboard-view',
        // User & Access
        'user-manage', 'role-manage', 'permission-manage',
        // Products & Recipes
        'category-manage', 'product-manage', 'recipe-manage',
        // Raw Materials & Warehouse
        'raw-material-manage', 'stock-manage', 'waste-manage', 'purchase-manage',
        // Production
        'production-manage',
        // Distribution & Courier
        'partner-manage', 'delivery-manage',
        // POS & Cashier
        'pos-manage', 'cash-register-manage',
        // Accounting & Finance
        'financial-report-view', 'journal-manage', 'ledger-manage', 'coa-manage', 'cash-transaction-manage',
    ];

    foreach ($expectedPermissions as $permName) {
        expect(Permission::where('name', $permName)->exists())->toBeTrue();
    }

    // Ensure obsolete permissions were removed
    expect(Permission::where('name', 'kitchen-manage')->exists())->toBeFalse()
        ->and(Permission::where('name', 'order-manage')->exists())->toBeFalse()
        ->and(Permission::where('name', 'payment-process')->exists())->toBeFalse();
});

it('assigns correct permissions to the 6 system roles', function () {
    $dev = Role::findByName('dev');
    $ceo = Role::findByName('ceo');
    $manager = Role::findByName('manager');
    $masak = Role::findByName('tukang masak');
    $kasir = Role::findByName('kasir');
    $kurir = Role::findByName('kurir');

    expect($dev->hasPermissionTo('user-manage'))->toBeTrue()
        ->and($dev->hasPermissionTo('recipe-manage'))->toBeTrue()
        ->and($dev->hasPermissionTo('journal-manage'))->toBeTrue();

    expect($ceo->hasPermissionTo('dashboard-view'))->toBeTrue()
        ->and($ceo->hasPermissionTo('financial-report-view'))->toBeTrue()
        ->and($ceo->hasPermissionTo('journal-manage'))->toBeTrue()
        ->and($ceo->hasPermissionTo('production-manage'))->toBeFalse();

    expect($manager->hasPermissionTo('raw-material-manage'))->toBeTrue()
        ->and($manager->hasPermissionTo('production-manage'))->toBeTrue()
        ->and($manager->hasPermissionTo('pos-manage'))->toBeTrue()
        ->and($manager->hasPermissionTo('financial-report-view'))->toBeTrue();

    expect($masak->hasPermissionTo('production-manage'))->toBeTrue()
        ->and($masak->hasPermissionTo('waste-manage'))->toBeTrue()
        ->and($masak->hasPermissionTo('recipe-manage'))->toBeTrue()
        ->and($masak->hasPermissionTo('financial-report-view'))->toBeFalse();

    expect($kasir->hasPermissionTo('pos-manage'))->toBeTrue()
        ->and($kasir->hasPermissionTo('cash-register-manage'))->toBeTrue()
        ->and($kasir->hasPermissionTo('production-manage'))->toBeFalse();

    expect($kurir->hasPermissionTo('delivery-manage'))->toBeTrue()
        ->and($kurir->hasPermissionTo('pos-manage'))->toBeFalse();
});

it('seeds master products, multi-UOM variants, and prices correctly', function () {
    $catTingTing = Category::where('slug', 'ting-ting-susu')->first();
    $catMarieWijen = Category::where('slug', 'marie-wijen')->first();

    expect($catTingTing)->not->toBeNull()
        ->and($catMarieWijen)->not->toBeNull();

    // Check Ting-Ting Susu variants
    $tts = Product::where('code', 'TTS-01')->first();
    expect($tts)->not->toBeNull()
        ->and($tts->category_id)->toBe($catTingTing->id);

    $pouchTTS = ProductVariant::where('sku_code', 'TTS-PCH-200G')->first();
    expect($pouchTTS)->not->toBeNull()
        ->and($pouchTTS->packaging_type)->toBe('pouch')
        ->and($pouchTTS->pcs_per_package)->toBe(10)
        ->and((float) $pouchTTS->weight_grams)->toBe(200.00)
        ->and((float) $pouchTTS->wholesale_price)->toBe(18000.00);

    $toplesTTS = ProductVariant::where('sku_code', 'TTS-TPL-20PCS')->first();
    expect($toplesTTS)->not->toBeNull()
        ->and($toplesTTS->packaging_type)->toBe('jar')
        ->and($toplesTTS->pcs_per_package)->toBe(20)
        ->and((float) $toplesTTS->wholesale_price)->toBe(34000.00);

    $ecerTTS = ProductVariant::where('sku_code', 'TTS-ECER-1PC')->first();
    expect($ecerTTS)->not->toBeNull()
        ->and($ecerTTS->packaging_type)->toBe('piece')
        ->and($ecerTTS->pcs_per_package)->toBe(1)
        ->and((float) $ecerTTS->retail_price)->toBe(2000.00);
});

it('seeds raw materials with stock and average costs accurately', function () {
    $susu = RawMaterial::where('code', 'BB-SSU-01')->first();
    $wijen = RawMaterial::where('code', 'BB-WJN-01')->first();
    $pouch = RawMaterial::where('code', 'KM-PCH-200')->first();

    expect($susu)->not->toBeNull()
        ->and($susu->unit)->toBe('gram')
        ->and((float) $susu->average_cost)->toBe(95.00)
        ->and((float) $susu->stock_qty)->toBe(25000.00);

    expect($wijen)->not->toBeNull()
        ->and($wijen->unit)->toBe('gram')
        ->and((float) $wijen->average_cost)->toBe(65.00);

    expect($pouch)->not->toBeNull()
        ->and($pouch->unit)->toBe('pcs')
        ->and((float) $pouch->average_cost)->toBe(850.00);
});

it('calculates recipe BOM material cost and estimated HPP accurately', function () {
    $pouchVariant = ProductVariant::where('sku_code', 'TTS-PCH-200G')->first();
    $recipe = Recipe::where('product_variant_id', $pouchVariant->id)->first();

    expect($recipe)->not->toBeNull()
        ->and($recipe->items->count())->toBe(6)
        ->and($recipe->batch_output_qty)->toBe(50);

    $totalMaterialCost = $recipe->calculateTotalMaterialCost();
    expect($totalMaterialCost)->toBeGreaterThan(0);

    $estimatedHpp = $recipe->calculateEstimatedHpp();
    expect($estimatedHpp)->toBeGreaterThan(0)
        ->and($estimatedHpp)->toBeLessThan((float) $pouchVariant->wholesale_price); // HPP should be lower than wholesale price
});
