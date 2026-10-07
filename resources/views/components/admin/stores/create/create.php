<?php

use App\Models\Store;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Tambah Toko Mitra Baru - Halala Food')] class extends Component {
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

    public function mount()
    {
        if (Gate::denies('toko-create')) {
            abort(403, 'Anda tidak memiliki izin untuk menambahkan data toko mitra.');
        }
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
                    if (! empty($value)) {
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

    public function save()
    {
        if (Gate::denies('toko-create')) {
            abort(403, 'Anda tidak memiliki izin untuk menambahkan data toko mitra.');
        }

        $validated = $this->validate();

        $store = Store::create([
            'name' => trim($validated['name']),
            'owner_name' => ! empty($validated['owner_name']) ? trim($validated['owner_name']) : null,
            'phone' => Store::normalizePhone($validated['phone'] ?? null),
            'address' => ! empty($validated['address']) ? trim($validated['address']) : null,
            'latitude' => isset($validated['latitude']) && $validated['latitude'] !== '' && $validated['latitude'] !== null ? (float) $validated['latitude'] : null,
            'longitude' => isset($validated['longitude']) && $validated['longitude'] !== '' && $validated['longitude'] !== null ? (float) $validated['longitude'] : null,
            'route' => ! empty($validated['route']) ? trim($validated['route']) : null,
            'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            'is_active' => (bool) $validated['is_active'],
        ]);

        if (! empty($this->photo_data)) {
            $store->updatePhotoFromBase64($this->photo_data);
        }

        session()->flash('toast', [
            'message' => "Toko mitra '{$store->name}' berhasil ditambahkan.",
            'type' => 'success',
        ]);
        session()->flash('success', "Toko mitra '{$store->name}' berhasil ditambahkan.");

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

        return view('components.admin.stores.create.create', [
            'existingRoutes' => $existingRoutes,
        ]);
    }
};
