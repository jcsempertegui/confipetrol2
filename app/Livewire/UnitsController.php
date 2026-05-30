<?php

namespace App\Livewire;

use App\Models\Unit;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class UnitsController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $name, $factor, $base_unit, $unit_id;
    public $isEditMode = false;
    public $searchTerm;

    public $perPage = 10;
    public $perPageOptions = [10, 25, 50, 100];
    public $filter_status = 1;

    public $baseUnitOptions = [
        'ARROBA', 'BALDE', 'BARRIL', 'BIDON', 'BLISTER', 'BOBINA', 'BOLSA', 'BOTELLA',
        'CAJA', 'CARTON', 'CENTIMETRO', 'CENTIMETRO CUADRADO', 'CENTIMETRO CUBICO',
        'CIENTO', 'DOCENA', 'FARDO', 'FRASCO', 'GALON', 'GRAMO', 'HECTAREA',
        'JUEGO', 'KILOGRAMO', 'KILOMETRO', 'KIT', 'LATA', 'LIBRA', 'LITRO',
        'METRO', 'METRO CUADRADO', 'METRO CUBICO', 'MILIGRAMO', 'MILILITRO',
        'MILIMETRO', 'MILLAR', 'ONZA', 'ONZA LIQUIDA', 'PAQUETE', 'PAR',
        'PIE', 'PIEZA', 'PLANCHA', 'PLIEGO', 'PULGADA', 'PUNTO', 'QUINTAL',
        'RESMA', 'ROLLO', 'SACO', 'SET', 'TAMBOR', 'TONELADA', 'TUBO',
        'UNIDAD', 'YARDA'
    ];

    protected $listeners = ['delete'];

    public function updatingSearchTerm() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function render()
    {
        $query = Unit::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc');

        if ($this->filter_status !== '') {
            $query->where('status', (int) $this->filter_status);
        }

        $units = $query->paginate($this->perPage);

        return view('livewire.units.units', [
            'units'           => $units,
            'startCount'      => $units->total() - ($units->currentPage() - 1) * $units->perPage(),
            'baseUnitOptions' => $this->baseUnitOptions,
        ])->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->name      = '';
        $this->factor    = '';
        $this->base_unit = '';
        $this->unit_id   = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $this->validate([
            'name'      => 'required|unique:units,name,' . ($this->isEditMode ? $this->unit_id : ''),
            'base_unit' => 'nullable|string|max:100',
            'factor'    => 'nullable|numeric|min:0',
        ], [
            'name.required'  => 'El nombre es requerido',
            'name.unique'    => 'El nombre ya está en uso',
            'factor.numeric' => 'El factor debe ser un número',
            'factor.min'     => 'El factor debe ser mayor o igual a 0',
        ]);

        $isEdit = $this->isEditMode;
        $unit = Unit::updateOrCreate(
            ['id' => $this->unit_id],
            ['name' => $this->name, 'base_unit' => $this->base_unit ?: null, 'factor' => $this->factor ?: null]
        );

        $this->logActivity(
            'UNIDADES', $isEdit ? 'EDITAR' : 'CREAR',
            ($isEdit ? 'Editó' : 'Creó') . " unidad: {$unit->name}",
            $unit->id
        );

        $message = $isEdit ? 'UNIDAD ACTUALIZADA EXITOSAMENTE.' : 'UNIDAD CREADA CON ÉXITO.';
        $this->resetInputFields();
        $this->dispatch('unitStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();
        $unit = Unit::findOrFail($id);
        $this->unit_id   = $id;
        $this->name      = $unit->name;
        $this->factor    = $unit->factor;
        $this->base_unit = $unit->base_unit;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $unit = Unit::find($id);
        if ($unit) {
            $newEstado = $unit->status == 1 ? 0 : 1;
            $unit->update(['status' => $newEstado]);
            $this->logActivity(
                'UNIDADES', $newEstado == 1 ? 'RESTAURAR' : 'ELIMINAR',
                ($newEstado == 1 ? 'Restauró' : 'Eliminó') . " unidad: {$unit->name}",
                $unit->id
            );
            $message = $newEstado == 1 ? 'UNIDAD RESTAURADA EXITOSAMENTE.' : 'UNIDAD ELIMINADA EXITOSAMENTE.';
            $this->dispatch('unitDeleted', $message);
        } else {
            session()->flash('message', 'UNIDAD NO ENCONTRADA.');
        }
    }
}
