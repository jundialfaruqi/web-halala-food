<?php

use App\Models\Store;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Ubah Data Toko Mitra - Halala Food')] class extends Component {
    public ?Store $store = null;
    public ?int $storeId = null;
    public string $name = '';
    public ?string $owner_name = '';
    public ?string $phone = '';
    public ?string $address = '';
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?string $route = '';
    public ?string $notes = '';
    public bool $is_active = true;
    public ?string $photo_data = null;
    public ?string $photo_url = null;

    public function mount(Store $store)
    {
        if (Gate::denies('toko-edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah data toko mitra.');
        }

        $this->store = $store;
        $this->storeId = $store->id;
        $this->name = $store->name;
        $this->owner_name = $store->owner_name;
        $rawPhone = $store->phone ?? '';
        // Normalize to digits only without 62 prefix for display in +62 input
        $digits = preg_replace('/\D/', '', $rawPhone);
        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }
        $this->phone = $digits ?: '';
        $this->address = $store->address;
        $this->latitude = $store->latitude !== null ? (float) $store->latitude : null;
        $this->longitude = $store->longitude !== null ? (float) $store->longitude : null;
        $this->route = $store->route;
        $this->notes = $store->notes;
        $this->is_active = (bool) $store->is_active;
        $this->photo_url = $store->photo_url;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'route' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'photo_data' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (! empty($value) && $value !== 'DELETE') {
                        $error = Store::validatePhotoBase64($value);
                        if ($error) {
                            $fail($error);
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama toko mitra wajib diisi.',
            'name.max' => 'Nama toko mitra maksimal 255 karakter.',
            'latitude.numeric' => 'Latitude harus berupa format angka koordinat.',
            'latitude.between' => 'Latitude harus berada dalam rentang -90 hingga 90.',
            'longitude.numeric' => 'Longitude harus berupa format angka koordinat.',
            'longitude.between' => 'Longitude harus berada dalam rentang -180 hingga 180.',
            'phone.max' => 'Nomor kontak maksimal 50 karakter.',
        ];
    }

    public function update()
    {
        if (Gate::denies('toko-edit')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah data toko mitra.');
        }

        $validated = $this->validate();

        $this->store->update([
            'name' => trim($validated['name']),
            'owner_name' => ! empty($validated['owner_name']) ? trim($validated['owner_name']) : null,
            'phone' => ! empty($validated['phone']) ? '62' . preg_replace('/\D/', '', $validated['phone']) : null,
            'address' => ! empty($validated['address']) ? trim($validated['address']) : null,
            'latitude' => isset($validated['latitude']) && $validated['latitude'] !== '' && $validated['latitude'] !== null ? (float) $validated['latitude'] : null,
            'longitude' => isset($validated['longitude']) && $validated['longitude'] !== '' && $validated['longitude'] !== null ? (float) $validated['longitude'] : null,
            'route' => ! empty($validated['route']) ? trim($validated['route']) : null,
            'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            'is_active' => (bool) $validated['is_active'],
        ]);

        if ($this->photo_data === 'DELETE') {
            if ($this->store->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->store->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->store->photo);
            }
            $this->store->photo = null;
            $this->store->save();
        } elseif (! empty($this->photo_data)) {
            $this->store->updatePhotoFromBase64($this->photo_data);
        }

        session()->flash('toast', [
            'message' => "Perubahan data toko '{$this->store->name}' berhasil disimpan.",
            'type' => 'success',
        ]);
        session()->flash('success', "Perubahan data toko '{$this->store->name}' berhasil disimpan.");

        return $this->redirect(route('admin.stores'), navigate: true);
    }

    public function render()
    {
        $existingRoutes = Store::whereNotNull('route')
            ->where('route', '!=', '')
            ->distinct()
            ->orderBy('route')
            ->pluck('route')
            ->toArray();

        return view('components.admin.stores.edit.edit', [
            'existingRoutes' => $existingRoutes,
        ]);
    }
};
