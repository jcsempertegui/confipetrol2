<?php

namespace App\Livewire;

use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class LogsController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $searchTerm;
    public $fromDate;
    public $toDate;
    public $filter_modulo = '';
    public $filter_accion = '';

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public $moduloOptions = [
        '' => 'TODOS LOS MÓDULOS',
        'ACCESO' => 'ACCESO',
        'USUARIOS' => 'USUARIOS',
        'ROLES' => 'ROLES',
        'TRABAJADORES' => 'TRABAJADORES',
        'PRODUCTOS' => 'PRODUCTOS',
        'CATEGORIAS' => 'CATEGORÍAS',
        'MARCAS' => 'MARCAS',
        'UNIDADES' => 'UNIDADES',
        'TALLAS' => 'TALLAS',
        'COLORES' => 'COLORES',
        'ENTREGAS' => 'ENTREGAS',
        'REMITOS' => 'REMITOS',
        'INVENTARIO' => 'INVENTARIO',
        'ALMACENES' => 'ALMACENES',
        'SUCURSALES' => 'SUCURSALES',
        'CONFIGURACION' => 'CONFIGURACIÓN',
    ];

    public $accionOptions = [
        '' => 'TODAS LAS ACCIONES',
        'INICIO_SESION' => 'INICIO SESIÓN',
        'CIERRE_SESION' => 'CIERRE SESIÓN',
        'CREAR' => 'CREAR',
        'EDITAR' => 'EDITAR',
        'ELIMINAR' => 'ELIMINAR',
        'RESTAURAR' => 'RESTAURAR',
        'ANULAR' => 'ANULAR',
        'CAMBIO_CONTRASENA' => 'CAMBIO CONTRASEÑA',
    ];

    public function mount()
    {
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate   = now()->format('Y-m-d');
    }

    public function updatedPerPage() { $this->resetPage(); }
    public function updatedFilterModulo() { $this->resetPage(); }
    public function updatedFilterAccion() { $this->resetPage(); }

    public function render()
    {
        $query = Log::with('user');

        if (!empty($this->searchTerm)) {
            $query->where(function ($sub) {
                $sub->where('descripcion', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('modulo', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('accion', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('login', 'like', '%' . $this->searchTerm . '%')
                          ->orWhere('name', 'like', '%' . $this->searchTerm . '%');
                    });
            });
        }

        if (!empty($this->filter_modulo)) {
            $query->where('modulo', $this->filter_modulo);
        }

        if (!empty($this->filter_accion)) {
            $query->where('accion', $this->filter_accion);
        }

        if (!empty($this->fromDate) && !empty($this->toDate)) {
            $fromDate = Carbon::parse($this->fromDate)->startOfDay();
            $toDate   = Carbon::parse($this->toDate)->endOfDay();
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        $logs = $query->orderBy('id', 'desc')->paginate($this->perPage);

        return view('livewire.logs.logs', [
            'logs'       => $logs,
            'startCount' => $logs->total() - ($logs->currentPage() - 1) * $logs->perPage(),
        ])->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function filterLogs()
    {
        if (empty($this->fromDate) || empty($this->toDate)) {
            $this->dispatch('errorDate', 'DEBE INGRESAR AMBAS FECHAS PARA EL REPORTE.');
            return;
        }

        if (Carbon::parse($this->fromDate)->gt(Carbon::parse($this->toDate))) {
            $this->dispatch('errorDate', 'LA FECHA INICIAL NO PUEDE SER POSTERIOR A LA FINAL.');
            return;
        }

        $this->resetPage();
    }
}
