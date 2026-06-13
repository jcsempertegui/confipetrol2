<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Kardex;
use App\Models\Inventorie;
use App\Models\ProductSku;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KardexsController extends Component
{
    use WithPagination;

    public $searchTerm;
    public $fromDate, $toDate, $products = [], $search;
    public $selectedProduct = null, $product_id, $branch_id;
    public $productSearch;
    public $selectedProductSkus = [];

    protected $paginationTheme = 'bootstrap';

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function refreshData($branchId = null)
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $this->resetPage();
        $this->updatedSearch();
    }

    public function mount()
    {
        $this->fromDate  = now()->format('Y-m-d');
        $this->toDate    = now()->format('Y-m-d');
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        $kardexs = $this->KardexByDate();

        return view('livewire.kardexs.kardexs', [
            'kardexs'      => $kardexs,
            'currentStock' => $this->currentStock,
            'totalIn'      => $this->totalIn,
            'totalOut'     => $this->totalOut,
            'startCount' => $kardexs->total() - ($kardexs->currentPage() - 1) * $kardexs->perPage()
        ])->extends('layouts.theme.app');
    }

    public function updatedFromDate()
    {
        $this->resetPage();
    }

    public function updatedToDate()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $searchTerm = trim($this->search);

        if (strlen($searchTerm) < 2) {
            $this->products = [];
            return;
        }

        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

        $this->products = Product::select([
            'products.id',
            'products.code',
            'products.name',
            'products.lote',
            'inventories.stock_lot',
            'inventories.stock_nolot'
        ])
        ->join('inventories', function($join) use ($warehouseId) {
            $join->on('products.id', '=', 'inventories.product_id')
                 ->where('inventories.warehouse_id', $warehouseId);
        })
        ->where('products.status', 1)
        ->whereIn('products.type', [0, 3, 4])
        ->where(function ($q) use ($searchTerm) {
            $q->where('products.code', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('products.name', 'LIKE', '%' . $searchTerm . '%');
        })
        ->orderBy('products.name', 'asc')
        ->limit(7)
        ->get();
    }

    public function codeSearch()
    {
        if (strlen($this->search) > 0) {
            $this->selectProduct($this->search, 'code');
            $this->search   = '';
            $this->products = [];
        }
    }

    public function selectProduct($identifier, $searchType = 'id')
    {
        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

        $query = Product::select(
            'products.id', 'products.code', 'products.name', 'products.type', 'products.lote', 'products.categorie_id', 'products.brand_id',
            'inventories.stock', 'inventories.stock_lot', 'inventories.stock_nolot'
        )
        ->with(['categories', 'brands'])
        ->join('inventories', function($join) use ($warehouseId) {
            $join->on('products.id', '=', 'inventories.product_id')
                 ->where('inventories.warehouse_id', $warehouseId);
        })
        ->where('products.status', 1)
        ->whereIn('products.type', [0, 3, 4]);

        if ($searchType === 'id') {
            $query->where('products.id', $identifier);
        } else {
            $query->where('products.code', $identifier);
        }

        $this->selectedProduct = $query->first();

        if ($this->selectedProduct) {
            $this->product_id    = $this->selectedProduct->id;
            $this->productSearch = $this->selectedProduct->name;
            
            $this->selectedProductSkus = ProductSku::with(['size', 'color'])
                ->where('product_id', $this->product_id)
                ->where('branch_id', $this->branch_id)
                ->get();
                
        } else {
            $this->selectedProduct = null;
            $this->product_id      = null;
            $this->productSearch   = '';
            $this->selectedProductSkus = [];
            $this->dispatch('alert', ['PRODUCTO NO ENCONTRADO', 'error']);
        }
        
        $this->resetPage();
    }

    public function KardexByDate()
    {
        $fromDate  = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate    = Carbon::parse($this->toDate ?? now())->endOfDay();

        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

        return Kardex::with(['user:id,name,login', 'lot:id,lot_number'])
        ->where('status', 1)
        ->where('warehouse_id', $warehouseId)
        ->where('product_id', $this->product_id)
        ->when($this->fromDate && $this->toDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        })
        ->when($this->searchTerm, function ($query) {
            $searchTerm = '%' . $this->searchTerm . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('type', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm)
                  ->orWhereHas('lot', function ($sub) use ($searchTerm) {
                      $sub->where('lot_number', 'like', $searchTerm);
                  })
                  ->orWhereHas('user', function ($sub) use ($searchTerm) {
                      $sub->where('name', 'like', $searchTerm)
                          ->orWhere('login', 'like', $searchTerm);
                  });
            });
        })
        ->orderBy('id', 'desc')
        ->paginate($this->perPage);
    }

    public function getCurrentStockProperty()
    {
        if (!$this->selectedProduct) return 0;
        return $this->selectedProduct->lote == 1
            ? $this->selectedProduct->stock_lot
            : $this->selectedProduct->stock_nolot;
    }

    public function getTotalInProperty()
    {
        if (!$this->selectedProduct) return 0;
        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

        return Kardex::where('product_id', $this->selectedProduct->id)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 1)
            ->sum('quantity_in');
    }

    public function getTotalOutProperty()
    {
        if (!$this->selectedProduct) return 0;
        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

        return Kardex::where('product_id', $this->selectedProduct->id)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 1)
            ->sum('quantity_out');
    }
}