<?php

namespace App\Livewire;

use App\Models\Color;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class ColorsController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $name, $color_id;
    public $isEditMode = false;
    public $searchTerm;

    protected $listeners = ['delete'];

    public function render()
    {
        $colors = Color::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('livewire.colors.colors', [
            'colors'     => $colors,
            'startCount' => $colors->total() - ($colors->currentPage() - 1) * $colors->perPage(),
        ])->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->name     = '';
        $this->color_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $this->validate(
            ['name' => 'required|unique:colors,name,' . ($this->isEditMode ? $this->color_id : '')],
            ['name.required' => 'El nombre es requerido', 'name.unique' => 'El nombre ya está en uso']
        );

        $isEdit = $this->isEditMode;
        $color = Color::updateOrCreate(['id' => $this->color_id], ['name' => $this->name]);

        $this->logActivity(
            'COLORES', $isEdit ? 'EDITAR' : 'CREAR',
            ($isEdit ? 'Editó' : 'Creó') . " color: {$color->name}",
            $color->id
        );

        $message = $isEdit ? 'COLOR ACTUALIZADO EXITOSAMENTE.' : 'COLOR CREADO CON ÉXITO.';
        $this->resetInputFields();
        $this->dispatch('colorStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();
        $color = Color::findOrFail($id);
        $this->color_id = $id;
        $this->name     = $color->name;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $color = Color::find($id);
        if ($color) {
            $newEstado = $color->status == 1 ? 0 : 1;
            $color->update(['status' => $newEstado]);
            $this->logActivity(
                'COLORES', $newEstado == 1 ? 'RESTAURAR' : 'ELIMINAR',
                ($newEstado == 1 ? 'Restauró' : 'Eliminó') . " color: {$color->name}",
                $color->id
            );
            $message = $newEstado == 1 ? 'COLOR RESTAURADO EXITOSAMENTE.' : 'COLOR ELIMINADO EXITOSAMENTE.';
            $this->dispatch('colorDeleted', $message);
        } else {
            session()->flash('message', 'COLOR NO ENCONTRADO.');
        }
    }
}
