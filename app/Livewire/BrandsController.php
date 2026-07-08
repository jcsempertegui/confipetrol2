<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class BrandsController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $name, $brand_id;
    public $isEditMode = false;
    public $searchTerm;

    protected $listeners = ['delete'];

    public function render()
    {
        $brands = Brand::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.brands.brands', [
            'brands' => $brands,
            'startCount' => $brands->total() - ($brands->currentPage() - 1) * $brands->perPage()
        ])
            ->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->name = '';
        $this->brand_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $rules = [
            'name' => 'required|min:2|max:100|unique:brands,name,' . ($this->isEditMode ? $this->brand_id : ''),
        ];

        $messages = [
            'name.required' => 'El nombre es requerido',
            'name.min'      => 'El nombre debe tener al menos 2 caracteres',
            'name.max'      => 'El nombre no puede superar los 100 caracteres',
            'name.unique'   => 'El nombre ya está en uso',
        ];

        $this->validate($rules, $messages);

        $brands = [
            'name' => $this->name
        ];

        $isEdit = $this->isEditMode;
        $brand = Brand::updateOrCreate(['id' => $this->brand_id], $brands);

        $this->logActivity(
            'MARCAS', $isEdit ? 'EDITAR' : 'CREAR',
            ($isEdit ? 'Editó' : 'Creó') . " marca: {$brand->name}",
            $brand->id
        );

        $message = $isEdit ? 'MARCAS ACTUALIZADA EXITOSAMENTE.' : 'MARCAS CREADA CON ÉXITO.';

        $this->resetInputFields();
        $this->dispatch('brandStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();
        
        $brand = Brand::findOrFail($id);
        $this->brand_id = $id;
        $this->name = $brand->name;
        $this->status = $brand->status;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $brand = Brand::find($id);

        if ($brand) {
            $newEstado = $brand->status == 1 ? 0 : 1;
            $brand->update(['status' => $newEstado]);
            $this->logActivity(
                'MARCAS', $newEstado == 1 ? 'RESTAURAR' : 'ELIMINAR',
                ($newEstado == 1 ? 'Restauró' : 'Eliminó') . " marca: {$brand->name}",
                $brand->id
            );
            $message = $newEstado == 1 ? 'MARCAS RESTAURADA EXITOSAMENTE.' : 'MARCAS ELIMINADA EXITOSAMENTE.';
            $this->dispatch('brandDeleted', $message);
        } else {
            session()->flash('message', 'MARCAS NO ENCONTRADA.');
        }
    }
}