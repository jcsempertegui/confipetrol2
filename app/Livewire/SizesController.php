<?php

namespace App\Livewire;

use App\Models\Size;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class SizesController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $name, $size_id;
    public $isEditMode = false;
    public $searchTerm;

    protected $listeners = ['delete'];

    public function render()
    {
        $sizes = Size::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('livewire.sizes.sizes', [
            'sizes'      => $sizes,
            'startCount' => $sizes->total() - ($sizes->currentPage() - 1) * $sizes->perPage(),
        ])->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->name    = '';
        $this->size_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $this->validate(
            ['name' => 'required|min:1|max:50|unique:sizes,name,' . ($this->isEditMode ? $this->size_id : '')],
            [
                'name.required' => 'El nombre es requerido',
                'name.min'      => 'El nombre no puede estar vacío',
                'name.max'      => 'El nombre no puede superar los 50 caracteres',
                'name.unique'   => 'El nombre ya está en uso',
            ]
        );

        $isEdit = $this->isEditMode;
        $size = Size::updateOrCreate(['id' => $this->size_id], ['name' => $this->name]);

        $this->logActivity(
            'TALLAS', $isEdit ? 'EDITAR' : 'CREAR',
            ($isEdit ? 'Editó' : 'Creó') . " talla: {$size->name}",
            $size->id
        );

        $message = $isEdit ? 'TALLA ACTUALIZADA EXITOSAMENTE.' : 'TALLA CREADA CON ÉXITO.';
        $this->resetInputFields();
        $this->dispatch('sizeStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();
        $size = Size::findOrFail($id);
        $this->size_id = $id;
        $this->name    = $size->name;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $size = Size::find($id);
        if ($size) {
            $newEstado = $size->status == 1 ? 0 : 1;
            $size->update(['status' => $newEstado]);
            $this->logActivity(
                'TALLAS', $newEstado == 1 ? 'RESTAURAR' : 'ELIMINAR',
                ($newEstado == 1 ? 'Restauró' : 'Eliminó') . " talla: {$size->name}",
                $size->id
            );
            $message = $newEstado == 1 ? 'TALLA RESTAURADA EXITOSAMENTE.' : 'TALLA ELIMINADA EXITOSAMENTE.';
            $this->dispatch('sizeDeleted', $message);
        } else {
            session()->flash('message', 'TALLA NO ENCONTRADA.');
        }
    }
}
