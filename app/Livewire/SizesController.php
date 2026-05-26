<?php

namespace App\Livewire;

use App\Models\Size;
use Livewire\Component;
use Livewire\WithPagination;

class SizesController extends Component
{
    use WithPagination;
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
            'sizes' => $sizes,
            'startCount' => $sizes->total() - ($sizes->currentPage() - 1) * $sizes->perPage()
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
        $this->size_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $rules = [
            'name' => 'required|unique:sizes,name,' . ($this->isEditMode ? $this->size_id : ''),
        ];

        $messages = [
            'name.required' => 'El nombre es requerido',
            'name.unique' => 'El nombre ya está en uso',
        ];

        $this->validate($rules, $messages);

        $data = [
            'name' => $this->name
        ];

        Size::updateOrCreate(
            ['id' => $this->size_id],
            $data
        );

        $message = $this->isEditMode ? 'TALLA ACTUALIZADA EXITOSAMENTE.' : 'TALLA CREADA CON ÉXITO.';

        $this->resetInputFields();
        $this->dispatch('sizeStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();
        
        $size = Size::findOrFail($id);
        $this->size_id = $id;
        $this->name = $size->name;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $size = Size::find($id);

        if ($size) {
            $newEstado = $size->status == 1 ? 0 : 1;
            $size->update([
                'status' => $newEstado
            ]);
            $message = $newEstado == 1 ? 'TALLA RESTAURADA EXITOSAMENTE.' : 'TALLA ELIMINADA EXITOSAMENTE.';
            $this->dispatch('sizeDeleted', $message);
        } else {
            session()->flash('message', 'TALLA NO ENCONTRADA.');
        }
    }
}