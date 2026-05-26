<?php
namespace App\Livewire;

use App\Models\Lot;
use App\Models\Movement;
use Livewire\Component;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HomeController extends Component
{
    public $totalSales, $totalPurchase, $totalPurchaseCredit, $totalSalesCredit, $totalProduct, $totalCustomers, $totalOrders;
    public $mountSales, $mountPurchase, $mountOrders;
    public $fromDate, $branch_id;
    public $sales_purchases = [];
    public $topProducts = [];
    public $lowProducts = [];
    public $topSellers = [];
    public $topCustomers = [];
    public $totalIncomes, $totalExpenses, $totalExpiredLots;
    public $dailySalesData = [];
    public $categorySalesData = [];
    public $incomeExpenseData = [];

    public function mount()
    {
        $this->fromDate = Carbon::now()->format('Y-m');
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function render()
    {
        //$this->totalesByDate();
        return view('livewire.home.home')->extends('layouts.theme.app');
    }

    public function refreshData($branchId = null)
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function getTopProducts()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $topProducts = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->select(
                'products.name',
                'products.code',
                DB::raw('SUM(sale_details.quantity) as total_vendido'),
                DB::raw('SUM(sale_details.subtotal) as total_ingresos')
            )
            ->where('sales.branch_id', $this->branch_id)
            ->where('sales.status', 1)
            ->whereBetween('sales.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('sale_details.product_id', 'products.name', 'products.code')
            ->orderBy('total_vendido', 'DESC')
            ->limit(5)
            ->get();

        $this->topProducts = $topProducts->toArray();
        $this->dispatch('topProductsUpdated', $this->topProducts);
    }

    public function getLowProducts()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $lowProducts = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->select(
                'products.name',
                'products.code',
                DB::raw('SUM(sale_details.quantity) as total_vendido'),
                DB::raw('SUM(sale_details.subtotal) as total_ingresos')
            )
            ->where('sales.branch_id', $this->branch_id)
            ->where('sales.status', 1)
            ->whereBetween('sales.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('sale_details.product_id', 'products.name', 'products.code')
            ->orderBy('total_vendido', 'ASC')
            ->limit(5)
            ->get();

        $this->lowProducts = $lowProducts->toArray();
        $this->dispatch('lowProductsUpdated', $this->lowProducts);
    }

    public function getTopSellers()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $topSellers = DB::table('sales')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->select(
                'users.name',
                'users.email',
                DB::raw('COUNT(sales.id) as total_ventas_count'),
                DB::raw('SUM(sales.total) as total_ventas'),
                DB::raw('AVG(sales.total) as promedio_venta')
            )
            ->where('sales.branch_id', $this->branch_id)
            ->where('sales.status', 1)
            ->whereBetween('sales.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('sales.user_id', 'users.name', 'users.email')
            ->orderBy('total_ventas', 'DESC')
            ->limit(5)
            ->get();

        $this->topSellers = $topSellers->toArray();
        $this->dispatch('topSellersUpdated', $this->topSellers);
    }

    public function getTopCustomers()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $topCustomers = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'customers.name',
                'customers.email',
                'customers.phone',
                DB::raw('COUNT(sales.id) as total_compras_count'),
                DB::raw('SUM(sales.total) as total'),
                DB::raw('AVG(sales.total) as promedio_compra')
            )
            ->where('sales.branch_id', $this->branch_id)
            ->where('sales.status', 1)
            ->whereBetween('sales.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('sales.customer_id', 'customers.name', 'customers.email', 'customers.phone')
            ->orderBy('total', 'DESC')
            ->limit(5)
            ->get();

        $this->topCustomers = $topCustomers->toArray();
        $this->dispatch('topCustomersUpdated', $this->topCustomers);
    }

    public function getSalesAndPurchasesByMonth()
    {
        $sales = DB::table('sales')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total) as total'))
            ->where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $purchases = DB::table('purchases')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total) as total'))
            ->where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $orders = DB::table('sales')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total) as total'))
            ->where('branch_id', $this->branch_id)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthNames = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $months = range(1, 12);
        $salesTotal = [];
        $purchasesTotals = [];
        $ordersTotals = [];
        $labels = [];

        foreach ($months as $month) {
            $salesTotal[] = $sales->get($month, 0);
            $purchasesTotals[] = $purchases->get($month, 0);
            $ordersTotals[] = $orders->get($month, 0);
            $labels[] = $monthNames[$month];
        }

        $this->sales_purchases = [
            'salesTotal' => $salesTotal,
            'purchasesTotals' => $purchasesTotals,
            'ordersTotals' => $ordersTotals,
            'months' => $labels
        ];

        $this->dispatch('dataUpdated', $this->sales_purchases);
    }

    public function getDailySales()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $sales = DB::table('sales')
            ->where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('SUM(total) as total'))
            ->groupBy('day')
            ->pluck('total', 'day');

        $daysInMonth = $startOfMonth->daysInMonth;
        $labels = [];
        $data = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $labels[] = $i;
            $data[] = $sales->get($i, 0);
        }

        $this->dailySalesData = [
            'labels' => $labels,
            'data' => $data
        ];

        $this->dispatch('dailySalesUpdated', $this->dailySalesData);
    }

    public function getSalesByCategory()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $categories = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('categories', 'products.categorie_id', '=', 'categories.id')
            ->where('sales.branch_id', $this->branch_id)
            ->where('sales.status', 1)
            ->whereBetween('sales.created_at', [$startOfMonth, $endOfMonth])
            ->select('categories.name', DB::raw('SUM(sale_details.subtotal) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total', 'DESC')
            ->limit(5)
            ->get();

        $this->categorySalesData = $categories->toArray();
        $this->dispatch('categorySalesUpdated', $this->categorySalesData);
    }

    public function getIncomeVsExpense()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $ingresos = DB::table('cash_transactions')
            ->where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->where('type', 'INGRESO')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $egresos = DB::table('cash_transactions')
            ->where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->where('type', 'EGRESO')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $this->incomeExpenseData = [
            'ingresos' => $ingresos,
            'egresos' => $egresos
        ];

        $this->dispatch('incomeExpenseUpdated', $this->incomeExpenseData);
    }

    public function totalesByDate()
    {
        $startOfMonth = Carbon::parse($this->fromDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->fromDate)->endOfMonth();

        $this->totalOrders = Sale::where('branch_id', $this->branch_id)
            ->where('status', 0)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $this->totalSales = Sale::where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $this->totalPurchase = Purchase::where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $this->totalIncomes = Movement::where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->where('transaction_type', 'income')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $this->totalExpenses = Movement::where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->where('transaction_type', 'expense')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $this->totalProduct = Product::where('status', 1)->count();
        $this->totalCustomers = Customer::where('status', 1)->count();

        $this->mountSales = Sale::where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');

        $this->mountPurchase = Purchase::where('branch_id', $this->branch_id)
            ->where('status', 1)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');

        $this->mountOrders = Sale::where('branch_id', $this->branch_id)
            ->where('status', 0)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');

        $this->totalExpiredLots = Lot::where('branch_id', $this->branch_id)
            ->whereDate('expiration_date', '<=', Carbon::today())
            ->count();

        $this->getSalesAndPurchasesByMonth();
        $this->getDailySales();
        $this->getSalesByCategory();
        $this->getTopProducts();
        $this->getLowProducts();
        $this->getTopSellers();
        $this->getTopCustomers();
        $this->getIncomeVsExpense();
    }
}
?>
