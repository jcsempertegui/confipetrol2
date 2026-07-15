<?php

namespace App\Livewire;

use App\Models\Log;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class LogsController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $searchTerm = '';

    public $fromDate;

    public $toDate;

    public $filter_modulo = '';

    public $filter_accion = '';

    public $perPage = 20;

    public array $expandedLogs = [];

    public $perPageOptions = [20, 50, 100];

    public $moduloOptions = [
        '' => 'Todos los módulos',
        'ACCESO' => 'Acceso al sistema',
        'USUARIOS' => 'Usuarios',
        'ROLES' => 'Roles y permisos',
        'CATEGORIAS' => 'Categorías y atributos',
        'PRODUCTOS' => 'Productos',
        'BACKUPS' => 'Backups',
    ];

    public $accionOptions = [
        '' => 'Todas las acciones',
        'INICIO_SESION' => 'Inicio de sesión',
        'CIERRE_SESION' => 'Cierre de sesión',
        'CREAR' => 'Creación',
        'EDITAR' => 'Modificación',
        'ELIMINAR' => 'Desactivación / eliminación',
        'RESTAURAR' => 'Activación / restauración',
        'CAMBIO_CONTRASENA' => 'Cambio de contraseña',
    ];

    public function mount(): void
    {
        $this->fromDate = now()->subDays(6)->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function updated($property): void
    {
        if (in_array($property, ['searchTerm', 'filter_modulo', 'filter_accion', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function filterLogs(): void
    {
        $this->validate([
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after_or_equal:fromDate',
        ], [
            'fromDate.required' => 'Selecciona la fecha inicial.',
            'toDate.required' => 'Selecciona la fecha final.',
            'toDate.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ]);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['searchTerm', 'filter_modulo', 'filter_accion']);
        $this->fromDate = now()->subDays(6)->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->resetValidation();
        $this->resetPage();
    }

    public function toggleDetails(int $logId): void
    {
        $this->expandedLogs[$logId] = ! ($this->expandedLogs[$logId] ?? false);
    }

    public function render()
    {
        $query = Log::with('user')
            ->when($this->searchTerm, function ($query) {
                $term = '%'.$this->searchTerm.'%';
                $query->where(fn ($sub) => $sub->where('descripcion', 'like', $term)
                    ->orWhere('actor_login', 'like', $term)
                    ->orWhere('modulo', 'like', $term)
                    ->orWhere('accion', 'like', $term)
                    ->orWhere('valores_anteriores', 'like', $term)
                    ->orWhere('valores_nuevos', 'like', $term));
            })
            ->when($this->filter_modulo, fn ($query) => $query->where('modulo', $this->filter_modulo))
            ->when($this->filter_accion, fn ($query) => $query->where('accion', $this->filter_accion));

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [Carbon::parse($this->fromDate)->startOfDay(), Carbon::parse($this->toDate)->endOfDay()]);
        }

        $summaryQuery = clone $query;
        $logs = $query->latest('id')->paginate($this->perPage);

        return view('livewire.logs.logs', [
            'logs' => $logs,
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'changes' => (clone $summaryQuery)->where('accion', 'EDITAR')->count(),
                'users' => (clone $summaryQuery)->distinct()->count('actor_login'),
            ],
        ])->extends('layouts.theme.app');
    }
}
