<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\Worker;
use App\Models\DeliveryDetail;
use App\Models\Branche;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DeliveryReportsController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $searchTerm;
    public $branches, $branch_id;
    public $users, $user_id;
    public $workers, $worker_id;
    public $fromDate, $toDate;

    public $totalItems, $totalQuantityDelivered;

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
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
        $query = $this->getDeliveriesQuery();

        $deliveries = (clone $query)->paginate($this->perPage);

        $allDeliveries = $query->get();
        $this->calculateDeliveryTotals($allDeliveries);

        return view('livewire.reports.delivery_reports', [
            'deliveries' => $deliveries,
            'startCount' => $deliveries->total() - ($deliveries->currentPage() - 1) * $deliveries->perPage(),
        ])->extends('layouts.theme.app');
    }

    public function DeliveriesByDate()
    {
        $this->resetPage();
    }

    private function getDeliveriesQuery()
    {
        $fromDate  = Carbon::parse($this->fromDate)->startOfDay();
        $toDate    = Carbon::parse($this->toDate)->endOfDay();
        $branch_id = $this->branch_id;
        $user_id   = $this->user_id;
        $worker_id = $this->worker_id;

        return DeliveryDetail::with([
            'product:id,name,code',
            'warehouse:id,name',
            'sku.color:id,name',
            'sku.size:id,name',
            'delivery.worker:id,name,last_name',
            'delivery.user:id,name,login',
        ])
            ->whereHas('delivery', function ($query) use ($branch_id, $user_id, $worker_id) {
                $query->where('branch_id', $branch_id)
                      ->where('status', 1);

                if ($user_id) {
                    $query->where('user_id', $user_id);
                }

                if ($worker_id) {
                    $query->where('worker_id', $worker_id);
                }
            })
            ->when($this->fromDate && $this->toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('delivery_details.created_at', [$fromDate, $toDate]);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('delivery', function ($subQ) {
                        $subQ->where('delivery_number', 'like', '%' . $this->searchTerm . '%')
                            ->orWhereHas('user', function ($u) {
                                $u->where('login', 'like', '%' . $this->searchTerm . '%');
                            })
                            ->orWhereHas('worker', function ($w) {
                                $w->where('name', 'like', '%' . $this->searchTerm . '%')
                                  ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%');
                            });
                    })
                    ->orWhereHas('product', function ($p) {
                        $p->where('code', 'like', '%' . $this->searchTerm . '%')
                          ->orWhere('name', 'like', '%' . $this->searchTerm . '%');
                    });
                });
            })
            ->orderBy('delivery_details.id', 'desc');
    }

    private function calculateDeliveryTotals($collection)
    {
        $this->totalItems            = $collection->count();
        $this->totalQuantityDelivered = $collection->sum('quantity');
    }

    public function deliveryReportPdf($fromDate, $toDate, $branch_id, $user_id = null, $worker_id = null)
    {
        $this->fromDate   = $fromDate;
        $this->toDate     = $toDate;
        $this->branch_id  = $branch_id;
        $this->user_id    = $user_id == '0' ? null : $user_id;
        $this->worker_id  = $worker_id == '0' ? null : $worker_id;

        $settings = Setting::first();
        $user     = auth()->user();

        $deliveries           = $this->getDeliveriesQuery()->get();
        $totalItems           = $deliveries->count();
        $totalQuantityDelivered = $deliveries->sum('quantity');

        $pdf = PDF::loadView('rooms.reports.reportDeliveryPdf', [
            'deliveries'            => $deliveries,
            'users'                 => $user,
            'settings'              => $settings,
            'totalItems'            => $totalItems,
            'totalQuantityDelivered' => $totalQuantityDelivered,
        ])
            ->setOption('defaultFont', 'sans-serif')
            ->setPaper('letter', 'landscape')
            ->setWarnings(false);

        return $pdf->stream('ReporteEntregas.pdf');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
}
