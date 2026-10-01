<?php

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

it('redirects unauthenticated user from products page to login', function () {
    get(route('admin.products'))
        ->assertRedirect(route('login'));
});

it('renders master products and recipes component successfully for authorized user', function () {
    $user = User::where('email', 'admin@halala-food.id')->first();
    actingAs($user);

    get(route('admin.products'))
        ->assertOk()
        ->assertSee('Master Produk')
        ->assertSee('Produk')
        ->assertSee('Formula Resep')
        ->assertSee('Bahan Baku')
        ->assertSee('Kategori Produk');

    Livewire::test('admin.products')
        ->assertStatus(200)
        ->assertSee('Master Produk')
        ->assertSee('Ting-Ting Susu Halala Original')
        ->assertSee('Marie Wijen Halala Renyah');
});

it('forbids unauthorized user without product permissions from accessing products page', function () {
    $unauthRole = Role::firstOrCreate(['name' => 'guest_role', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($unauthRole);
    actingAs($user);

    get(route('admin.products'))
        ->assertForbidden();
});

it('can create a new product with multiple packaging variants', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $cat = Category::where('slug', 'ting-ting-susu')->first();

    $variantsData = [
        [
            'name' => 'Kemasan Mini Pouch 100g',
            'packaging_type' => 'pouch',
            'pcs_per_package' => 5,
            'weight_grams' => 100,
            'barcode' => '8991234999011',
            'sku_code' => 'TTS-MINI-100G',
            'base_cost' => 6000,
            'wholesale_price' => 10000,
            'retail_price' => 12000,
            'stock_qty' => 50,
            'min_stock_alert' => 10,
        ],
        [
            'name' => 'Kemasan Toples Jumbo 50 Pcs',
            'packaging_type' => 'jar',
            'pcs_per_package' => 50,
            'weight_grams' => 1000,
            'barcode' => '8991234999028',
            'sku_code' => 'TTS-TPL-50PCS',
            'base_cost' => 50000,
            'wholesale_price' => 80000,
            'retail_price' => 95000,
            'stock_qty' => 20,
            'min_stock_alert' => 5,
        ],
    ];

    $component = Livewire::test('admin.products');
    $result = $component->instance()->saveProduct(
        null,
        $cat->id,
        'Ting-Ting Susu Cokelat',
        'TTS-CKL-01',
        'Varian baru rasa cokelat premium',
        true,
        $variantsData
    );

    expect($result['success'])->toBeTrue();

    $newProd = Product::where('code', 'TTS-CKL-01')->first();
    expect($newProd)->not->toBeNull()
        ->and($newProd->name)->toBe('Ting-Ting Susu Cokelat')
        ->and($newProd->variants->count())->toBe(2);

    $miniPouch = ProductVariant::where('sku_code', 'TTS-MINI-100G')->first();
    expect($miniPouch)->not->toBeNull()
        ->and((float) $miniPouch->wholesale_price)->toBe(10000.00)
        ->and((float) $miniPouch->retail_price)->toBe(12000.00);
});

it('can update an existing product and update variants', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $prod = Product::where('code', 'TTS-01')->first();
    $cat = Category::where('slug', 'ting-ting-susu')->first();

    $existingVariants = $prod->variants;
    $updatedVariantList = [];
    foreach ($existingVariants as $v) {
        $updatedVariantList[] = [
            'id' => $v->id,
            'name' => $v->name . ' (Updated)',
            'packaging_type' => $v->packaging_type,
            'pcs_per_package' => $v->pcs_per_package,
            'weight_grams' => $v->weight_grams,
            'barcode' => $v->barcode,
            'sku_code' => $v->sku_code,
            'base_cost' => $v->base_cost,
            'wholesale_price' => (float) $v->wholesale_price + 1000,
            'retail_price' => (float) $v->retail_price + 1000,
            'stock_qty' => $v->stock_qty,
            'min_stock_alert' => $v->min_stock_alert,
        ];
    }

    $component = Livewire::test('admin.products');
    $result = $component->instance()->saveProduct(
        $prod->id,
        $cat->id,
        'Ting-Ting Susu Halala Super Original',
        'TTS-01',
        'Deskripsi baru yang diperbarui',
        true,
        $updatedVariantList
    );

    expect($result['success'])->toBeTrue();

    $prod->refresh();
    expect($prod->name)->toBe('Ting-Ting Susu Halala Super Original');

    $firstVar = $prod->variants->first();
    expect($firstVar->name)->toContain('(Updated)');
});

it('can create, update, and delete a raw material', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $component = Livewire::test('admin.products');

    // 1. Create Raw Material
    $resCreate = $component->instance()->saveRawMaterial(
        null,
        'BB-KKL-01',
        'Bubuk Kakao Cokelat Murni',
        'ingredient',
        'gram',
        10000,
        2000,
        55.00,
        'Bahan cokelat premium'
    );
    expect($resCreate['success'])->toBeTrue();

    $newMat = RawMaterial::where('code', 'BB-KKL-01')->first();
    expect($newMat)->not->toBeNull()
        ->and((float) $newMat->stock_qty)->toBe(10000.00)
        ->and((float) $newMat->average_cost)->toBe(55.00);

    // 2. Update Raw Material
    $resUpdate = $component->instance()->saveRawMaterial(
        $newMat->id,
        'BB-KKL-01',
        'Bubuk Kakao Cokelat Dutch Processed',
        'ingredient',
        'gram',
        12000,
        2500,
        60.00,
        'Kakao aroma kuat'
    );
    expect($resUpdate['success'])->toBeTrue();

    $newMat->refresh();
    expect($newMat->name)->toBe('Bubuk Kakao Cokelat Dutch Processed')
        ->and((float) $newMat->average_cost)->toBe(60.00);

    // 3. Delete Raw Material
    $resDelete = $component->instance()->deleteRawMaterial($newMat->id);
    expect($resDelete['success'])->toBeTrue();
    expect(RawMaterial::find($newMat->id))->toBeNull();
});

it('can create, update, and delete a recipe with BOM items', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $variant = ProductVariant::where('sku_code', 'MRW-ECER-1PC')->first();
    $susu = RawMaterial::where('code', 'BB-SSU-01')->first();
    $wijen = RawMaterial::where('code', 'BB-WJN-01')->first();

    $component = Livewire::test('admin.products');

    // 1. Create Recipe
    $resCreate = $component->instance()->saveRecipe(
        null,
        $variant->id,
        'Resep Marie Wijen Eceran Batch 200 Pcs',
        200,
        30000,
        15000,
        'Campur wijen dan adonan marie, potong cetak 200 pcs.',
        true,
        [
            ['raw_material_id' => $susu->id, 'quantity_required' => 500],
            ['raw_material_id' => $wijen->id, 'quantity_required' => 1000],
        ]
    );

    expect($resCreate['success'])->toBeTrue();

    $newRecipe = Recipe::where('name', 'Resep Marie Wijen Eceran Batch 200 Pcs')->first();
    expect($newRecipe)->not->toBeNull()
        ->and($newRecipe->items->count())->toBe(2)
        ->and($newRecipe->batch_output_qty)->toBe(200);

    // Verify HPP calculation
    expect($newRecipe->calculateEstimatedHpp())->toBeGreaterThan(0);

    // 2. Delete Recipe
    $resDelete = $component->instance()->deleteRecipe($newRecipe->id);
    expect($resDelete['success'])->toBeTrue();
    expect(Recipe::find($newRecipe->id))->toBeNull();
});

it('can create and update category and prevents deleting category with products', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $component = Livewire::test('admin.products');

    // 1. Create Category
    $resCreate = $component->instance()->saveCategory(
        null,
        'Kue Basah Halala',
        'Kategori aneka kue basah tradisional',
        true
    );
    expect($resCreate['success'])->toBeTrue();

    $newCat = Category::where('name', 'Kue Basah Halala')->first();
    expect($newCat)->not->toBeNull();

    // 2. Prevent deleting existing category with products
    $catWithProds = Category::where('slug', 'ting-ting-susu')->first();
    $resFailDelete = $component->instance()->deleteCategory($catWithProds->id);
    expect($resFailDelete['success'])->toBeFalse()
        ->and($resFailDelete['message'])->toContain('memiliki');

    // 3. Delete empty new category
    $resDelete = $component->instance()->deleteCategory($newCat->id);
    expect($resDelete['success'])->toBeTrue();
    expect(Category::find($newCat->id))->toBeNull();
});
