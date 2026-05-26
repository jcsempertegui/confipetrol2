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

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function mount()
    {
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Log::with('user');

        if (!empty($this->searchTerm)) {
            $query->where(function ($subQuery) {
                $subQuery->where('evento', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('login', 'like', '%' . $this->searchTerm . '%');
                    });
            });
        }

        if (!empty($this->fromDate) && !empty($this->toDate)) {
            $fromDate = Carbon::parse($this->fromDate)->startOfDay();
            $toDate = Carbon::parse($this->toDate)->endOfDay();
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        $logs = $query->orderBy('id', 'desc')->paginate($this->perPage);

        return view('livewire.logs.logs', [
            'logs' => $logs,
            'startCount' => $logs->total() - ($logs->currentPage() - 1) * $logs->perPage()

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