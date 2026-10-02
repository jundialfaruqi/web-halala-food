<?php

use App\Models\Unit;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Tambah Satuan Baru - Halala Food')] class extends Component {
    public string $name = '';
    public string $short_name = '';
    public ?string $description = '';
    public bool $is_active = true;

    public function mount()
    {
        if (Gate::denies('satuan-create')) {
            abort(403, 'Anda tidak memiliki izin untuk menambahkan data satuan.');
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['required', 'string', 'max:30', 'unique:units,short_name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap satuan wajib diisi.',
            'name.max' => 'Nama satuan maksimal 100 karakter.',
            'short_name.required' => 'Simbol atau singkatan satuan wajib diisi.',
            'short_name.unique' => 'Simbol atau singkatan satuan ini sudah digunakan.',
            'short_name.max' => 'Simbol satuan maksimal 30 karakter.',
            'description.max' => 'Keterangan maksimal 255 karakter.',
        ];
    }

    public function save()
    {
        if (Gate::denies('satuan-create')) {
            abort(403, 'Anda tidak memiliki izin untuk menambahkan data satuan.');
        }

        $validated = $this->validate();
        $validated['short_name'] = strtolower(trim($validated['short_name']));

        $unit = Unit::create($validated);

        session()->flash('toast', [
            'message' => "Satuan '{$unit->name}' ({$unit->short_name}) berhasil ditambahkan.",
            'type' => 'success',
        ]);
        session()->flash('success', "Satuan '{$unit->name}' ({$unit->short_name}) berhasil ditambahkan.");

        return $this->redirect(route('admin.units'), navigate: true);
    }

    public function render()
    {
        return view('components.admin.units.create.create');
    }
};
