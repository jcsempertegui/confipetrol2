<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\SaleDetail;
use App\Models\Branche;
use App\Models\Setting;
use App\Models\Payment;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleReportsController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $name, $sale_id, $sale, $sale_details;
    public $sale_number, $total, $customer, $user;

    public $searchTerm;
    public $branches, $branch_id;
    public $users, $user_id;
    public $fromDate, $toDate;

    public $totalProducts, $totalQuantitySold, $totalSalesAmount, $totalProfit;

    public $totalPayments, $totalEffective, $totalQR, $totalCard;

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
        $this->sale_details = [];
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $query = $this->getSalesQuery();

        $sales = (clone $query)->paginate($this->perPage);

        $allSales = $query->get();
        $this->calculateSalesTotals($allSales);

        $this->calculatePaymentsTotals();

        return view('livewire.reports.sale_reports', [
            'sales' => $sales,
            'startCount' => $sales->total() - ($sales->currentPage() - 1) * $sales->perPage()
        ])->extends('layouts.theme.app');
    }

    public function SalesByDate()
    {
        $this->resetPage();
    }

    private function getSalesQuery()
    {
        $fromDate = Carbon::parse($this->fromDate)->startOfDay();
        $toDate = Carbon::parse($this->toDate)->endOfDay();
        $branch_id = $this->branch_id;
        $user_id = $this->user_id;

        return SaleDetail::select('sale_details.*', 'units.name as unit_name')
            ->leftJoin('units', 'sale_details.unit_id', '=', 'units.id')
            ->with([
                'product',
                'sale.customer',
                'sale.user',
                'detailSkus.productSku.color',
                'detailSkus.productSku.size'
            ])
            ->whereHas('sale', function ($query) use ($branch_id, $user_id) {
                $query->where('branch_id', $branch_id)
                    ->where('status', 1);

                if ($user_id) {
                    $query->where('user_id', $user_id);
                }
            })
            ->when($this->fromDate && $this->toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('sale_details.created_at', [$fromDate, $toDate]);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('sale', function ($subQ) {
                        $subQ->where('sale_number', 'like', '%' . $this->searchTerm . '%')
                            ->orWhereHas('user', function ($u) {
                                $u->where('login', 'like', '%' . $this->searchTerm . '%');
                            })
                            ->orWhereHas('customer', function ($c) {
                                $c->where('name', 'like', '%' . $this->searchTerm . '%');
                            });
                    })
                        ->orWhereHas('product', function ($p) {
                            $p->where('code', 'like', '%' . $this->searchTerm . '%')
                                ->orWhere('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->orderBy('sale_details.id', 'desc');
    }

    private function getPaymentsQuery()
    {
        $fromDate = Carbon::parse($this->fromDate)->startOfDay();
        $toDate = Carbon::parse($this->toDate)->endOfDay();
        $branch_id = $this->branch_id;
        $user_id = $this->user_id;

        return Payment::where('transaction_type', 'sales')
            ->whereHas('sale', function ($query) use ($branch_id, $user_id) {
                $query->where('branch_id', $branch_id)
                    ->where('status', 1);

                if ($user_id) {
                    $query->where('user_id', $user_id);
                }
            })
            ->when($this->fromDate && $this->toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            });
    }

    private function calculateSalesTotals($salesCollection)
    {
        $this->totalProducts = $salesCollection->count();
        $this->totalQuantitySold = $salesCollection->sum('quantity');

        $this->totalSalesAmount = $salesCollection->sum(function ($sale) {
            return $sale->quantity * $sale->sale_price;
        });

        $this->totalProfit = $salesCollection->sum(function ($sale) {
            return ($sale->sale_price - $sale->purchase_price) * $sale->quantity;
        });
    }

    private function calculatePaymentsTotals()
    {
        $payments = $this->getPaymentsQuery()->get();

        $this->totalPayments = $payments->sum('amount');
        $this->totalEffective = $payments->where('description', 'EFECTIVO')->sum('amount');
        $this->totalQR = $payments->where('description', 'QR')->sum('amount');
        $this->totalCard = $payments->where('description', 'TARJETA')->sum('amount');
    }

    public function saleReportPdf($fromDate, $toDate, $branch_id, $user_id = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->branch_id = $branch_id;
        $this->user_id = $user_id == '0' ? null : $user_id;

        $settings = Setting::first();
        $user = auth()->user();

        $sales = $this->getSalesQuery()->get();
        $payments = $this->getPaymentsQuery()->get();

        $totalProducts = $sales->count();
        $totalQuantitySold = $sales->sum('quantity');
        $totalSalesAmount = $sales->sum(function ($s) {
            return $s->quantity * $s->sale_price; });
        $totalGrossProfit = $sales->sum(function ($s) {
            return ($s->sale_price - $s->purchase_price) * $s->quantity; });

        $totalPayments = $payments->sum('amount');
        $totalEffective = $payments->where('description', 'EFECTIVO')->sum('amount');
        $totalQR = $payments->where('description', 'QR')->sum('amount');
        $totalCard = $payments->where('description', 'TARJETA')->sum('amount');

        $pdf = PDF::loadView('rooms.reports.reportSalePdf', [
            'sales' => $sales,
            'users' => $user,
            'settings' => $settings,
            'totalProducts' => $totalProducts,
            'totalQuantitySold' => $totalQuantitySold,
            'totalSalesAmount' => $totalSalesAmount,
            'totalGrossProfit' => $totalGrossProfit,
            'totalPayments' => $totalPayments,
            'totalEffective' => $totalEffective,
            'totalQR' => $totalQR,
            'totalCard' => $totalCard,
        ])
            ->setOption('defaultFont', 'sans-serif')
            ->setPaper('letter', 'landscape')
            ->setWarnings(false);

        return $pdf->stream('ReporteVentas.pdf');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
}