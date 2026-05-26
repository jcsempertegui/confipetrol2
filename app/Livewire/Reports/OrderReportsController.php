<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Branche;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\SalesReportExport;
use Maatwebsite\Excel\Facades\Excel;

class OrderReportsController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $name, $sale_id, $sale, $order_details; 
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
        
        $this->order_details = [];
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $orders = $this->OrdersByDate();
        $this->totales();

        return view('livewire.reports.order_reports', [
            'orders' => $orders, 
            'startCount' => ($orders->currentPage() - 1) * $orders->perPage() + 1
        ])
        ->extends('layouts.theme.app');
    }
        
    public function OrdersByDate()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();
        $branch_id = $this->branch_id;
        $user_id = $this->user_id;
        
        $query = OrderDetail::with([
            'product',
            'order.customer',
            'order.user'
        ])
        ->whereHas('order', function ($query) use ($branch_id,$user_id) {
            $query->where('branch_id', $branch_id);
            if ($user_id) {
                $query->where('user_id', $user_id);
            }
        })
        ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        })
        ->when($this->searchTerm, function ($query) {
            $query->where(function ($q) {
                $q->whereHas('order', function ($q) {
                    $q->where('order_number', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('login', 'like', '%' . $this->searchTerm . '%');
                    })
                    ->orWhereHas('customer', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTerm . '%');
                    });
                })
                ->orWhereHas('product', function ($q) {
                    $q->where('code', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('name', 'like', '%' . $this->searchTerm . '%');
                });
            });
        })
        ->orderBy('id', 'desc')
        ->paginate(10);

        return $query;
    }

    public function totales(){

        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();
        $branch_id = $this->branch_id;
        $user_id = $this->user_id;
        
        $orders = OrderDetail::with([
                    'product',
                    'order.customer',
                    'order.user'
                ])
                ->whereHas('order', function ($query) use ($branch_id, $user_id) {
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
                            
            $this->totalProducts = $orders->count();
            $this->totalQuantitySold = $orders->sum('quantity');
            $this->totalSalesAmount = $orders->sum(function ($order) {
                return $order->quantity * $order->order_price;
            });
    }


    public function orderReportPdf($fromDate, $toDate, $branch_id, $user_id)
    {
        $settings = Setting::first();
        
        $users = User::first();

        $orders = OrderDetail::with([
                    'product',
                    'order.customer',
                    'order.user'
                ])
                ->whereHas('order', function ($query) use ($branch_id, $user_id) {
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

            $totalProducts = $orders->count();
            $totalQuantitySold = $orders->sum('quantity');
            $totalSalesAmount = $orders->sum(function ($order) {
                return $order->quantity * $order->order_price;
            });

            $pdf = PDF::loadView('rooms.reports.reportOrderPdf', [
                'orders' => $orders,
                'users' => $users,
                'settings' => $settings,
                'totalProducts' => $totalProducts,
                'totalQuantitySold' => $totalQuantitySold,
                'totalSalesAmount' => $totalSalesAmount,
            ])
            
            ->setOption('defaultFont', 'sans-serif')  
            ->setPaper('letter', 'landscape') 
            ->setWarnings(false);  
            
        return $pdf->stream('orderPdf.pdf');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
}