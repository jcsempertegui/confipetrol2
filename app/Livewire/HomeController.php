<?php
namespace App\Livewire;

use App\Models\Remito;
use App\Models\Delivery;
use App\Models\Product;
use App\Models\Worker;
use App\Models\Warehouse;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Component
{
    public $totalRemitos, $totalDeliveries, $totalProducts, $totalWorkers;
    public $totalRemitoItems, $totalDeliveryItems, $totalWarehouses, $totalLowStock;
    public $fromDate, $branch_id;
    public $remitos_deliveries = [];
    public $topProductsRemito = [];
    public $topProductsDelivery = [];
    public $topWorkers = [];
    public $dailyRemitosData = [];
    public $remitosByType = [];

    public function mount()
    {
        $this->fromDate = Carbon::now()->format('Y-m');
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function render()
    {
        $this->totalesByDate();
        return view('livewire.home.home')->extends('layouts.theme.app');
    }

    public function refreshData($branchId = null)
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function totalesByDate()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $this->totalRemitos = Remito::where('branch_id', $this->branch_id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $this->totalDeliveries = Delivery::where('branch_id', $this->branch_id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $this->totalRemitoItems = DB::table('remito_details')
            ->join('remitos', 'remito_details.remito_id', '=', 'remitos.id')
            ->where('remitos.branch_id', $this->branch_id)
            ->whereBetween('remitos.created_at', [$startOfMonth, $endOfMonth])
            ->sum('remito_details.quantity');

        $this->totalDeliveryItems = DB::table('delivery_details')
            ->join('deliveries', 'delivery_details.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.branch_id', $this->branch_id)
            ->whereBetween('deliveries.created_at', [$startOfMonth, $endOfMonth])
            ->sum('delivery_details.quantity');

        $this->totalProducts = Product::where('status', 1)->count();
        $this->totalWorkers = Worker::where('status', 1)->count();
        $this->totalWarehouses = Warehouse::where('branch_id', $this->branch_id)->where('status', 1)->count();

        $this->totalLowStock = DB::table('inventories')
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->whereRaw('(inventories.stock_lot + inventories.stock_nolot) <= products.minimum_stock')
            ->where('products.status', 1)
            ->count();

        $this->getRemitosAndDeliveriesByMonth();
        $this->getDailyRemitos();
        $this->getRemitosByType();
        $this->getTopProductsRemito();
        $this->getTopProductsDelivery();
        $this->getTopWorkers();
    }

    public function getRemitosAndDeliveriesByMonth()
    {
        $remitos = DB::table('remitos')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
            ->where('branch_id', $this->branch_id)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $deliveries = DB::table('deliveries')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
            ->where('branch_id', $this->branch_id)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthNames = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $remitosTotals = [];
        $deliveriesTotals = [];
        $labels = [];

        foreach (range(1, 12) as $month) {
            $remitosTotals[] = $remitos->get($month, 0);
            $deliveriesTotals[] = $deliveries->get($month, 0);
            $labels[] = $monthNames[$month];
        }

        $this->remitos_deliveries = [
            'remitosTotals' => $remitosTotals,
            'deliveriesTotals' => $deliveriesTotals,
            'months' => $labels,
        ];

        $this->dispatch('dataUpdated', $this->remitos_deliveries);
    }

    public function getDailyRemitos()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $remitos = DB::table('remitos')
            ->where('branch_id', $this->branch_id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day');

        $daysInMonth = $startOfMonth->daysInMonth;
        $labels = [];
        $data = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $labels[] = $i;
            $data[] = $remitos->get($i, 0);
        }

        $this->dailyRemitosData = ['labels' => $labels, 'data' => $data];
        $this->dispatch('dailyRemitosUpdated', $this->dailyRemitosData);
    }

    public function getRemitosByType()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $tipos = DB::table('remitos')
            ->where('branch_id', $this->branch_id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select('tipo', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo')
            ->get();

        $this->remitosByType = $tipos->toArray();
        $this->dispatch('remitosByTypeUpdated', $this->remitosByType);
    }

    public function getTopProductsRemito()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $top = DB::table('remito_details')
            ->join('remitos', 'remito_details.remito_id', '=', 'remitos.id')
            ->join('products', 'remito_details.product_id', '=', 'products.id')
            ->select('products.name', 'products.code', DB::raw('SUM(remito_details.quantity) as total_despachado'))
            ->where('remitos.branch_id', $this->branch_id)
            ->whereBetween('remitos.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('remito_details.product_id', 'products.name', 'products.code')
            ->orderBy('total_despachado', 'DESC')
            ->limit(5)
            ->get();

        $this->topProductsRemito = $top->toArray();
        $this->dispatch('topProductsRemitoUpdated', $this->topProductsRemito);
    }

    public function getTopProductsDelivery()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $top = DB::table('delivery_details')
            ->join('deliveries', 'delivery_details.delivery_id', '=', 'deliveries.id')
            ->join('products', 'delivery_details.product_id', '=', 'products.id')
            ->select('products.name', 'products.code', DB::raw('SUM(delivery_details.quantity) as total_entregado'))
            ->where('deliveries.branch_id', $this->branch_id)
            ->whereBetween('deliveries.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('delivery_details.product_id', 'products.name', 'products.code')
            ->orderBy('total_entregado', 'DESC')
            ->limit(5)
            ->get();

        $this->topProductsDelivery = $top->toArray();
        $this->dispatch('topProductsDeliveryUpdated', $this->topProductsDelivery);
    }

    public function getTopWorkers()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $top = DB::table('deliveries')
            ->join('workers', 'deliveries.worker_id', '=', 'workers.id')
            ->select(
                DB::raw("CONCAT(workers.name, ' ', workers.last_name) as name"),
                DB::raw('COUNT(deliveries.id) as total_entregas')
            )
            ->where('deliveries.branch_id', $this->branch_id)
            ->whereBetween('deliveries.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('deliveries.worker_id', 'workers.name', 'workers.last_name')
            ->orderBy('total_entregas', 'DESC')
            ->limit(5)
            ->get();

        $this->topWorkers = $top->toArray();
        $this->dispatch('topWorkersUpdated', $this->topWorkers);
    }
}
