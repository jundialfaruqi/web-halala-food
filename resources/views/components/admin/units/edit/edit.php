<?php

use App\Models\Unit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Ubah Data Satuan - Halala Food')] class extends Component
{
    public ?Unit $unit = null;

    public ?int $unitId = null;

    public string $name = '';

    public string $short_name = '';

    public ?string $description = '';

    public bool $is_active = true;

    public function mount(Unit $unit)
    {
        if (Gate::denies('satuan-edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah data satuan.');
        }

        $this->unit = $unit;
        $this->unitId = $unit->id;
        $this->name = $unit->name;
        $this->short_name = $unit->short_name;
        $this->description = $unit->description;
        $this->is_active = (bool) $unit->is_active;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['required', 'string', 'max:30', Rule::unique('units', 'short_name')->ignore($this->unitId)],
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

    public function update()
    {
        if (Gate::denies('satuan-edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah data satuan.');
        }

        $validated = $this->validate();
        $validated['short_name'] = strtolower(trim($validated['short_name']));

        $this->unit->update($validated);

        session()->flash('toast', [
            'message' => "Perubahan data satuan '{$this->unit->name}' berhasil disimpan.",
            'type' => 'success',
        ]);
        session()->flash('success', "Perubahan data satuan '{$this->unit->name}' berhasil disimpan.");

        return $this->redirect(route('admin.units'), navigate: true);
    }

    public function render()
    {
        return view('components.admin.units.edit.edit');
    }
};
