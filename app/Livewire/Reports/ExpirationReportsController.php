<?php

namespace App\Livewire\Reports;

use App\Models\Lot;
use App\Models\User;
use App\Models\SaleDetail;
use App\Models\Branche;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpirationReportsController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $name, $sale_id, $sale, $sale_details;
    public $sale_number, $total, $customer, $user;

    public $searchTerm;
    public $branches, $branch_id, $users, $user_id;

    //GENERAR REPORTE
    public $fromDate, $toDate;
    public $totalProducts, $totalQuantitySold, $totalSalesAmount;

    public function mount()
    {
        $this->users = User::where('status', 1)->get();

        $this->branches = Branche::where('status', 1)->get();
        $this->branch_id = $this->branches->first()->id ?? null;

        $this->sale_details = [];
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $lots = $this->LotsByDate();
        $this->totales();

        return view('livewire.reports.expiration_reports', [
            'lots' => $lots,
            'startCount' => $lots->total() - ($lots->currentPage() - 1) * $lots->perPage()
        ])
            ->extends('layouts.theme.app');
    }


    public function LotsByDate()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();
        $branch_id = $this->branch_id;

        $query = Lot::with('product')
            ->whereDate('expiration_date', '>=', $fromDate)
            ->whereDate('expiration_date', '<=', $toDate)
            ->when($branch_id, function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('code', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->orderBy('expiration_date', 'asc')
            ->paginate(10);

        return $query;
    }

    public function totales()
    {

        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();
        $branch_id = $this->branch_id;
        $user_id = $this->user_id;

        $sales = SaleDetail::with([
            'product',
            'sale.customer',
            'sale.user'
        ])
            ->whereHas('sale', function ($query) use ($branch_id, $user_id) {
                $query->where('branch_id', $branch_id);
                if ($user_id) {
                    $query->where('user_id', $user_id);
                }
            })
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($fromDate)->startOfDay(),
                    Carbon::parse($toDate)->endOfDay()
                ]);
            })
            ->orderBy('id', 'desc')
            ->get();

        $this->totalProducts = $sales->count();
        $this->totalQuantitySold = $sales->sum('quantity');
        $this->totalSalesAmount = $sales->sum(function ($sale) {
            return $sale->quantity * $sale->sale_price;
        });
    }


    public function saleReportPdf($fromDate, $toDate, $branch_id, $user_id)
    {
        $settings = Setting::first();

        $users = User::first();

        $sales = SaleDetail::with([
            'product',
            'sale.customer',
            'sale.user'
        ])
            ->whereHas('sale', function ($query) use ($branch_id, $user_id) {
                $query->where('branch_id', $branch_id);
                if ($user_id) {
                    $query->where('user_id', $user_id);
                }
            })
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($fromDate)->startOfDay(),
                    Carbon::parse($toDate)->endOfDay()
                ]);
            })
            ->orderBy('id', 'desc')
            ->get();

        $totalProducts = $sales->count();
        $totalQuantitySold = $sales->sum('quantity');
        $totalSalesAmount = $sales->sum(function ($sale) {
            return $sale->quantity * $sale->sale_price;
        });

        $pdf = PDF::loadView('rooms.reports.reportSalePdf', [
            'sales' => $sales,
            'users' => $users,
            'settings' => $settings,
            'totalProducts' => $totalProducts,
            'totalQuantitySold' => $totalQuantitySold,
            'totalSalesAmount' => $totalSalesAmount,
        ])

            ->setOption('defaultFont', 'sans-serif')
            ->setPaper('letter', 'landscape')
            ->setWarnings(false);

        return $pdf->stream('salePdf.pdf');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
}