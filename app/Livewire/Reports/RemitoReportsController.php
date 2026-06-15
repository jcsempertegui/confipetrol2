<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\Worker;
use App\Models\RemitoDetail;
use App\Models\Branche;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class RemitoReportsController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $searchTerm;
    public $branches, $branch_id;
    public $users, $user_id;
    public $workers, $worker_id;
    public $fromDate, $toDate;
    public $filter_tipo = '';

    public $totalItems, $totalQuantityRemito;

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedFilterTipo()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->users     = User::where('status', 1)->get();
        $this->workers   = Worker::where('status', 1)->orderBy('name')->get();
        $this->branches  = Branche::where('status', 1)->get();
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $this->fromDate  = now()->format('Y-m-d');
        $this->toDate    = now()->format('Y-m-d');
    }

    public function render()
    {
        $query = $this->getRemitosQuery();

        $remitos = (clone $query)->paginate($this->perPage);

        $allRemitos = $query->get();
        $this->calculateRemitoTotals($allRemitos);

        return view('livewire.reports.remito_reports', [
            'remitos'    => $remitos,
            'startCount' => $remitos->total() - ($remitos->currentPage() - 1) * $remitos->perPage(),
        ])->extends('layouts.theme.app');
    }

    public function RemitosByDate()
    {
        $this->resetPage();
    }

    private function getRemitosQuery()
    {
        $fromDate  = Carbon::parse($this->fromDate)->startOfDay();
        $toDate    = Carbon::parse($this->toDate)->endOfDay();
        $branch_id = $this->branch_id;
        $user_id   = $this->user_id;
        $worker_id = $this->worker_id;
        $tipo      = $this->filter_tipo;

        return RemitoDetail::with([
            'product:id,name,code',
            'warehouse:id,name',
            'sku.color:id,name',
            'sku.size:id,name',
            'remito.user:id,name,login',
            'remito.branch:id,name',
            'remito.worker:id,name,last_name',
        ])
            ->whereHas('remito', function ($query) use ($branch_id, $user_id, $worker_id, $tipo) {
                $query->where('branch_id', $branch_id)
                      ->where('status', 1);

                if ($tipo !== '') {
                    $query->where('tipo', $tipo);
                }

                if ($user_id) {
                    $query->where('user_id', $user_id);
                }

                if ($worker_id) {
                    $query->where('worker_id', $worker_id);
                }
            })
            ->when($this->fromDate && $this->toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('remito_details.created_at', [$fromDate, $toDate]);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('remito', function ($subQ) {
                        $subQ->where('remito_number', 'like', '%' . $this->searchTerm . '%')
                             ->orWhere('contrato', 'like', '%' . $this->searchTerm . '%')
                             ->orWhere('campo', 'like', '%' . $this->searchTerm . '%')
                             ->orWhere('n_orden', 'like', '%' . $this->searchTerm . '%')
                             ->orWhereHas('user', function ($u) {
                                 $u->where('login', 'like', '%' . $this->searchTerm . '%');
                             });
                    })
                    ->orWhereHas('product', function ($p) {
                        $p->where('code', 'like', '%' . $this->searchTerm . '%')
                          ->orWhere('name', 'like', '%' . $this->searchTerm . '%');
                    });
                });
            })
            ->orderBy('remito_details.id', 'desc');
    }

    private function calculateRemitoTotals($collection)
    {
        $this->totalItems          = $collection->count();
        $this->totalQuantityRemito = $collection->sum('quantity');
    }

    public function remitoReportPdf($fromDate, $toDate, $branch_id, $user_id = null, $tipo = '', $worker_id = null)
    {
        $this->fromDate    = $fromDate;
        $this->toDate      = $toDate;
        $this->branch_id   = $branch_id;
        $this->user_id     = $user_id == '0' ? null : $user_id;
        $this->worker_id   = $worker_id == '0' ? null : $worker_id;
        $this->filter_tipo = $tipo == '0' ? '' : $tipo;

        $settings = Setting::first();
        $user     = auth()->user();

        $remitos             = $this->getRemitosQuery()->get();
        $totalItems          = $remitos->count();
        $totalQuantityRemito = $remitos->sum('quantity');

        $pdf = PDF::loadView('rooms.reports.reportRemitoPdf', [
            'remitos'             => $remitos,
            'users'               => $user,
            'settings'            => $settings,
            'totalItems'          => $totalItems,
            'totalQuantityRemito' => $totalQuantityRemito,
            'filter_tipo'         => $this->filter_tipo,
        ])
            ->setOption('defaultFont', 'sans-serif')
            ->setPaper('letter', 'landscape')
            ->setWarnings(false);

        return $pdf->stream('ReporteRemitos.pdf');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
}
