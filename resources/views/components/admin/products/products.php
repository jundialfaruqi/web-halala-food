<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin'), Title('Master Produk & Resep - Halala Food')] class extends Component
{
    /**
     * Save (Create or Update) a Product with its Packaging Variants
     */
    public function saveProduct(?int $id, int $categoryId, string $name, string $code, ?string $description = null, bool $isActive = true, array $variants = []): array
    {
        $name = trim($name);
        $code = trim(strtoupper($code));
        $description = $description ? trim($description) : null;

        $rules = [
            'categoryId' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('products', 'code')->ignore($id),
            ],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.name' => ['required', 'string', 'max:150'],
            'variants.*.packaging_type' => ['required', 'string', 'in:pouch,jar,piece,box,other'],
            'variants.*.pcs_per_package' => ['required', 'integer', 'min:1'],
            'variants.*.wholesale_price' => ['required', 'numeric', 'min:0'],
            'variants.*.retail_price' => ['required', 'numeric', 'min:0'],
        ];

        $validator = validator(
            [
                'categoryId' => $categoryId,
                'name' => $name,
                'code' => $code,
                'variants' => $variants,
            ],
            $rules,
            [
                'categoryId.required' => 'Kategori produk wajib dipilih.',
                'name.required' => 'Nama produk wajib diisi.',
                'code.required' => 'Kode produk wajib diisi.',
                'code.unique' => 'Kode produk ini sudah digunakan.',
                'variants.required' => 'Minimal harus menambahkan satu varian kemasan produk.',
                'variants.min' => 'Minimal harus menambahkan satu varian kemasan produk.',
                'variants.*.name.required' => 'Nama varian kemasan wajib diisi.',
                'variants.*.pcs_per_package.required' => 'Jumlah isi pcs per kemasan wajib diisi.',
                'variants.*.wholesale_price.required' => 'Harga grosir/supermarket wajib diisi.',
                'variants.*.retail_price.required' => 'Harga eceran/kelontong wajib diisi.',
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
            DB::transaction(function () use ($id, $categoryId, $name, $code, $description, $isActive, $variants) {
                if ($id) {
                    $product = Product::findOrFail($id);
                    $product->update([
                        'category_id' => $categoryId,
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'code' => $code,
                        'description' => $description,
                        'is_active' => $isActive,
                    ]);
                } else {
                    $product = Product::create([
                        'category_id' => $categoryId,
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'code' => $code,
                        'description' => $description,
                        'is_active' => $isActive,
                    ]);
                }

                $existingVariantIds = [];
                foreach ($variants as $varData) {
                    $varId = !empty($varData['id']) ? (int) $varData['id'] : null;
                    $barcode = !empty($varData['barcode']) ? trim($varData['barcode']) : null;
                    $skuCode = !empty($varData['sku_code']) ? trim(strtoupper($varData['sku_code'])) : null;

                    $payload = [
                        'product_id' => $product->id,
                        'name' => trim($varData['name']),
                        'packaging_type' => $varData['packaging_type'] ?? 'pouch',
                        'pcs_per_package' => (int) ($varData['pcs_per_package'] ?? 1),
                        'weight_grams' => !empty($varData['weight_grams']) ? (float) $varData['weight_grams'] : null,
                        'barcode' => $barcode,
                        'sku_code' => $skuCode,
                        'base_cost' => (float) ($varData['base_cost'] ?? 0),
                        'wholesale_price' => (float) ($varData['wholesale_price'] ?? 0),
                        'retail_price' => (float) ($varData['retail_price'] ?? 0),
                        'stock_qty' => (int) ($varData['stock_qty'] ?? 0),
                        'min_stock_alert' => (int) ($varData['min_stock_alert'] ?? 10),
                        'is_active' => isset($varData['is_active']) ? (bool) $varData['is_active'] : true,
                    ];

                    if ($varId) {
                        $variant = ProductVariant::where('product_id', $product->id)->find($varId);
                        if ($variant) {
                            $variant->update($payload);
                            $existingVariantIds[] = $variant->id;
                            continue;
                        }
                    }

                    $newVariant = ProductVariant::create($payload);
                    $existingVariantIds[] = $newVariant->id;
                }

                // Delete variants removed from the form if updating
                if ($id && !empty($existingVariantIds)) {
                    ProductVariant::where('product_id', $product->id)
                        ->whereNotIn('id', $existingVariantIds)
                        ->delete();
                }
            });

            $msg = $id ? "Produk '{$name}' berhasil diperbarui." : "Produk baru '{$name}' berhasil ditambahkan.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menyimpan produk: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete a Product
     */
    public function deleteProduct(int $id): array
    {
        try {
            $product = Product::withCount('variants')->findOrFail($id);
            $productName = $product->name;
            $product->delete();

            $msg = "Produk '{$productName}' berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menghapus produk: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Save (Create or Update) a Category
     */
    public function saveCategory(?int $id, string $name, ?string $description = null, bool $isActive = true): array
    {
        $name = trim($name);
        $slug = Str::slug($name);
        $description = $description ? trim($description) : null;

        $rules = [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('categories', 'name')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];

        $validator = validator(
            ['name' => $name, 'description' => $description],
            $rules,
            [
                'name.required' => 'Nama kategori wajib diisi.',
                'name.unique' => 'Nama kategori ini sudah ada.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first('name') ?: $validator->errors()->first('description'),
                'errors' => $validator->errors()->toArray(),
            ];
        }

        try {
            if ($id) {
                $category = Category::findOrFail($id);
                $category->update([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'is_active' => $isActive,
                ]);
            } else {
                Category::create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'is_active' => $isActive,
                ]);
            }

            $msg = $id ? "Kategori '{$name}' berhasil diperbarui." : "Kategori '{$name}' berhasil dibuat.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Terjadi kesalahan: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete a Category
     */
    public function deleteCategory(int $id): array
    {
        try {
            $category = Category::withCount('products')->findOrFail($id);

            if ($category->products_count > 0) {
                $msg = "Kategori '{$category->name}' memiliki {$category->products_count} produk terhubung. Hapus atau pindahkan produk terlebih dahulu.";
                $this->dispatch('show-toast', message: $msg, type: 'error');

                return [
                    'success' => false,
                    'message' => $msg,
                ];
            }

            $catName = $category->name;
            $category->delete();

            $msg = "Kategori '{$catName}' berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menghapus kategori: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Save (Create or Update) a Raw Material
     */
    public function saveRawMaterial(?int $id, string $code, string $name, string $category, string $unit, float $stockQty = 0, float $minStockAlert = 0, float $averageCost = 0, ?string $notes = null, bool $isActive = true): array
    {
        $code = trim(strtoupper($code));
        $name = trim($name);
        $unit = trim(strtolower($unit));
        $notes = $notes ? trim($notes) : null;

        $rules = [
            'code' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('raw_materials', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'category' => ['required', 'string', 'in:ingredient,packaging,other'],
            'unit' => ['required', 'string', 'max:30'],
            'stockQty' => ['required', 'numeric', 'min:0'],
            'minStockAlert' => ['required', 'numeric', 'min:0'],
            'averageCost' => ['required', 'numeric', 'min:0'],
        ];

        $validator = validator(
            [
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'stockQty' => $stockQty,
                'minStockAlert' => $minStockAlert,
                'averageCost' => $averageCost,
            ],
            $rules,
            [
                'code.required' => 'Kode bahan baku wajib diisi.',
                'code.unique' => 'Kode bahan baku ini sudah ada.',
                'name.required' => 'Nama bahan baku wajib diisi.',
                'category.required' => 'Pilih kategori bahan baku.',
                'unit.required' => 'Satuan unit bahan baku wajib diisi.',
                'stockQty.required' => 'Stok saat ini wajib diisi.',
                'minStockAlert.required' => 'Ambang batas stok minimum wajib diisi.',
                'averageCost.required' => 'Biaya beli rata-rata wajib diisi.',
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
            $payload = [
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'stock_qty' => $stockQty,
                'min_stock_alert' => $minStockAlert,
                'average_cost' => $averageCost,
                'notes' => $notes,
                'is_active' => $isActive,
            ];

            if ($id) {
                $material = RawMaterial::findOrFail($id);
                $material->update($payload);
            } else {
                RawMaterial::create($payload);
            }

            $msg = $id ? "Bahan baku '{$name}' berhasil diperbarui." : "Bahan baku '{$name}' berhasil ditambahkan.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menyimpan bahan baku: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete a Raw Material
     */
    public function deleteRawMaterial(int $id): array
    {
        try {
            $material = RawMaterial::withCount('recipeItems')->findOrFail($id);

            if ($material->recipe_items_count > 0) {
                $msg = "Bahan baku '{$material->name}' sedang digunakan pada {$material->recipe_items_count} formula resep. Hapus dari resep terlebih dahulu.";
                $this->dispatch('show-toast', message: $msg, type: 'error');

                return [
                    'success' => false,
                    'message' => $msg,
                ];
            }

            $matName = $material->name;
            $material->delete();

            $msg = "Bahan baku '{$matName}' berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menghapus bahan baku: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Save (Create or Update) a Recipe with its BOM Items
     */
    public function saveRecipe(?int $id, int $productVariantId, string $name, int $batchOutputQty = 1, float $laborCost = 0, float $overheadCost = 0, ?string $notes = null, bool $isActive = true, array $items = []): array
    {
        $name = trim($name);
        $notes = $notes ? trim($notes) : null;

        $rules = [
            'productVariantId' => ['required', 'exists:product_variants,id'],
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'batchOutputQty' => ['required', 'integer', 'min:1'],
            'laborCost' => ['required', 'numeric', 'min:0'],
            'overheadCost' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.raw_material_id' => ['required', 'exists:raw_materials,id'],
            'items.*.quantity_required' => ['required', 'numeric', 'min:0.01'],
        ];

        $validator = validator(
            [
                'productVariantId' => $productVariantId,
                'name' => $name,
                'batchOutputQty' => $batchOutputQty,
                'laborCost' => $laborCost,
                'overheadCost' => $overheadCost,
                'items' => $items,
            ],
            $rules,
            [
                'productVariantId.required' => 'Varian produk wajib dipilih.',
                'name.required' => 'Nama formula resep wajib diisi.',
                'batchOutputQty.required' => 'Target output hasil per batch wajib diisi.',
                'items.required' => 'Minimal harus menyertakan satu bahan baku.',
                'items.min' => 'Minimal harus menyertakan satu bahan baku.',
                'items.*.raw_material_id.required' => 'Pilih bahan baku untuk setiap baris takaran.',
                'items.*.quantity_required.required' => 'Jumlah takaran bahan wajib diisi.',
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
            DB::transaction(function () use ($id, $productVariantId, $name, $batchOutputQty, $laborCost, $overheadCost, $notes, $isActive, $items) {
                if ($id) {
                    $recipe = Recipe::findOrFail($id);
                    $recipe->update([
                        'product_variant_id' => $productVariantId,
                        'name' => $name,
                        'batch_output_qty' => $batchOutputQty,
                        'labor_cost' => $laborCost,
                        'overhead_cost' => $overheadCost,
                        'notes' => $notes,
                        'is_active' => $isActive,
                    ]);
                } else {
                    $recipe = Recipe::create([
                        'product_variant_id' => $productVariantId,
                        'name' => $name,
                        'batch_output_qty' => $batchOutputQty,
                        'labor_cost' => $laborCost,
                        'overhead_cost' => $overheadCost,
                        'notes' => $notes,
                        'is_active' => $isActive,
                    ]);
                }

                // Delete old items and insert updated ones
                $recipe->items()->delete();

                foreach ($items as $itemData) {
                    RecipeItem::create([
                        'recipe_id' => $recipe->id,
                        'raw_material_id' => (int) $itemData['raw_material_id'],
                        'quantity_required' => (float) $itemData['quantity_required'],
                        'notes' => !empty($itemData['notes']) ? trim($itemData['notes']) : null,
                    ]);
                }
            });

            $msg = $id ? "Resep formula '{$name}' berhasil diperbarui." : "Resep formula '{$name}' berhasil ditambahkan.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menyimpan resep: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete a Recipe
     */
    public function deleteRecipe(int $id): array
    {
        try {
            $recipe = Recipe::findOrFail($id);
            $recipeName = $recipe->name;
            $recipe->delete();

            $msg = "Resep formula '{$recipeName}' berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menghapus resep: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Provide dataset for the view
     */
    public function with(): array
    {
        $categories = Category::withCount('products')
            ->orderBy('name', 'asc')
            ->get();

        $products = Product::with(['category', 'variants.defaultRecipe'])
            ->orderBy('name', 'asc')
            ->get();

        $variants = ProductVariant::with(['product.category', 'defaultRecipe'])
            ->orderBy('product_id', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $rawMaterials = RawMaterial::withCount('recipeItems')
            ->orderBy('name', 'asc')
            ->get();

        $recipes = Recipe::with(['productVariant.product', 'items.rawMaterial'])
            ->orderBy('name', 'asc')
            ->get();

        // Calculate helper statistics
        $totalVariants = $variants->count();
        $totalLowStockMaterials = $rawMaterials->filter(fn($m) => $m->isLowStock())->count();
        $totalLowStockProducts = $variants->filter(fn($v) => $v->isLowStock())->count();

        return [
            'categories' => $categories,
            'products' => $products,
            'variants' => $variants,
            'rawMaterials' => $rawMaterials,
            'recipes' => $recipes,
            'totalCategories' => $categories->count(),
            'totalProducts' => $products->count(),
            'totalVariants' => $totalVariants,
            'totalRawMaterials' => $rawMaterials->count(),
            'totalRecipes' => $recipes->count(),
            'totalLowStockMaterials' => $totalLowStockMaterials,
            'totalLowStockProducts' => $totalLowStockProducts,
        ];
    }
};
