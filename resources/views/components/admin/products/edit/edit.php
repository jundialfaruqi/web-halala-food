<?php

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Edit Produk Jadi - Halala Food')] class extends Component {
    public Product $product;

    public string $name = '';
    public ?int $unit_id = null;
    public float $consignment_price = 0;
    public float $retail_price = 0;
    public int $stock_ready = 0;
    public ?string $description = '';
    public bool $is_active = true;

    public function mount(Product $product)
    {
        if (Gate::denies('produk-edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit produk.');
        }

        $this->product = $product;
        $this->name = $product->name;
        $this->unit_id = $product->unit_id;
        $this->consignment_price = (float) $product->consignment_price;
        $this->retail_price = (float) $product->retail_price;
        $this->stock_ready = (int) $product->stock_ready;
        $this->description = $product->description ?? '';
        $this->is_active = (bool) $product->is_active;
    }

    public function save()
    {
        if (Gate::denies('produk-edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit produk.');
        }

        $this->validate([
            'name' => ['required', 'string', 'max:150', 'unique:products,name,' . $this->product->id],
            'unit_id' => ['required', 'exists:units,id'],
            'consignment_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'stock_ready' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Nama produk kemasan wajib diisi.',
            'name.unique' => 'Nama produk ini sudah terdaftar sebelumnya.',
            'unit_id.required' => 'Pilih satuan kemasan produk.',
            'consignment_price.required' => 'Harga setor konsinyasi wajib diisi.',
            'retail_price.required' => 'Harga eceran toko rekomendasi wajib diisi.',
            'stock_ready.required' => 'Stok barang jadi wajib diisi.',
        ]);

        $unit = Unit::find($this->unit_id);

        $this->product->update([
            'name' => $this->name,
            'unit_id' => $this->unit_id,
            'unit' => $unit?->short_name ?? $this->product->unit,
            'consignment_price' => $this->consignment_price,
            'retail_price' => $this->retail_price,
            'stock_ready' => $this->stock_ready,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        session()->flash('toast', [
            'message' => "Data produk '{$this->product->name}' berhasil diperbarui.",
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
