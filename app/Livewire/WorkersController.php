<?php

namespace App\Livewire;

use App\Models\Worker;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class WorkersController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $name, $last_name, $document, $cargo, $birth_date, $phone, $status, $worker_id;
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
        $workers = Worker::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('document', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('cargo', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.workers.workers', [
            'workers' => $workers,
            'startCount' => $workers->total() - ($workers->currentPage() - 1) * $workers->perPage()
        ])->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->name = '';
        $this->last_name = '';
        $this->document = '';
        $this->cargo = '';
        $this->birth_date = '';
        $this->phone = '';
        $this->worker_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $rules = [
            'name'       => 'required|min:2',
            'last_name'  => 'required|min:2',
            'document'   => 'required|numeric|digits_between:7,15|unique:workers,document,' . ($this->isEditMode ? $this->worker_id : ''),
            'cargo'      => 'required|min:2',
            'birth_date' => 'nullable|date',
            'phone'      => 'nullable|numeric|digits_between:7,12',
        ];

        $messages = [
            'name.required'       => 'El nombre es requerido.',
            'name.min'            => 'El nombre debe tener al menos 2 caracteres.',
            'last_name.required'  => 'Los apellidos son requeridos.',
            'last_name.min'       => 'Los apellidos deben tener al menos 2 caracteres.',
            'document.required'   => 'El documento es requerido.',
            'document.numeric'    => 'El documento debe contener solo números.',
            'document.digits_between' => 'El documento debe tener entre 7 y 15 dígitos.',
            'document.unique'     => 'El documento ya está en uso.',
            'cargo.required'      => 'El cargo es requerido.',
            'cargo.min'           => 'El cargo debe tener al menos 2 caracteres.',
            'birth_date.date'     => 'La fecha de nacimiento no es válida.',
            'phone.numeric'       => 'El teléfono debe contener solo números.',
            'phone.digits_between' => 'El teléfono debe tener entre 7 y 12 dígitos.',
        ];

        $this->validate($rules, $messages);

        $isEdit = $this->isEditMode;
        $oldWorker = $isEdit ? Worker::find($this->worker_id) : null;

        $worker = Worker::updateOrCreate(
            ['id' => $this->worker_id],
            [
                'name'       => $this->name,
                'last_name'  => $this->last_name,
                'document'   => $this->document,
                'cargo'      => $this->cargo,
                'birth_date' => $this->birth_date ?: null,
                'phone'      => $this->phone,
            ]
        );

        if ($isEdit) {
            $this->logActivity(
                'TRABAJADORES', 'EDITAR',
                "Editó trabajador: {$worker->name} {$worker->last_name} (CI: {$worker->document})",
                $worker->id,
                $oldWorker ? ['name' => $oldWorker->name, 'last_name' => $oldWorker->last_name, 'cargo' => $oldWorker->cargo] : null,
                ['name' => $worker->name, 'last_name' => $worker->last_name, 'cargo' => $worker->cargo]
            );
        } else {
            $this->logActivity(
                'TRABAJADORES', 'CREAR',
                "Creó trabajador: {$worker->name} {$worker->last_name} (CI: {$worker->document})",
                $worker->id,
                null,
                ['name' => $worker->name, 'last_name' => $worker->last_name, 'cargo' => $worker->cargo]
            );
        }

        $message = $isEdit ? 'TRABAJADOR ACTUALIZADO EXITOSAMENTE.' : 'TRABAJADOR CREADO CON ÉXITO.';

        $this->resetInputFields();
        $this->dispatch('workerStoreOrUpdate', $message);
    }

    public function edit($id)
    {
        $this->resetValidation();

        $worker = Worker::findOrFail($id);
        $this->worker_id  = $id;
        $this->name       = $worker->name;
        $this->last_name  = $worker->last_name;
        $this->document   = $worker->document;
        $this->cargo      = $worker->cargo;
        $this->birth_date = $worker->birth_date;
        $this->phone      = $worker->phone;
        $this->status     = $worker->status;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $worker = Worker::find($id);

        if ($worker) {
            $newEstado = $worker->status == 1 ? 0 : 1;
            $worker->update(['status' => $newEstado]);

            $accion = $newEstado == 1 ? 'RESTAURAR' : 'ELIMINAR';
            $this->logActivity(
                'TRABAJADORES', $accion,
                ($newEstado == 1 ? 'Restauró' : 'Eliminó') . " trabajador: {$worker->name} {$worker->last_name} (CI: {$worker->document})",
                $worker->id,
                ['status' => $newEstado == 1 ? 0 : 1],
                ['status' => $newEstado]
            );

            $message = $newEstado == 1 ? 'TRABAJADOR RESTAURADO EXITOSAMENTE.' : 'TRABAJADOR ELIMINADO EXITOSAMENTE.';
            $this->dispatch('workerDeleted', $message);
        } else {
            session()->flash('message', 'TRABAJADOR NO ENCONTRADO.');
        }
    }
}
