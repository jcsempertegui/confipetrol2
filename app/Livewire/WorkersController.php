<?php

namespace App\Livewire;

use App\Models\Worker;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class WorkersController extends Component
{
    use AuditLog, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $workerId;

    public $document = '';

    public $name = '';

    public $lastname = '';

    public $position = '';

    public $area = '';

    public $phone = '';

    public $email = '';

    public $start_date = '';

    public $notes = '';

    public $status = true;

    public $searchTerm = '';

    public $statusFilter = 'active';

    public function render()
    {
        $workers = Worker::query()
            ->when($this->searchTerm, function ($query) {
                $term = '%'.trim($this->searchTerm).'%';
                $query->where(fn ($q) => $q->where('code', 'like', $term)
                    ->orWhere('document', 'like', $term)->orWhere('name', 'like', $term)
                    ->orWhere('lastname', 'like', $term)->orWhere('area', 'like', $term)
                    ->orWhere('position', 'like', $term));
            })
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('status', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('status', false))
            ->orderBy('lastname')->orderBy('name')->paginate(15);

        return view('livewire.workers.workers', compact('workers'))->extends('layouts.theme.app');
    }

    public function updated($property): void
    {
        if (in_array($property, ['searchTerm', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->workerId ? 'editar-trabajador' : 'crear-trabajador'), 403);
        $this->normalizeInputs();
        $data = $this->validate([
            'document' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9.\-]+$/', Rule::unique('workers')->ignore($this->workerId)],
            'name' => ['required', 'string', 'max:100', "regex:/^[\pL\pM '\-.]+$/u"],
            'lastname' => ['required', 'string', 'max:100', "regex:/^[\pL\pM '\-.]+$/u"],
            'position' => 'nullable|string|max:120',
            'area' => 'nullable|string|max:120',
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\- ]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:150', Rule::unique('workers')->ignore($this->workerId)],
            'start_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ], [
            'document.regex' => 'El documento solo puede contener letras, números, puntos y guiones.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'lastname.regex' => 'Los apellidos contienen caracteres no permitidos.',
            'phone.regex' => 'El teléfono contiene caracteres no permitidos.',
            'start_date.before_or_equal' => 'La fecha de ingreso no puede ser futura.',
        ]);

        $before = $this->workerId ? $this->snapshot(Worker::findOrFail($this->workerId)) : null;
        $worker = DB::transaction(function () use ($data) {
            $worker = Worker::updateOrCreate(['id' => $this->workerId], $data);
            if (! $worker->code) {
                $worker->update(['code' => 'TRB-'.str_pad((string) $worker->id, 6, '0', STR_PAD_LEFT)]);
            }

            return $worker->fresh();
        });

        $this->logActivity('TRABAJADORES', $this->workerId ? 'EDITAR' : 'CREAR', 'Trabajador '.$worker->full_name, $worker->id, $before, $this->snapshot($worker));
        $this->resetForm();
        $this->dispatch('alert', 'Trabajador guardado correctamente.', 'success');
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('editar-trabajador'), 403);
        $worker = Worker::findOrFail($id);
        $this->workerId = $worker->id;
        foreach (['document', 'name', 'lastname', 'position', 'area', 'phone', 'email', 'notes'] as $field) {
            $this->{$field} = $worker->{$field} ?? '';
        }
        $this->start_date = $worker->start_date?->format('Y-m-d') ?? '';
        $this->status = $worker->status;
        $this->resetValidation();
    }

    public function toggleStatus(int $id): void
    {
        $worker = Worker::findOrFail($id);
        $permission = $worker->status ? 'eliminar-trabajador' : 'restaurar-trabajador';
        abort_unless(auth()->user()->can($permission), 403);
        $before = $this->snapshot($worker);
        $worker->update(['status' => ! $worker->status]);
        $this->logActivity('TRABAJADORES', $worker->status ? 'RESTAURAR' : 'ELIMINAR', ($worker->status ? 'Activación de ' : 'Desactivación de ').$worker->full_name, $worker->id, $before, $this->snapshot($worker->fresh()));
        $this->dispatch('alert', $worker->status ? 'Trabajador activado.' : 'Trabajador desactivado.', 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['workerId', 'document', 'name', 'lastname', 'position', 'area', 'phone', 'email', 'start_date', 'notes']);
        $this->status = true;
        $this->resetValidation();
    }

    private function normalizeInputs(): void
    {
        $this->document = Str::upper(trim($this->document));
        $this->name = preg_replace('/\s+/u', ' ', trim($this->name));
        $this->lastname = preg_replace('/\s+/u', ' ', trim($this->lastname));
        foreach (['position', 'area', 'phone', 'notes'] as $field) {
            $this->{$field} = trim($this->{$field});
        }
        $this->email = Str::lower(trim($this->email));
    }

    private function snapshot(Worker $worker): array
    {
        return [
            'código' => $worker->code, 'documento' => $worker->document,
            'nombre' => $worker->name, 'apellidos' => $worker->lastname,
            'cargo' => $worker->position, 'área' => $worker->area,
            'teléfono' => $worker->phone, 'correo' => $worker->email,
            'fecha_ingreso' => $worker->start_date?->format('Y-m-d'),
            'observaciones' => $worker->notes, 'estado' => $worker->status,
        ];
    }
}
