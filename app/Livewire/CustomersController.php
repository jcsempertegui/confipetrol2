<?php

namespace App\Livewire;

use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class CustomersController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $name, $document_type, $document, $phone, $email, $address, $status, $customer_id;
    public $isEditMode = false;
    public $searchTerm;

    protected $listeners = ['delete'];

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }
    public function render()
    {
        $customers = Customer::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('document', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('phone', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.customers.customers', [
            'customers' => $customers,
            'startCount' => $customers->total() - ($customers->currentPage() - 1) * $customers->perPage()
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
        $this->document_type = '';
        $this->document = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->customer_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $rules = [
            'name' => 'required|min:3',
            'document_type' => 'required',
            'document' => 'required|numeric|digits_between:7,15|unique:customers,document,' . ($this->isEditMode ? $this->customer_id : ''),
            'phone' => 'nullable|numeric|digits_between:7,12',
            'email' => 'nullable|email|unique:customers,email,' . ($this->isEditMode ? $this->customer_id : ''),
        ];

        $messages = [
            'name.required' => 'La razon social es requerido',
            'name.min' => 'La razon social debe tener al menos 3 caracteres',
            'document.unique' => 'El documento ya está en uso',
            'document_type.required' => 'El tipo de documento es requerido',
            'document.required' => 'El documento es requerido',
            'document.numeric' => 'El documento debe contener solo números.',
            'document.digits_between' => 'El documento debe tener al menos entre 7 y 15 dígitos.',
            'phone.numeric' => 'El teléfono debe contener solo números.',
            'phone.digits_between' => 'El teléfono debe tener entre 7 y 12 dígitos.',
            'email.email' => 'El correo electrónico debe tener un formato válido',
            'email.unique' => 'El correo electrónico ya está en uso',
        ];

        if (empty($this->email)) {
            $this->email = null;
        }
        $this->validate($rules, $messages);

        $customers = [
            'name' => $this->name,
            'document_type' => $this->document_type,
            'document' => $this->document,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
        ];

        $customer = Customer::updateOrCreate(
            ['id' => $this->customer_id],
            $customers
        );

        $message = $this->isEditMode ? 'CLIENTE ACTUALIZADO EXITOSAMENTE.' : 'CLIENTE CREADO CON ÉXITO.';

        $this->resetInputFields();
        $this->dispatch('customerStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();

        $customer = Customer::findOrFail($id);
        $this->customer_id = $id;
        $this->name = $customer->name;
        $this->document_type = $customer->document_type;
        $this->document = $customer->document;
        $this->phone = $customer->phone;
        $this->email = $customer->email;
        $this->address = $customer->address;
        $this->status = $customer->status;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $customer = Customer::find($id);

        if ($customer) {
            $newEstado = $customer->status == 1 ? 0 : 1;
            $customer->update([
                'status' => $newEstado
            ]);
            $message = $newEstado == 1 ? 'CLIENTE RESTAURADO EXITOSAMENTE.' : 'CLIENTE ELIMINADO EXITOSAMENTE.';
            $this->dispatch('customerDeleted', $message);
        } else {
            session()->flash('message', 'CLIENTE NO ENCONTRADO.');
        }
    }
}