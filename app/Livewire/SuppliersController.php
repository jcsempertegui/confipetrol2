<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class SuppliersController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $name, $contact_person, $document, $phone, $address, $supplier_id;
    public $isEditMode = false;
    public $searchTerm;
    public $roles;

    protected $listeners = ['delete'];

    ///////// ----------- Pagination------------- ////////////
    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }
    ///////// ----------- Fin------------- ////////////
    public function render()
    {
        $suppliers = Supplier::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('document', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.suppliers.suppliers', [
            'suppliers' => $suppliers,
            'startCount' => $suppliers->total() - ($suppliers->currentPage() - 1) * $suppliers->perPage()
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
        $this->supplier_id = '';
        $this->name = '';
        $this->contact_person = '';
        $this->document = '';
        $this->phone = '';
        $this->address = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $rules = [
            'document' => 'required|numeric|digits_between:7,15|unique:suppliers,document,' . ($this->isEditMode ? $this->supplier_id : ''),
            'name' => 'required|min:3',
            'phone' => 'nullable|numeric|digits_between:7,12',
        ];

        $messages = [
            'document.required' => 'El nit es requerido',
            'document.digits_between' => 'El nit debe tener al menos entre 7 y 15 dígitos.',
            'document.numeric' => 'El nit debe contener solo números.',
            'document.unique' => 'El nit ya está en uso',
            'name.required' => 'La razon social es requerida',
            'name.min' => 'La razon social debe tener al menos 3 caracteres',
            'phone.numeric' => 'El teléfono debe contener solo números.',
            'phone.digits_between' => 'El teléfono debe tener entre 7 y 12 dígitos.',
        ];

        // VALIDAR DATOS
        $this->validate($rules, $messages);

        $suppliers = [
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'document' => $this->document,
            'phone' => $this->phone,
            'address' => $this->address,
        ];

        $supplier = Supplier::updateOrCreate(
            ['id' => $this->supplier_id],
            $suppliers
        );

        $message = $this->isEditMode ? 'PROVEEDOR ACTUALIZADO EXITOSAMENTE.' : 'PROVEEDOR CREADO CON ÉXITO.';

        $this->resetInputFields();
        $this->dispatch('supplierStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();

        $supplier = Supplier::findOrFail($id);
        $this->supplier_id = $id;
        $this->name = $supplier->name;
        $this->contact_person = $supplier->contact_person;
        $this->document = $supplier->document;
        $this->phone = $supplier->phone;
        $this->address = $supplier->address;
        $this->status = $supplier->status;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $supplier = Supplier::find($id);

        if ($supplier) {
            $newEstado = $supplier->status == 1 ? 0 : 1;
            $supplier->update([
                'status' => $newEstado
            ]);
            $message = $newEstado == 1 ? 'PROVEEDOR RESTAURADO EXITOSAMENTE.' : 'PROVEEDOR ELIMINADO EXITOSAMENTE.';
            $this->dispatch('supplierDeleted', $message);
        } else {
            session()->flash('message', 'PROVEEDOR NO ENCONTRADO.');
        }
    }
}