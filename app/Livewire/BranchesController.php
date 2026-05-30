<?php

namespace App\Livewire;

use App\Models\Branche;
use App\Models\Printer;
use App\Models\Product;
use App\Models\Inventorie;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class BranchesController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $code, $branch_type, $name, $phone, $address, $status, $branche_id;
    public $isEditMode = false;
    public $searchTerm;

    protected $listeners = ['delete'];

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedBranchType()
    {
        if (!$this->isEditMode && $this->branch_type) {
            $this->code = $this->generateBranchCode($this->branch_type);
        }
    }

    private function generateBranchCode($branch_type)
    {
        $prefix = match ($branch_type) {
            'Casa Matriz' => 'CM',
            'Sucursal' => 'SUC',
            default => 'SUC'
        };

        $lastBranch = Branche::where('code', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastBranch) {
            preg_match('/' . $prefix . '(\d+)/', $lastBranch->code, $matches);
            $nextNumber = isset($matches[1]) ? ((int) $matches[1] + 1) : 1;
        }

        return "{$prefix}{$nextNumber}";
    }

    public function render()
    {
        $branches = Branche::where('name', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('code', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.branches.branches', [
            'branches' => $branches,
            'startCount' => $branches->total() - ($branches->currentPage() - 1) * $branches->perPage()
        ])
            ->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function resetInputFields()
    {
        $this->resetValidation();
        $this->code = '';
        $this->branch_type = '';
        $this->name = '';
        $this->phone = '';
        $this->address = '';
        $this->branche_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $rules = [
            'code' => 'required|unique:branches,code,' . ($this->isEditMode ? $this->branche_id : ''),
            'branch_type' => 'required|in:Casa Matriz,Sucursal',
            'name' => 'required',
            'phone' => 'nullable|numeric|digits_between:7,20',
        ];

        $messages = [
            'code.required' => 'El codigo de la sucursal es requerido.',
            'code.unique' => 'El código de la sucursal ya está en uso.',
            'branch_type.required' => 'El tipo sucursal es requerido.',
            'branch_type.in' => 'Seleccione una opción válida.',
            'name.required' => 'El nombre de la sucursal es requerido.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'phone.numeric' => 'El teléfono debe contener solo números.',
            'phone.digits_between' => 'El teléfono debe tener entre 7 y 20 dígitos.',
        ];

        $this->validate($rules, $messages);

        if ($this->branch_type === 'Casa Matriz') {
            $existingCasaMatriz = Branche::where('branch_type', 'Casa Matriz')
                ->when($this->isEditMode, function ($q) {
                    return $q->where('id', '!=', $this->branche_id);
                })
                ->first();

            if ($existingCasaMatriz) {
                $this->dispatch('alert', ['SOLO PUEDE HABER UNA CASA MATRIZ REGISTRADA.', 'error']);
                return;
            }
        }

        $brancheData = [
            'code' => $this->code,
            'branch_type' => $this->branch_type,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
        ];

        $branche = Branche::updateOrCreate(
            ['id' => $this->branche_id],
            $brancheData
        );

        if (!$this->isEditMode) {
            
            $newWarehouse = Warehouse::create([
                'name' => 'Almacén Principal',
                'branch_id' => $branche->id,
                'is_default' => 1,
                'status' => 1
            ]);

            $products = Product::all();

            $inventories = Inventorie::whereIn('product_id', $products->pluck('id'))->get()->keyBy('product_id');

            $inventoriesData = [];

            foreach ($products as $product) {
                $inventory = $inventories->get($product->id);

                $inventoriesData[] = [
                    'purchase_price' => $inventory ? $inventory->purchase_price : 0,
                    'sale_price' => $inventory ? $inventory->sale_price : 0,
                    'profit' => $inventory ? $inventory->profit : 25,
                    'product_id' => $product->id,
                    'warehouse_id' => $newWarehouse->id,
                    'stock_lot' => 0,
                    'stock_nolot' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            foreach (array_chunk($inventoriesData, 500) as $chunk) {
                Inventorie::insert($chunk);
            }

            Setting::create([
                'business' => 'Nombre de Empresa',
                'owner' => 'Propietario',
                'email' => 'correo@example.com',
                'message' => '¡Gracias por su compra!',
                'branch_id' => $branche->id,
            ]);

            Printer::create([
                'name' => 'Impresora Principal',
                'type' => 'ticket',
                'connection_type' => 'none',
                'print_behavior' => 'none',
                'branch_id' => $branche->id,
            ]);
        }

        $isEdit = $this->isEditMode;
        $this->logActivity(
            'SUCURSALES', $isEdit ? 'EDITAR' : 'CREAR',
            ($isEdit ? 'Editó' : 'Creó') . " sucursal: [{$branche->code}] {$branche->name}",
            $branche->id,
            null,
            ['code' => $branche->code, 'name' => $branche->name, 'branch_type' => $branche->branch_type]
        );

        $message = $isEdit ? 'SUCURSAL ACTUALIZADO EXITOSAMENTE.' : 'SUCURSAL CREADO CON ÉXITO.';

        $this->resetInputFields();
        $this->dispatch('branchesStoreOrUpdate', $message);
    }

    public function syncInventory($id)
    {
        $warehouses = Warehouse::where('branch_id', $id)->get();
        $products = Product::select('id')->get();

        foreach ($warehouses as $warehouse) {
            $existingInventories = Inventorie::where('warehouse_id', $warehouse->id)->pluck('product_id')->toArray();
            $missingProducts = $products->whereNotIn('id', $existingInventories);
            $inventoriesData = [];

            foreach ($missingProducts as $product) {
                $baseInv = Inventorie::where('product_id', $product->id)->first();
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

        $this->dispatch('alert', ['INVENTARIOS SINCRONIZADOS CORRECTAMENTE', 'success']);
    }

    public function edit($id)
    {
        $this->resetValidation();
        $branche = Branche::findOrFail($id);
        $this->branche_id = $id;
        $this->code = $branche->code;
        $this->branch_type = $branche->branch_type;
        $this->name = $branche->name;
        $this->phone = $branche->phone;
        $this->address = $branche->address;
        $this->status = $branche->status;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $branche = Branche::find($id);

        if ($branche) {
            $newEstado = $branche->status == 1 ? 0 : 1;
            $branche->update(['status' => $newEstado]);
            $this->logActivity(
                'SUCURSALES', $newEstado == 1 ? 'RESTAURAR' : 'ELIMINAR',
                ($newEstado == 1 ? 'Restauró' : 'Eliminó') . " sucursal: [{$branche->code}] {$branche->name}",
                $branche->id
            );
            $message = $newEstado == 1 ? 'SUCURSAL RESTAURADO EXITOSAMENTE.' : 'SUCURSAL ELIMINADO EXITOSAMENTE.';
            $this->dispatch('branchesDeleted', $message);
        } else {
            session()->flash('message', 'SUCURSAL NO ENCONTRADO.');
        }
    }
}