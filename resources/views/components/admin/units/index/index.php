<?php

use App\Models\Unit;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Master Satuan - Halala Food')] class extends Component
{
    public function mount()
    {
        if (Gate::denies('satuan-view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat daftar master satuan.');
        }
    }

    public function render()
    {
        $units = Unit::query()
            ->orderBy('name')
            ->get()
            ->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'short_name' => $unit->short_name,
                    'description' => $unit->description ?? '-',
                    'is_active' => (bool) $unit->is_active,
                    'created_at_human' => $unit->created_at ? $unit->created_at->translatedFormat('d M Y') : '-',
                    'edit_url' => route('admin.units.edit', $unit->id),
                ];
            });

        return view('components.admin.units.index.index', [
            'units' => $units,
        ]);
    }

    /**
     * Toggle active/inactive status of a unit.
     */
    public function toggleStatus(int $id): array
    {
        if (Gate::denies('satuan-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengubah status satuan.'];
        }

        $unit = Unit::find($id);
        if (! $unit) {
            return ['success' => false, 'message' => 'Data satuan tidak ditemukan.'];
        }

        $unit->is_active = ! $unit->is_active;
        $unit->save();

        return [
            'success' => true,
            'message' => 'Status satuan '.$unit->name.' berhasil diubah menjadi '.($unit->is_active ? 'Aktif' : 'Nonaktif').'.',
        ];
    }

    /**
     * Delete a unit by ID.
     */
    public function deleteUnit(int $id): array
    {
        if (Gate::denies('satuan-delete')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus satuan.'];
        }

        $unit = Unit::find($id);
        if (! $unit) {
            return ['success' => false, 'message' => 'Data satuan tidak ditemukan.'];
        }

        $name = $unit->name;
        $shortName = $unit->short_name;
        $unit->delete();

        return [
            'success' => true,
            'message' => "Satuan '{$name}' ({$shortName}) berhasil dihapus.",
        ];
    }
};
