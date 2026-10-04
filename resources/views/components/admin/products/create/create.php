<?php

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Tambah Produk Jadi Baru - Halala Food')] class extends Component {
    public string $name = '';
    public ?int $unit_id = null;
    public float $consignment_price = 0;
    public float $retail_price = 0;
    public int $stock_ready = 0;
    public ?string $description = '';
    public bool $is_active = true;
    public ?string $photo_data = null;

    public function mount()
    {
        if (Gate::denies('produk-create')) {
            abort(403, 'Anda tidak memiliki izin untuk menambahkan produk baru.');
        }

        $firstUnit = Unit::where('is_active', true)->first();
        if ($firstUnit) {
            $this->unit_id = $firstUnit->id;
        }
    }

    public function save()
    {
        if (Gate::denies('produk-create')) {
            abort(403, 'Anda tidak memiliki izin untuk menambahkan produk baru.');
        }

        $this->validate([
            'name' => ['required', 'string', 'max:150', 'unique:products,name'],
            'unit_id' => ['required', 'exists:units,id'],
            'consignment_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'stock_ready' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'photo_data' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (! empty($value)) {
                        $error = Product::validatePhotoBase64($value);
                        if ($error) {
                            $fail($error);
                        }
                    }
                },
            ],
        ], [
            'name.required' => 'Nama produk kemasan wajib diisi.',
            'name.unique' => 'Nama produk ini sudah terdaftar sebelumnya.',
            'unit_id.required' => 'Pilih satuan kemasan produk.',
            'consignment_price.required' => 'Harga setor konsinyasi wajib diisi.',
            'retail_price.required' => 'Harga eceran toko rekomendasi wajib diisi.',
            'stock_ready.required' => 'Stok awal barang jadi wajib diisi.',
        ]);

        $unit = Unit::find($this->unit_id);

        $product = Product::create([
            'name' => $this->name,
            'unit_id' => $this->unit_id,
            'unit' => $unit?->short_name ?? 'pcs',
            'consignment_price' => $this->consignment_price,
            'retail_price' => $this->retail_price,
            'stock_ready' => $this->stock_ready,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        if (! empty($this->photo_data)) {
            $product->updatePhotoFromBase64($this->photo_data);
        }

        session()->flash('toast', [
            'message' => "Produk kemasan '{$product->name}' berhasil ditambahkan ke katalog.",
            'type' => 'success',
        ]);

        return redirect()->route('admin.products');
    }

    public function with(): array
    {
        return [
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
        ];
    }
};
