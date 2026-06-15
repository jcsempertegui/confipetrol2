<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Inventorie;
use App\Models\Branche;
use App\Models\Product;
use App\Models\Lot;
use App\Models\Kardex;
use App\Models\InventoryAdjustment;
use App\Models\ProductSku;
use App\Models\Warehouse;
use App\Models\Setting;
use App\Models\Printer;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Shuchkin\SimpleXLSXGen;

class InventoriesController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $warehousesList = [];
    public $warehouse_id;
    public $searchTerm;

    public $product_id, $product_name, $product_code;
    public $listLots = [];
    public $editingLot = false;
    public $lot_id, $lot_number, $lot_expiration_date, $lot_quantity;

    public $selectedInventory = null;
    public $availableLots = [];
    public $selected_lot_id;
    public $new_quantity;
    public $adjustment_reason;

    public $has_sku = false;
    public $availableSkus = [];
    public $selected_sku_id = '';

    public $skusList = [];
    public $skusProductName = '';

    protected $listeners = ['updateLot'];

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $warehouses = Warehouse::join('branches', 'warehouses.branch_id', '=', 'branches.id')
            ->select('warehouses.id', 'warehouses.name as warehouse_name', 'branches.name as branch_name', 'warehouses.branch_id')
            ->where('warehouses.status', 1)
            ->orderBy('branches.id', 'asc')
            ->orderBy('warehouses.id', 'asc')
            ->get();

        $branchCounts = $warehouses->groupBy('branch_id')->map->count();

        $this->warehousesList = $warehouses->map(function($wh) use ($branchCounts) {
            $wh['display_name'] = $branchCounts[$wh['branch_id']] > 1
                ? $wh['branch_name'] . ' - ' . $wh['warehouse_name']
                : $wh['branch_name'];
            return $wh->toArray();
        })->toArray();

        $branch_id = session('branch_user_id', auth()->user()->branch_id);
        $defaultWarehouse = Warehouse::where('branch_id', $branch_id)->where('is_default', 1)->first();
        $this->warehouse_id = $defaultWarehouse ? $defaultWarehouse->id : ($this->warehousesList[0]['id'] ?? '');
    }

    public function exportExcel()
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(600);

        $branch_id = session('branch_user_id', auth()->user()->branch_id);
        $settings = Setting::where('branch_id', $branch_id)->first();
        $user = auth()->user();
        $date = date('d/m/Y H:i');

        $borderColor = '#d0d0d0';

        $hStyle = '<style bgcolor="#FC0038" color="#FFFFFF" border="1" border-color="' . $borderColor . '" font-size="10"><b>';
        $hEnd = '</b></style>';

        $bodyStyle = '<style font-size="10" border="1" border-color="' . $borderColor . '">';
        $bodyEnd = '</style>';

        $data = [];

        $businessName = $settings ? mb_strtoupper($settings->business) : 'EMPRESA';
        $address = $settings && $settings->branch ? $settings->branch->address : '';
        $phone = $settings && $settings->branch ? $settings->branch->phone : '';

        $data[] = ['<center><style font-size="16"><b>' . $businessName . '</b></style></center>', null, null, null, null, null, null];
        $data[] = ['<center><style color="#444444" font-size="10">' . $address . '</style></center>', null, null, null, null, null, null];
        $data[] = ['<center><style color="#444444" font-size="10">Tel: ' . $phone . '</style></center>', null, null, null, null, null, null];

        $data[] = ['<center><style font-size="12" bgcolor="#EFEFEF"><b>STOCK DE INVENTARIO GENERAL</b></style></center>', null, null, null, null, null, null];

        $data[] = [
            '<style font-size="10"><b>Generado por:</b> ' . ($user->name ?? 'Sistema') . '</style>',
            null, null, null, null,
            '<right><style font-size="10"><b>Fecha:</b> ' . $date . '</style></right>',
            null
        ];

        $data[] = [
            $hStyle . 'CÓDIGO' . $hEnd,
            $hStyle . 'PRODUCTO' . $hEnd,
            $hStyle . 'TIPO PRODUCTO' . $hEnd,
            $hStyle . 'STOCK MINIMO' . $hEnd,
            $hStyle . 'STOCK' . $hEnd,
            $hStyle . 'SUCURSAL / ALMACÉN' . $hEnd,
            null
        ];

        $inventories = Inventorie::with(['product', 'warehouse.branch'])
            ->whereHas('product', function ($query) {
                $query->whereIn('type', [0, 3, 4]);
            })
            ->when(!empty($this->warehouse_id), function ($query) {
                $query->where('warehouse_id', $this->warehouse_id);
            })
            ->when(!empty($this->searchTerm), function ($query) {
                $query->where(function ($q) {
                    $q->where('stock', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('product', function ($productQuery) {
                            $productQuery->where('name', 'like', '%' . $this->searchTerm . '%')
                                ->orWhere('code', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('warehouse.branch', function ($branchQuery) {
                            $branchQuery->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        if ($inventories->isEmpty()) {
            $this->dispatch('alert', 'No hay productos con stock para exportar.', 'warning');
            return;
        }

        $branchCounts = Warehouse::where('status', 1)->get()->groupBy('branch_id')->map->count();

        foreach ($inventories as $inv) {
            $tipoProducto = '';
            if ($inv->product->type == 0) {
                $tipoProducto = 'Producto';
            } elseif ($inv->product->type == 3) {
                $tipoProducto = 'Insumo';
            } else {
                $tipoProducto = 'Otro';
            }

            $branchName = 'S/N';
            if ($inv->warehouse && $inv->warehouse->branch) {
                $bId = $inv->warehouse->branch_id;
                $branchName = $inv->warehouse->branch->name;
                if (isset($branchCounts[$bId]) && $branchCounts[$bId] > 1) {
                    $branchName .= ' - ' . $inv->warehouse->name;
                }
            }

            $data[] = [
                $bodyStyle . '<center>' . ($inv->product->code ?: 'S/N') . '</center>' . $bodyEnd,
                $bodyStyle . ($inv->product->name ?: 'S/N') . $bodyEnd,
                $bodyStyle . '<center>' . strtoupper($tipoProducto) . '</center>' . $bodyEnd,
                $bodyStyle . '<center>' . ($inv->product->minimum_stock ?: 0) . '</center>' . $bodyEnd,
                $bodyStyle . '<center>' . ($inv->stock ?: 0) . '</center>' . $bodyEnd,
                $bodyStyle . '<center>' . $branchName . '</center>' . $bodyEnd,
                null
            ];
        }

        $xlsx = SimpleXLSXGen::fromArray($data);

        $xlsx->mergeCells('A1:G1');
        $xlsx->mergeCells('A2:G2');
        $xlsx->mergeCells('A3:G3');
        $xlsx->mergeCells('A4:G4');
        $xlsx->mergeCells('A5:E5');
        $xlsx->mergeCells('F5:G5');

        $xlsx->setColWidth(1, 15);
        $xlsx->setColWidth(2, 45);
        $xlsx->setColWidth(3, 18);
        $xlsx->setColWidth(4, 15);
        $xlsx->setColWidth(5, 12);
        $xlsx->setColWidth(6, 28);
        $xlsx->setColWidth(7, 5);

        $fileName = 'inventario_valorado_' . $branch_id . '_' . time() . '.xlsx';
        $path = storage_path('app/public/' . $fileName);

        $xlsx->saveAs($path);

        return response()->download($path)->deleteFileAfterSend();
    }

    public function exportPdf()
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(600);

        $branch_id = session('branch_user_id', auth()->user()->branch_id);
        $settings = Setting::where('branch_id', $branch_id)->first();
        $printer = Printer::where('branch_id', $branch_id)->where('status', 1)->first();

        $inventories = Inventorie::with(['product', 'warehouse.branch'])
            ->whereHas('product', function ($query) {
                $query->whereIn('type', [0, 3, 4]);
            })
            ->when(!empty($this->warehouse_id), function ($query) {
                $query->where('warehouse_id', $this->warehouse_id);
            })
            ->when(!empty($this->searchTerm), function ($query) {
                $query->where(function ($q) {
                    $q->where('stock', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('product', function ($productQuery) {
                            $productQuery->where('name', 'like', '%' . $this->searchTerm . '%')
                                ->orWhere('code', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('warehouse.branch', function ($branchQuery) {
                            $branchQuery->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        if ($inventories->isEmpty()) {
            $this->dispatch('alert', 'No hay productos con stock para exportar.', 'warning');
            return;
        }

        $branchCounts = Warehouse::where('status', 1)->get()->groupBy('branch_id')->map->count();

        $pdf = PDF::loadView('rooms.inventoryPdf', [
            'inventories' => $inventories,
            'settings' => $settings,
            'printer' => $printer,
            'branchCounts' => $branchCounts
        ])->setPaper('letter', 'portrait')->setWarnings(false);

        $fileName = 'inventario_valorado_' . $branch_id . '_' . time() . '.pdf';
        $path = storage_path('app/public/' . $fileName);
        $pdf->save($path);

        return response()->download($path)->deleteFileAfterSend();
    }

    public function render()
    {
        $inventories = Inventorie::with([
                'product' => fn($q) => $q->withCount('skus'),
                'warehouse.branch',
            ])
            ->whereHas('product', function ($query) {
                $query->whereIn('type', [0, 3, 4]);
            })
            ->when(!empty($this->warehouse_id), function ($query) {
                $query->where('warehouse_id', $this->warehouse_id);
            })
            ->when(!empty($this->searchTerm), function ($query) {
                $query->where(function ($q) {
                    $q->where('stock', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('product', function ($productQuery) {
                            $productQuery->where('name', 'like', '%' . $this->searchTerm . '%')
                                ->orWhere('code', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('warehouse.branch', function ($branchQuery) {
                            $branchQuery->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        foreach ($inventories as $inv) {
            $inv->branch = $inv->warehouse && $inv->warehouse->branch ? $inv->warehouse->branch : (object)['name' => 'S/N', 'id' => null];
            $inv->branch_id = $inv->branch->id;
        }

        return view('livewire.inventories.inventories', [
            'inventories' => $inventories,
            'startCount' => $inventories->total() - ($inventories->currentPage() - 1) * $inventories->perPage()
        ])->extends('layouts.theme.app');
    }

    public function searchBybranch()
    {
        $this->resetPage();
    }

    public function openAdjustModal($inventory_id)
    {
        $inventory = Inventorie::with(['product', 'warehouse.branch'])->find($inventory_id);

        if (!$inventory) {
            $this->dispatch('alert', 'INVENTARIO NO ENCONTRADO', 'error');
            return;
        }

        $branchName = $inventory->warehouse && $inventory->warehouse->branch ? $inventory->warehouse->branch->name : 'S/N';
        $branchId = $inventory->warehouse && $inventory->warehouse->branch ? $inventory->warehouse->branch->id : null;

        $this->selectedInventory = [
            'id'             => $inventory->id,
            'product_id'     => $inventory->product_id,
            'product_name'   => $inventory->product->name,
            'product_code'   => $inventory->product->code,
            'warehouse_id'   => $inventory->warehouse_id,
            'branch_id'      => $branchId,
            'branch_name'    => $branchName,
            'current_stock'  => $inventory->stock,
            'has_lote'       => false,
            'stock_lot'      => $inventory->stock_lot,
            'stock_nolot'    => $inventory->stock_nolot,
        ];

        if ($this->selectedInventory['has_lote']) {
            $this->availableLots = Lot::where('product_id', $inventory->product_id)
                ->where('branch_id', $branchId)
                ->orderBy('expiration_date', 'asc')
                ->get();
        }

        $skus = ProductSku::with(['color', 'size'])
            ->where('product_id', $inventory->product_id)
            ->where('branch_id', $branchId)
            ->get();

        if ($skus->count() > 0) {
            $this->has_sku = true;
            $this->availableSkus = $skus;
        } else {
            $this->has_sku = false;
            $this->availableSkus = [];
        }

        $this->selected_lot_id   = '';
        $this->selected_sku_id   = '';
        $this->new_quantity      = '';
        $this->adjustment_reason = '';
        $this->resetValidation();
    }

    public function saveStockAdjustment()
    {
        if (!$this->selectedInventory) {
            $this->dispatch('alert', 'ERROR: NO HAY INVENTARIO SELECCIONADO', 'error');
            return;
        }

        $rules = [
            'new_quantity'      => 'required|numeric|min:0',
            'adjustment_reason' => 'required|string|min:3|max:255',
        ];

        $messages = [
            'new_quantity.required'      => 'La cantidad es requerida',
            'new_quantity.numeric'       => 'La cantidad debe ser un número',
            'new_quantity.min'           => 'La cantidad no puede ser negativa',
            'adjustment_reason.required' => 'El motivo del ajuste es obligatorio',
            'adjustment_reason.min'      => 'El motivo debe tener al menos 3 caracteres',
            'adjustment_reason.max'      => 'El motivo no puede superar los 255 caracteres',
        ];

        if ($this->selectedInventory['has_lote']) {
            $rules['selected_lot_id']             = 'required|exists:lots,id';
            $messages['selected_lot_id.required'] = 'Debe seleccionar un lote';
            $messages['selected_lot_id.exists']   = 'El lote seleccionado no existe';
        } elseif ($this->has_sku) {
            $rules['selected_sku_id']             = 'required|exists:product_skus,id';
            $messages['selected_sku_id.required'] = 'Debe seleccionar una talla/color';
            $messages['selected_sku_id.exists']   = 'La talla/color seleccionada no existe';
        }

        $this->validate($rules, $messages);

        try {
            DB::beginTransaction();

            $inventory    = Inventorie::find($this->selectedInventory['id']);
            $costPrice    = 0;
            $lot_id_saved = null;

            if ($this->selectedInventory['has_lote']) {
                $lot = Lot::find($this->selected_lot_id);

                if (!$lot) {
                    throw new \Exception('LOTE NO ENCONTRADO');
                }

                $old_quantity  = $lot->quantity;
                $difference    = $this->new_quantity - $old_quantity;

                $lot->quantity = $this->new_quantity;
                $lot->save();

                $inventory->stock_lot = $inventory->stock_lot + $difference;
                $inventory->save();

                $lot_id_saved = $lot->id;

            } elseif ($this->has_sku) {
                $sku = ProductSku::find($this->selected_sku_id);

                if (!$sku) {
                    throw new \Exception('VARIANTE NO ENCONTRADA');
                }

                $old_quantity = $sku->stock;
                $difference   = $this->new_quantity - $old_quantity;

                $sku->stock = $this->new_quantity;
                $sku->save();

                $inventory->stock_nolot = $inventory->stock_nolot + $difference;
                $inventory->save();

            } else {
                $old_quantity           = $inventory->stock_nolot;
                $difference             = $this->new_quantity - $old_quantity;
                $inventory->stock_nolot = $this->new_quantity;
                $inventory->save();
            }

            $adjustment = InventoryAdjustment::create([
                'type'           => $difference > 0 ? 'Entrada' : ($difference < 0 ? 'Salida' : 'Sin cambio'),
                'previous_stock' => $old_quantity,
                'new_stock'      => $this->new_quantity,
                'difference'     => $difference,
                'reason'         => $this->adjustment_reason,
                'cost'           => $costPrice,
                'total'          => abs($difference) * $costPrice,
                'product_id'     => $this->selectedInventory['product_id'],
                'lot_id'         => $lot_id_saved,
                'branch_id'      => $this->selectedInventory['branch_id'],
                'user_id'        => auth()->id(),
            ]);

            $lastKardex = Kardex::where('product_id', $this->selectedInventory['product_id'])
                ->where('warehouse_id', $this->selectedInventory['warehouse_id'])
                ->where('status', 1)
                ->orderBy('created_at', 'desc')
                ->first();

            $previousBalance = $lastKardex ? $lastKardex->balance : $old_quantity;
            $newBalance      = $previousBalance + $difference;

            Kardex::create([
                'type'             => $difference >= 0 ? 'ENTRADA' : 'SALIDA',
                'description'      => strtoupper($this->adjustment_reason),
                'quantity_in'      => $difference > 0 ? abs($difference) : 0,
                'quantity_out'     => $difference < 0 ? abs($difference) : 0,
                'balance'          => $newBalance,
                'product_id'       => $this->selectedInventory['product_id'],
                'lot_id'           => $lot_id_saved,
                'user_id'          => auth()->id(),
                'branch_id'        => $this->selectedInventory['branch_id'],
                'warehouse_id'     => $this->selectedInventory['warehouse_id'],
                'transaction_type' => 'adjustment',
                'transaction_id'   => $adjustment->id,
                'status'           => 1,
            ]);

            DB::commit();

            $this->selectedInventory = null;
            $this->availableLots     = [];
            $this->availableSkus     = [];
            $this->selected_lot_id   = '';
            $this->selected_sku_id   = '';
            $this->new_quantity      = '';
            $this->adjustment_reason = '';
            $this->has_sku           = false;

            $this->dispatch('alert', 'STOCK AJUSTADO Y KARDEX REGISTRADO', 'success');
            $this->dispatch('closeAdjustModal');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'ERROR AL AJUSTAR STOCK: ' . $e->getMessage(), 'error');
        }
    }

    public function openLotesModal($product_id, $product_name, $product_code, $branch_id)
    {
        $this->product_id   = $product_id;
        $this->product_name = $product_name;
        $this->product_code = $product_code;

        $this->listLots = Lot::where('product_id', $product_id)
            ->where('branch_id', $branch_id)
            ->orderBy('expiration_date', 'asc')
            ->get()
            ->toArray();
    }

    public function editLot($lot_id)
    {
        $lot = Lot::find($lot_id);

        if ($lot) {
            $this->editingLot          = true;
            $this->lot_id              = $lot->id;
            $this->lot_number          = $lot->lot_number;
            $this->lot_expiration_date = $lot->expiration_date;
            $this->lot_quantity        = $lot->quantity;
        }
    }

    public function updateLot()
    {
        $rules = [
            'lot_number'          => 'required|string|max:50',
            'lot_expiration_date' => 'nullable|date',
        ];

        $messages = [
            'lot_number.required'      => 'El número de lote es requerido',
            'lot_expiration_date.date' => 'La fecha de vencimiento debe ser válida',
        ];

        $this->validate($rules, $messages);

        $lot = Lot::find($this->lot_id);

        if ($lot) {
            $existingLot = Lot::where('lot_number', $this->lot_number)
                ->where('product_id', $this->product_id)
                ->where('branch_id', $lot->branch_id)
                ->where('id', '!=', $this->lot_id)
                ->first();

            if ($existingLot) {
                $this->dispatch('alert', 'EL NÚMERO DE LOTE YA EXISTE PARA ESTE PRODUCTO', 'error');
                return;
            }

            $lot->update([
                'lot_number'      => $this->lot_number,
                'expiration_date' => !empty($this->lot_expiration_date) ? $this->lot_expiration_date : null,
            ]);

            $this->listLots = Lot::where('product_id', $this->product_id)
                ->where('branch_id', $lot->branch_id)
                ->orderBy('expiration_date', 'asc')
                ->get()
                ->toArray();

            $this->resetEditLot();
            $this->dispatch('alert', 'LOTE ACTUALIZADO CON ÉXITO', 'success');
        }
    }

    public function cancelEditLot()
    {
        $this->resetEditLot();
    }

    public function resetEditLot()
    {
        $this->editingLot          = false;
        $this->lot_id              = null;
        $this->lot_number          = '';
        $this->lot_expiration_date = '';
        $this->lot_quantity        = '';
        $this->resetValidation();
    }

    public function openSkusModal($inventory_id)
    {
        $inventory = Inventorie::with(['product', 'warehouse.branch'])->find($inventory_id);

        if (!$inventory) {
            $this->dispatch('alert', 'INVENTARIO NO ENCONTRADO', 'error');
            return;
        }

        $branchId = $inventory->warehouse && $inventory->warehouse->branch
            ? $inventory->warehouse->branch->id
            : null;

        $this->skusProductName = $inventory->product->name . ' (' . ($inventory->product->code ?: 'S/C') . ')';

        $this->skusList = ProductSku::with(['color', 'size'])
            ->where('product_id', $inventory->product_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('color_id')
            ->orderBy('size_id')
            ->get()
            ->map(fn($sku) => [
                'color' => $sku->color->name ?? 'S/C',
                'size'  => $sku->size->name  ?? 'S/T',
                'sku'   => $sku->sku          ?? '',
                'stock' => $sku->stock        ?? 0,
            ])
            ->toArray();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
}