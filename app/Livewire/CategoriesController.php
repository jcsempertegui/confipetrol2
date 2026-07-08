<?php

namespace App\Livewire;

use App\Models\Categorie;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriesController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $name, $categorie_id;
    public $isEditMode = false;
    public $searchTerm;
    public $roles;

    protected $listeners = ['delete'];

    public function render()
    {
        $categories = Categorie::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.categories.categories', [
            'categories' => $categories,
            'startCount' => $categories->total() - ($categories->currentPage() - 1) * $categories->perPage()
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
        $this->categorie_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $rules = [
            'name' => 'required|min:2|max:100|unique:categories,name,' . ($this->isEditMode ? $this->categorie_id : ''),
        ];

        $messages = [
            'name.required' => 'El nombre es requerido',
            'name.min'      => 'El nombre debe tener al menos 2 caracteres',
            'name.max'      => 'El nombre no puede superar los 100 caracteres',
            'name.unique'   => 'El nombre ya está en uso',
        ];

        $this->validate($rules, $messages);

        $categories = [
            'name' => $this->name
        ];

        $isEdit = $this->isEditMode;
        $categorie = Categorie::updateOrCreate(
            ['id' => $this->categorie_id],
            $categories
        );

        $this->logActivity(
            'CATEGORIAS', $isEdit ? 'EDITAR' : 'CREAR',
            ($isEdit ? 'Editó' : 'Creó') . " categoría: {$categorie->name}",
            $categorie->id
        );

        $message = $isEdit ? 'CATEGORIA ACTUALIZADA EXITOSAMENTE.' : 'CATEGORIA CREADA CON ÉXITO.';

        $this->resetInputFields();
        $this->dispatch('categorieStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();
        
        $categorie = Categorie::findOrFail($id);
        $this->categorie_id = $id;
        $this->name = $categorie->name;
        $this->status = $categorie->status;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $categorie = Categorie::find($id);

        if ($categorie) {
            $newEstado = $categorie->status == 1 ? 0 : 1;
            $categorie->update(['status' => $newEstado]);
            $this->logActivity(
                'CATEGORIAS', $newEstado == 1 ? 'RESTAURAR' : 'ELIMINAR',
                ($newEstado == 1 ? 'Restauró' : 'Eliminó') . " categoría: {$categorie->name}",
                $categorie->id
            );
            $message = $newEstado == 1 ? 'CATEGORIA RESTAURADA EXITOSAMENTE.' : 'CATEGORIA ELIMINADA EXITOSAMENTE.';
            $this->dispatch('categorieDeleted', $message);
        } else {
            session()->flash('message', 'CATEGORIA NO ENCONTRADA.');
        }
    }
}