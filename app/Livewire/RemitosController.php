<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Remito;
use App\Models\RemitoDetail;
use App\Models\Inventorie;
use App\Models\Kardex;
use App\Models\Branche;
use App\Models\Warehouse;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Exception;

include_once(base_path('public/assets/plugins/literal.php'));

class RemitosController extends Component
{
    use WithPagination, AuditLog;

    public $search = '';
    public $products = [];

    public $cart = [];
    public $total_items = 0;

    public $branch_id;

    public $selectedProductData = null;
    public $productSkus = [];
    public $selectedSkuId = null;

    public $tipo = 'EGRESO';
    public $contrato = '';
    public $senores = '';
    public $atencion = '';
    public $campo = '';
    public $n_orden = '';
    public $observations = '';
    public $despachado_por = '';
    public $transportado_por = '';
    public $placa = '';
    public $remito_date;

    #[On('branchChanged')]
    public function handleBranchChanged($data = null)
    {
        $branchId = is_array($data) ? ($data['branchId'] ?? null) : $data;
        if ($branchId) {
            $this->branch_id = $branchId;
            session()->put('branch_user_id', $branchId);
            $this->refreshData($branchId);
            $this->resetPage();
        }
    }

    public function mount()
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $this->remito_date = now()->format('Y-m-d');
        $this->refreshData();
    }

    public function refreshData($branchId = null)
    {
        if ($branchId !== null) {
            $this->branch_id = $branchId;
            session()->put('branch_user_id', $branchId);
        } else {
            $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        }

        $cartSessionKey = 'remitos_cart_' . $this->branch_id;
        $this->cart = session()->get($cartSessionKey, []);
        $this->total_items = count($this->cart);
    }

    public function render()
    {
        $this->refreshData();
        return view('livewire.remitos.remitos', [
            'remitos_cart' => $this->cart,
        ])->extends('layouts.theme.app');
    }

    public function updatedTipo()
    {
        $cartSessionKey = 'remitos_cart_' . $this->branch_id;
        if (!empty(session()->get($cartSessionKey, []))) {
            $this->clearRemito();
            $this->dispatch('alert', 'TIPO CAMBIADO — CARRITO LIMPIADO', 'info');
        }
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

        $this->products = Product::with(['brands', 'categories'])
            ->select([
                'products.id',
                'products.code',
                'products.name',
                'products.type',
                'products.brand_id',
                'products.categorie_id',
                'inventories.stock_lot',
                'inventories.stock_nolot',
            ])
            ->leftJoin('inventories', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'inventories.product_id')
                    ->where('inventories.warehouse_id', $warehouseId);
            })
            ->where('products.status', 1)
            ->where(function ($q) use ($searchTerm) {
                $q->where('products.code', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('products.name', 'LIKE', '%' . $searchTerm . '%');
            })
            ->orderBy('products.name', 'asc')
            ->limit(7)
            ->get();
    }

    public function AddOrUpdate($product_id)
    {
        if ($product_id === 'null' || $product_id === null) {
            $this->dispatch('alert', 'NO HAY PRODUCTOS DISPONIBLES O SELECCIONADOS', 'error');
            return;
        }

        $product = Product::where('id', $product_id)
            ->where('status', 1)
            ->first();

        if (!$product) {
            $this->dispatch('alert', 'PRODUCTO NO DISPONIBLE', 'error');
            return;
        }

        $hasSkus = ProductSku::where('product_id', $product_id)
            ->where('branch_id', $this->branch_id)
            ->exists();

        if ($hasSkus) {
            $this->loadSkuModal($product);
        } else {
            $this->processAddToCart($product);
        }
    }

    private function loadSkuModal($product)
    {
        $this->selectedProductData = [
            'id'   => $product->id,
            'name' => $product->name,
            'code' => $product->code,
        ];
        $this->selectedSkuId = null;

        $skus = ProductSku::with(['size', 'color'])
            ->where('product_id', $product->id)
            ->where('branch_id', $this->branch_id)
            ->get();

        $this->productSkus = $skus->map(fn ($s) => [
            'id'         => $s->id,
            'sku'        => $s->sku,
            'stock'      => $s->stock,
            'size_name'  => $s->size  ? $s->size->name  : null,
            'color_name' => $s->color ? $s->color->name : null,
        ])->toArray();

        $this->resetInputFields();
        $this->dispatch('show-sku-remito-modal');
    }

    public function selectSku($sku_id)
    {
        $this->selectedSkuId = $sku_id;
    }

    public function addSkuToCart()
    {
        if (!$this->selectedSkuId) {
            $this->dispatch('alert', 'SELECCIONA UNA TALLA / COLOR', 'error');
            return;
        }

        if (!$this->selectedProductData) {
            $this->dispatch('alert', 'ERROR: PRODUCTO NO SELECCIONADO', 'error');
            return;
        }

        $sku = ProductSku::with(['size', 'color'])->find($this->selectedSkuId);
        if (!$sku) {
            $this->dispatch('alert', 'VARIANTE NO ENCONTRADA', 'error');
            return;
        }

        if ($this->tipo === 'EGRESO' && $sku->stock <= 0) {
            $this->dispatch('alert', 'SIN STOCK PARA ESTA VARIANTE', 'error');
            return;
        }

        $cartSessionKey = 'remitos_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);
        $cartKey = $this->selectedProductData['id'] . '_sku_' . $sku->id;

        if (isset($cart[$cartKey])) {
            $newQty = $cart[$cartKey]['quantity'] + 1;
            if ($this->tipo === 'EGRESO' && $newQty > $sku->stock) {
                $this->dispatch('alert', 'STOCK INSUFICIENTE PARA ESTA VARIANTE', 'error');
                return;
            }
            $cart[$cartKey]['quantity'] = $newQty;
            $this->dispatch('update-remito-qty-input', ['productId' => $cartKey, 'qty' => $newQty]);
        } else {
            $parts = array_filter([
                $sku->size  ? 'Talla: ' . $sku->size->name  : null,
                $sku->color ? 'Color: ' . $sku->color->name : null,
            ]);
            $skuLabel = implode(' / ', $parts) ?: $sku->sku;

            $cart[$cartKey] = [
                'id'       => $this->selectedProductData['id'],
                'cartKey'  => $cartKey,
                'name'     => $this->selectedProductData['name'],
                'code'     => $this->selectedProductData['code'],
                'sku_id'   => $sku->id,
                'sku_name' => $skuLabel,
                'quantity' => 1,
            ];
        }

        session()->put($cartSessionKey, $cart);
        $this->selectedProductData = null;
        $this->selectedSkuId = null;
        $this->productSkus = [];
        $this->dispatch('close-sku-remito-modal');
        $this->dispatch('alert', 'PRODUCTO AGREGADO AL CARRITO', 'success');
        $this->dispatch('focusRemitoSearchInput');
    }

    private function processAddToCart($product)
    {
        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
        $inventory = Inventorie::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->first();

        if ($this->tipo === 'EGRESO') {
            $availableStock = $inventory ? $inventory->stock_nolot : 0;
            if ($availableStock <= 0) {
                $this->dispatch('alert', 'SIN STOCK DISPONIBLE PARA ESTE PRODUCTO', 'error');
                return;
            }
        }

        $cartSessionKey = 'remitos_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);
        $cartKey = (string) $product->id;

        if (isset($cart[$cartKey])) {
            $newQty = $cart[$cartKey]['quantity'] + 1;
            if ($this->tipo === 'EGRESO' && $inventory) {
                if ($newQty > $inventory->stock_nolot) {
                    $this->dispatch('alert', 'STOCK INSUFICIENTE', 'error');
                    return;
                }
            }
            $cart[$cartKey]['quantity'] = $newQty;
            $this->dispatch('update-remito-qty-input', ['productId' => $cartKey, 'qty' => $newQty]);
        } else {
            $cart[$cartKey] = [
                'id'       => $product->id,
                'cartKey'  => $cartKey,
                'name'     => $product->name,
                'code'     => $product->code,
                'sku_id'   => null,
                'sku_name' => null,
                'quantity' => 1,
            ];
        }

        session()->put($cartSessionKey, $cart);
        $this->dispatch('alert', 'PRODUCTO AGREGADO AL CARRITO', 'success');
        $this->resetInputFields();
        $this->dispatch('focusRemitoSearchInput');
    }

    public function updateQty($cartKey, $quantity)
    {
        $cartSessionKey = 'remitos_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        if (!is_numeric($quantity) || $quantity <= 0) {
            $this->dispatch('alert', 'EL VALOR DE LA CANTIDAD ES INCORRECTO', 'error');
            if (isset($cart[$cartKey])) {
                $this->dispatch('update-remito-qty-input', ['productId' => $cartKey, 'qty' => $cart[$cartKey]['quantity']]);
            }
            return;
        }

        if (!isset($cart[$cartKey])) return;

        $item = $cart[$cartKey];
        $previousQty = $item['quantity'];

        if ($this->tipo === 'EGRESO') {
            if ($item['sku_id']) {
                $sku = ProductSku::find($item['sku_id']);
                if ($sku && $quantity > $sku->stock) {
                    $this->dispatch('alert', 'STOCK INSUFICIENTE PARA ESTA VARIANTE (disponible: ' . $sku->stock . ')', 'error');
                    $this->dispatch('update-remito-qty-input', ['productId' => $cartKey, 'qty' => $previousQty]);
                    return;
                }
            } else {
                $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
                $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
                $product = Product::find($item['id']);
                $inventory = Inventorie::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->first();
                if ($inventory) {
                    if ($quantity > $inventory->stock_nolot) {
                        $this->dispatch('alert', 'NO HAY SUFICIENTE STOCK DISPONIBLE (disponible: ' . $inventory->stock_nolot . ')', 'error');
                        $this->dispatch('update-remito-qty-input', ['productId' => $cartKey, 'qty' => $previousQty]);
                        return;
                    }
                }
            }
        }

        $cart[$cartKey]['quantity'] = $quantity;
        session()->put($cartSessionKey, $cart);
        $this->dispatch('update-remito-qty-input', ['productId' => $cartKey, 'qty' => $quantity]);
        $this->dispatch('alert', 'CANTIDAD ACTUALIZADA', 'success');
    }

    public function removeItem($cartKey)
    {
        $cartSessionKey = 'remitos_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);
        if (isset($cart[$cartKey])) unset($cart[$cartKey]);
        session()->put($cartSessionKey, $cart);
        $this->dispatch('alert', 'PRODUCTO ELIMINADO DEL CARRITO', 'success');
        $this->dispatch('focusRemitoSearchInput');
    }

    public function confirmRemito()
    {
        $this->refreshData();

        if (empty($this->cart)) {
            $this->dispatch('alert', 'EL CARRITO ESTÁ VACÍO', 'error');
            return;
        }

        $this->validate(
            [
                'remito_date'      => 'required|date',
                'n_orden'          => 'required|string|min:1|max:100',
                'contrato'         => 'required|string|min:2|max:200',
                'senores'          => 'required|string|min:2|max:200',
                'atencion'         => 'required|string|min:2|max:200',
                'campo'            => 'required|string|min:2|max:200',
                'placa'            => 'required|string|min:1|max:50',
                'despachado_por'   => 'required|string|min:2|max:200',
                'transportado_por' => 'required|string|min:2|max:200',
                'observations'     => 'nullable|string|max:500',
            ],
            [
                'remito_date.required'      => 'La fecha es obligatoria.',
                'remito_date.date'          => 'La fecha debe ser válida.',
                'n_orden.required'          => 'El N° de Orden es obligatorio.',
                'contrato.required'         => 'El Contrato es obligatorio.',
                'contrato.min'              => 'El contrato debe tener al menos 2 caracteres.',
                'senores.required'          => 'El campo Señores es obligatorio.',
                'senores.min'              => 'Señores debe tener al menos 2 caracteres.',
                'atencion.required'         => 'El campo Atención es obligatorio.',
                'atencion.min'             => 'Atención debe tener al menos 2 caracteres.',
                'campo.required'            => 'El campo Campo es obligatorio.',
                'campo.min'               => 'Campo debe tener al menos 2 caracteres.',
                'placa.required'            => 'La Placa es obligatoria.',
                'despachado_por.required'   => 'Despachado por es obligatorio.',
                'despachado_por.min'       => 'Despachado por debe tener al menos 2 caracteres.',
                'transportado_por.required' => 'Transportado por es obligatorio.',
                'transportado_por.min'     => 'Transportado por debe tener al menos 2 caracteres.',
            ]
        );

        DB::beginTransaction();
        try {
            $branch = Branche::find($this->branch_id);
            $branchCode = $branch && $branch->code ? $branch->code : 'SUC' . $this->branch_id;
            $currentYear = now()->format('y');
            $prefix = $this->tipo === 'INGRESO' ? 'ING' : 'EGR';

            $lastRemito = Remito::where('branch_id', $this->branch_id)
                ->where('tipo', $this->tipo)
                ->where('remito_number', 'like', "{$prefix}-{$branchCode}-%-{$currentYear}")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastRemito) {
                preg_match("/^{$prefix}-{$branchCode}-(\d+)-{$currentYear}$/", $lastRemito->remito_number, $matches);
                $nextNumber = isset($matches[1]) ? ((int) $matches[1] + 1) : 1;
            }

            $remitoNumber = "{$prefix}-{$branchCode}-{$nextNumber}-{$currentYear}";
            while (Remito::where('remito_number', $remitoNumber)->exists()) {
                $nextNumber++;
                $remitoNumber = "{$prefix}-{$branchCode}-{$nextNumber}-{$currentYear}";
            }

            $remitoDateTime = $this->remito_date . ' ' . now()->format('H:i:s');

            $remito = Remito::create([
                'remito_number'   => $remitoNumber,
                'tipo'            => $this->tipo,
                'contrato'        => $this->contrato ?: null,
                'senores'         => $this->senores ?: null,
                'atencion'        => $this->atencion ?: null,
                'campo'           => $this->campo ?: null,
                'n_orden'         => $this->n_orden ?: null,
                'observations'    => $this->observations ?: null,
                'despachado_por'  => $this->despachado_por ?: null,
                'transportado_por'=> $this->transportado_por ?: null,
                'placa'           => $this->placa ?: null,
                'status'          => 1,
                'branch_id'       => $this->branch_id,
                'user_id'         => auth()->id(),
                'created_at'      => $remitoDateTime,
                'updated_at'      => now(),
            ]);

            $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

            foreach ($this->cart as $item) {
                RemitoDetail::create([
                    'remito_id'    => $remito->id,
                    'product_id'   => $item['id'],
                    'warehouse_id' => $warehouseId,
                    'sku_id'       => $item['sku_id'] ?? null,
                    'quantity'     => $item['quantity'],
                    'observations' => null,
                    'created_at'   => $remitoDateTime,
                    'updated_at'   => now(),
                ]);

                $isIngreso = $this->tipo === 'INGRESO';

                if ($item['sku_id']) {
                    $sku = ProductSku::where('id', $item['sku_id'])->lockForUpdate()->first();
                    if ($sku) {
                        $sku->stock = $isIngreso
                            ? $sku->stock + $item['quantity']
                            : $sku->stock - $item['quantity'];
                        $sku->save();
                    }
                    $inventory = Inventorie::where('product_id', $item['id'])
                        ->where('warehouse_id', $warehouseId)
                        ->lockForUpdate()->first();
                    if ($inventory) {
                        $inventory->stock_nolot = $isIngreso
                            ? $inventory->stock_nolot + $item['quantity']
                            : $inventory->stock_nolot - $item['quantity'];
                        $inventory->save();
                    } else if ($isIngreso) {
                        Inventorie::create([
                            'product_id'   => $item['id'],
                            'warehouse_id' => $warehouseId,
                            'stock_nolot'  => $item['quantity'],
                            'stock_lot'    => 0,
                            'stock'        => 0,
                            'status'       => 1,
                        ]);
                    }
                } else {
                    $inventory = Inventorie::where('product_id', $item['id'])
                        ->where('warehouse_id', $warehouseId)
                        ->lockForUpdate()->first();

                    if ($inventory) {
                        $inventory->stock_nolot = $isIngreso
                            ? $inventory->stock_nolot + $item['quantity']
                            : $inventory->stock_nolot - $item['quantity'];
                        $inventory->save();
                    } else if ($isIngreso) {
                        Inventorie::create([
                            'product_id'   => $item['id'],
                            'warehouse_id' => $warehouseId,
                            'stock_lot'    => 0,
                            'stock_nolot'  => $item['quantity'],
                            'stock'        => 0,
                            'status'       => 1,
                        ]);
                    }
                }

                $lastKardex = Kardex::where('product_id', $item['id'])
                    ->where('warehouse_id', $warehouseId)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $previousBalance = $lastKardex ? $lastKardex->balance : 0;

                Kardex::create([
                    'type'             => $isIngreso ? 'ENTRADA' : 'SALIDA',
                    'description'      => ($isIngreso ? 'REMITO INGRESO - ' : 'REMITO EGRESO - ') . $remitoNumber,
                    'quantity_in'      => $isIngreso ? $item['quantity'] : 0,
                    'quantity_out'     => $isIngreso ? 0 : $item['quantity'],
                    'balance'          => $isIngreso
                        ? $previousBalance + $item['quantity']
                        : $previousBalance - $item['quantity'],
                    'product_id'       => $item['id'],
                    'user_id'          => auth()->id(),
                    'warehouse_id'     => $warehouseId,
                    'transaction_type' => 'remitos',
                    'transaction_id'   => $remito->id,
                    'status'           => 1,
                ]);
            }

            DB::commit();

            $totalItems = collect($this->cart)->sum('quantity');
            $this->logActivity(
                'REMITOS', 'CREAR',
                "Registró remito {$this->tipo}: {$remitoNumber} ({$totalItems} ítems)",
                $remito->id,
                null,
                ['remito_number' => $remitoNumber, 'tipo' => $this->tipo, 'branch_id' => $this->branch_id, 'items' => $totalItems]
            );

            $this->clearRemito();
            $this->remito_date = now()->format('Y-m-d');
            $this->dispatch('processPrintBehaviorRemito', url: '#', behavior: 'none', message: 'REMITO REGISTRADO: ' . $remitoNumber);
            $this->dispatch('focusRemitoSearchInput');

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al registrar el remito: ' . $e->getMessage(), 'error');
        }
    }

    public function clearRemito()
    {
        $cartSessionKey = 'remitos_cart_' . $this->branch_id;
        session()->forget($cartSessionKey);
        $this->cart = [];
        $this->total_items = 0;
        $this->contrato = '';
        $this->senores = '';
        $this->atencion = '';
        $this->campo = '';
        $this->n_orden = '';
        $this->observations = '';
        $this->despachado_por = '';
        $this->transportado_por = '';
        $this->placa = '';
    }

    public function resetInputFields()
    {
        $this->search = '';
        $this->products = [];
    }
}
