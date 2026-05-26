<?php

namespace App\Livewire;

use App\Models\Warehouse;
use App\Models\Branche;
use App\Models\Product;
use App\Models\Inventorie;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehousesController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $name, $branch_id_field, $is_default = 0, $warehouse_id;
    public $isEditMode = false;

    public $filter_branch = '';
    public $filter_status = '1';

    public $searchTerm;
    public $branches;

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    protected $listeners = [
        'delete',
    ];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedFilterBranch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filter_branch = '';
        $this->filter_status = '1';
        $this->resetPage();
    }

    public function mount()
    {
        $this->branches = Branche::where('status', 1)->orderBy('id', 'asc')->get();
        $this->branch_id_field = session('branch_user_id', auth()->user()->branch_id);
    }

    public function render()
    {
        $warehouses = Warehouse::query()
            ->with(['branch:id,name'])
            ->when($this->filter_status !== '', function ($q) {
                $q->where('warehouses.status', $this->filter_status);
            })
            ->when($this->filter_branch, function ($q) {
                $q->where('warehouses.branch_id', $this->filter_branch);
            })
            ->when(strlen($this->searchTerm) > 0, function ($query) {
                $query->where(function ($q) {
                    $q->where('warehouses.name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('branch', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.warehouses.warehouses', [
            'warehouses' => $warehouses,
            'startCount' => $warehouses->total() - ($warehouses->currentPage() - 1) * $warehouses->perPage()
        ])->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->name = '';
        $this->branch_id_field = session('branch_user_id', auth()->user()->branch_id);
        $this->is_default = 0;
        $this->warehouse_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $rules = [
            'name' => 'required|min:3',
            'branch_id_field' => 'required|numeric',
        ];

        $messages = [
            'name.required' => 'El nombre del almacén es requerido',
            'name.min' => 'El nombre debe tener al menos 3 caracteres',
            'branch_id_field.required' => 'La sucursal es requerida',
        ];

        $this->validate($rules, $messages);

        DB::beginTransaction();

        try {
            if ($this->is_default) {
                Warehouse::where('branch_id', $this->branch_id_field)
                    ->where('id', '!=', $this->warehouse_id ?: 0)
                    ->update(['is_default' => 0]);
            }

            $warehouseData = [
                'name' => $this->name,
                'branch_id' => $this->branch_id_field,
                'is_default' => $this->is_default ? 1 : 0,
            ];

            $warehouse = Warehouse::updateOrCreate(
                ['id' => $this->warehouse_id],
                $warehouseData
            );

            if (!$this->isEditMode) {
                $products = Product::select('id')->get();
                $existingInventories = Inventorie::whereIn('product_id', $products->pluck('id'))->get()->keyBy('product_id');

                $inventoriesData = [];
                foreach ($products as $product) {
                    $baseInv = $existingInventories->get($product->id);
                    $inventoriesData[] = [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'purchase_price' => $baseInv ? $baseInv->purchase_price : 0,
                        'sale_price' => $baseInv ? $baseInv->sale_price : 0,
                        'profit' => $baseInv ? $baseInv->profit : 25,
                        'stock_lot' => 0,
                        'stock_nolot' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                foreach (array_chunk($inventoriesData, 500) as $chunk) {
                    Inventorie::insert($chunk);
                }
            }

            DB::commit();

            $message = $this->isEditMode ? 'ALMACÉN ACTUALIZADO EXITOSAMENTE.' : 'ALMACÉN CREADO CON ÉXITO.';
            $this->resetInputFields();
            $this->dispatch('warehouseStoreOrUpdate', $message);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("ERROR AL GUARDAR ALMACÉN: " . $e->getMessage());
            $this->dispatch('alert', ['Error al guardar: ' . $e->getMessage(), 'error']);
        }
    }

    public function syncInventory($id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) return;

        $products = Product::select('id')->get();
        $existingInventories = Inventorie::where('warehouse_id', $id)->pluck('product_id')->toArray();

        $missingProducts = $products->whereNotIn('id', $existingInventories);
        $inventoriesData = [];

        foreach ($missingProducts as $product) {
            $baseInv = Inventorie::where('product_id', $product->id)->first();
            $inventoriesData[] = [
                'product_id' => $product->id,
                'warehouse_id' => $id,
                'purchase_price' => $baseInv ? $baseInv->purchase_price : 0,
                'sale_price' => $baseInv ? $baseInv->sale_price : 0,
                'profit' => $baseInv ? $baseInv->profit : 25,
                'stock_lot' => 0,
                'stock_nolot' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($inventoriesData, 500) as $chunk) {
            Inventorie::insert($chunk);
        }

        $this->dispatch('alert', ['INVENTARIO SINCRONIZADO CORRECTAMENTE', 'success']);
    }

    public function edit($id)
    {
        $this->resetValidation();

        $warehouse = Warehouse::findOrFail($id);

        $this->warehouse_id = $warehouse->id;
        $this->name = $warehouse->name;
        $this->branch_id_field = $warehouse->branch_id;
        $this->is_default = $warehouse->is_default;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $warehouse = Warehouse::find($id);

        if ($warehouse) {
            $newStatus = $warehouse->status == 1 ? 0 : 1;
            $warehouse->update(['status' => $newStatus]);
            $message = $newStatus == 1 ? 'ALMACÉN RESTAURADO EXITOSAMENTE.' : 'ALMACÉN ELIMINADO EXITOSAMENTE.';
            $this->dispatch('warehouseDeleted', $message);
        } else {
            session()->flash('message', 'ALMACÉN NO ENCONTRADO.');
        }
    }
}