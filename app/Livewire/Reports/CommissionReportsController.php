<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\SaleDetail;
use App\Models\Branche;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CommissionReportsController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $searchTerm;
    public $branches, $branch_id;
    public $users, $user_id;
    public $fromDate, $toDate;

    public $totalPaid = 0;
    public $totalItems = 0;
    public $totalSubtotal = 0;

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->users = User::where('status', 1)->get();
        $this->branches = Branche::where('status', 1)->get();
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $query = $this->getCommissionsQuery();

        $commissions = (clone $query)->paginate($this->perPage);

        $allCommissions = (clone $query)->get();
        $this->totalItems = $allCommissions->count();
        $this->totalSubtotal = $allCommissions->sum('subtotal');
        $this->totalPaid = $allCommissions->sum('commission_amount');

        return view('livewire.reports.commission_reports', [
            'commissions' => $commissions,
            'startCount' => $commissions->total() - ($commissions->currentPage() - 1) * $commissions->perPage()
        ])->extends('layouts.theme.app');
    }

    public function SalesByDate()
    {
        $this->resetPage();
    }

    private function getCommissionsQuery()
    {
        $fromDate = Carbon::parse($this->fromDate)->startOfDay();
        $toDate = Carbon::parse($this->toDate)->endOfDay();
        $branch_id = $this->branch_id;

        $query = SaleDetail::query()
            ->with(['product', 'employee', 'sale.customer'])
            ->select(
                'sale_details.*',
                'commissions.percentage as commission_percentage',
                'commissions.amount as commission_amount'
            )
            ->join('commissions', 'sale_details.id', '=', 'commissions.sale_detail_id')
            ->where('commissions.status', 1)
            ->whereNotNull('sale_details.employee_id')
            ->whereHas('sale', function ($q) use ($branch_id, $fromDate, $toDate) {
                $q->where('branch_id', $branch_id)
                  ->where('status', 1)
                  ->whereBetween('created_at', [$fromDate, $toDate]);
            });

        if ($this->user_id) {
            $query->where('sale_details.employee_id', $this->user_id);
        }

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->whereHas('employee', function ($e) {
                    $e->where('name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('lastname', 'like', '%' . $this->searchTerm . '%');
                })
                ->orWhereHas('sale', function ($s) {
                    $s->where('sale_number', 'like', '%' . $this->searchTerm . '%');
                })
                ->orWhereHas('product', function ($p) {
                    $p->where('name', 'like', '%' . $this->searchTerm . '%');
                });
            });
        }

        return $query->orderBy('sale_details.id', 'desc');
    }

    public function commissionReportPdf($fromDate, $toDate, $branch_id, $user_id = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->branch_id = $branch_id;
        $this->user_id = $user_id == '0' ? null : $user_id;

        $settings = Setting::first();
        $user = auth()->user();

        $commissionsData = $this->getCommissionsQuery()->get();
        $totalItems = $commissionsData->count();
        $totalSubtotal = $commissionsData->sum('subtotal');
        $totalPaid = $commissionsData->sum('commission_amount');

        $pdf = Pdf::loadView('rooms.reports.reportCommissionPdf', [
            'commissions' => $commissionsData,
            'user' => $user,
            'settings' => $settings,
            'totalItems' => $totalItems,
            'totalSubtotal' => $totalSubtotal,
            'totalPaid' => $totalPaid,
        ])
        ->setOption('defaultFont', 'sans-serif')
        ->setPaper('letter', 'landscape')
        ->setWarnings(false);

        return $pdf->stream('ReporteComisiones.pdf');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
}