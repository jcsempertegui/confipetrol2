<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Delivery;
use App\Models\DeliveryDetail;
use App\Models\Inventorie;
use App\Models\Kardex;
use App\Models\Branche;
use App\Models\Worker;
use App\Models\Warehouse;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Exception;

include_once(base_path('public/assets/plugins/literal.php'));

class DeliveriesController extends Component
{
    use WithPagination, AuditLog;

    public $search = '';
    public $products = [];

    public $cart = [];
    public $total_items = 0;

    public $workers_id;
    public $worker_document, $worker_name;
    public $workerSearchTerm = '';
    public $workerResults = [];
    public $showWorkerDropdown = false;

    public $branch_id;

    // SKU modal state (stored as arrays for Livewire compatibility)
    public $selectedProductData = null;
    public $productSkus = [];
    public $selectedSkuId = null;

    public $delivery_date;
    public $observations = '';

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
        $this->delivery_date = now()->format('Y-m-d');
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

        $cartSessionKey = 'deliveries_cart_' . $this->branch_id;
        $this->cart = session()->get($cartSessionKey, []);
        $this->total_items = count($this->cart);
    }

    public function render()
    {
        $this->refreshData();
        return view('livewire.deliveries.deliveries', [
            'deliveries_cart' => $this->cart,
        ])->extends('layouts.theme.app');
    }

    // ── Worker search (identical pattern to SalesController) ─────────────────

    public function updatedWorkerSearchTerm()
    {
        if (strlen($this->workerSearchTerm) >= 1) {
            $this->workerResults = Worker::select('id', 'name', 'last_name', 'document', 'cargo')
                ->where('status', 1)
                ->where(function ($q) {
                    $q->where('document', 'LIKE', '%' . $this->workerSearchTerm . '%')
                        ->orWhere('name', 'LIKE', '%' . $this->workerSearchTerm . '%')
                        ->orWhere('last_name', 'LIKE', '%' . $this->workerSearchTerm . '%');
                })
                ->limit(10)
                ->get();
            $this->showWorkerDropdown = true;
        } else {
            $this->workerResults = [];
            $this->showWorkerDropdown = false;
        }
    }

    public function selectWorker($id)
    {
        $worker = Worker::find($id);
        if ($worker) {
            $this->workers_id = $worker->id;
            $this->worker_document = $worker->document;
            $this->worker_name = $worker->name . ' ' . $worker->last_name;
            $this->workerSearchTerm = $worker->name . ' ' . $worker->last_name . ' - ' . $worker->document;
            $this->workerResults = Worker::where('id', $worker->id)->get();
            $this->showWorkerDropdown = false;
            $this->dispatch('alert', 'TRABAJADOR SELECCIONADO', 'success');
        }
    }

    public function clearWorkerSearch()
    {
        if ($this->workers_id) {
            $this->workerSearchTerm = $this->worker_name . ' - ' . $this->worker_document;
        } else {
            $this->clearWorkerSelection();
        }
        $this->showWorkerDropdown = false;
    }

    public function clearWorkerSelection()
    {
        $this->workers_id = null;
        $this->worker_document = null;
        $this->worker_name = null;
        $this->workerSearchTerm = '';
        $this->workerResults = [];
        $this->showWorkerDropdown = false;
    }

    // ── Product search – only type = 3 (EPPs) ────────────────────────────────

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
            ->where('products.type', 3)
            ->where(function ($q) use ($searchTerm) {
                $q->where('products.code', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('products.name', 'LIKE', '%' . $searchTerm . '%');
            })
            ->orderBy('products.name', 'asc')
            ->limit(7)
            ->get();
    }

    // ── Cart operations ───────────────────────────────────────────────────────

    public function AddOrUpdate($product_id)
    {
        if ($product_id === 'null' || $product_id === null) {
            $this->dispatch('alert', 'NO HAY PRODUCTOS DISPONIBLES O SELECCIONADOS', 'error');
            return;
        }

        $product = Product::where('id', $product_id)
            ->where('status', 1)
            ->where('type', 3)
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
        $this->dispatch('show-sku-delivery-modal');
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

        if ($sku->stock <= 0) {
            $this->dispatch('alert', 'SIN STOCK PARA ESTA VARIANTE', 'error');
            return;
        }

        $cartSessionKey = 'deliveries_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);
        $cartKey = $this->selectedProductData['id'] . '_sku_' . $sku->id;

        if (isset($cart[$cartKey])) {
            $newQty = $cart[$cartKey]['quantity'] + 1;
            if ($newQty > $sku->stock) {
                $this->dispatch('alert', 'STOCK INSUFICIENTE PARA ESTA VARIANTE', 'error');
                return;
            }
            $cart[$cartKey]['quantity'] = $newQty;
            $this->dispatch('update-delivery-qty-input', ['productId' => $cartKey, 'qty' => $newQty]);
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
        $this->dispatch('close-sku-delivery-modal');
        $this->dispatch('alert', 'EPP AGREGADO AL CARRITO', 'success');
        $this->dispatch('focusDeliverySearchInput');
    }

    private function processAddToCart($product)
    {
        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
        $inventory = Inventorie::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->first();

        $availableStock = $inventory ? $inventory->stock_nolot : 0;

        if ($availableStock <= 0) {
            $this->dispatch('alert', 'SIN STOCK DISPONIBLE PARA ESTE PRODUCTO', 'error');
            return;
        }

        $cartSessionKey = 'deliveries_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);
        $cartKey = (string) $product->id;

        if (isset($cart[$cartKey])) {
            $newQty = $cart[$cartKey]['quantity'] + 1;
            if ($newQty > $availableStock) {
                $this->dispatch('alert', 'STOCK INSUFICIENTE', 'error');
                return;
            }
            $cart[$cartKey]['quantity'] = $newQty;
            $this->dispatch('update-delivery-qty-input', ['productId' => $cartKey, 'qty' => $newQty]);
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
        $this->dispatch('alert', 'EPP AGREGADO AL CARRITO', 'success');
        $this->resetInputFields();
        $this->dispatch('focusDeliverySearchInput');
    }

    public function updateQty($cartKey, $quantity)
    {
        $cartSessionKey = 'deliveries_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        if (!is_numeric($quantity) || $quantity <= 0) {
            $this->dispatch('alert', 'EL VALOR DE LA CANTIDAD ES INCORRECTO', 'error');
            if (isset($cart[$cartKey])) {
                $this->dispatch('update-delivery-qty-input', ['productId' => $cartKey, 'qty' => $cart[$cartKey]['quantity']]);
            }
            return;
        }

        if (!isset($cart[$cartKey])) return;

        $item = $cart[$cartKey];
        $previousQty = $item['quantity'];

        if ($item['sku_id']) {
            $sku = ProductSku::find($item['sku_id']);
            if ($sku && $quantity > $sku->stock) {
                $this->dispatch('alert', 'STOCK INSUFICIENTE PARA ESTA VARIANTE (disponible: ' . $sku->stock . ')', 'error');
                $this->dispatch('update-delivery-qty-input', ['productId' => $cartKey, 'qty' => $previousQty]);
                return;
            }
        } else {
            $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
            $inventory = Inventorie::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->first();
            if ($inventory) {
                if ($quantity > $inventory->stock_nolot) {
                    $this->dispatch('alert', 'NO HAY SUFICIENTE STOCK DISPONIBLE (disponible: ' . $inventory->stock_nolot . ')', 'error');
                    $this->dispatch('update-delivery-qty-input', ['productId' => $cartKey, 'qty' => $previousQty]);
                    return;
                }
            }
        }

        $cart[$cartKey]['quantity'] = $quantity;
        session()->put($cartSessionKey, $cart);
        $this->dispatch('update-delivery-qty-input', ['productId' => $cartKey, 'qty' => $quantity]);
        $this->dispatch('alert', 'CANTIDAD ACTUALIZADA', 'success');
    }

    public function removeItem($cartKey)
    {
        $cartSessionKey = 'deliveries_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);
        if (isset($cart[$cartKey])) unset($cart[$cartKey]);
        session()->put($cartSessionKey, $cart);
        $this->dispatch('alert', 'PRODUCTO ELIMINADO DEL CARRITO', 'success');
        $this->dispatch('focusDeliverySearchInput');
    }

    // ── Save delivery ─────────────────────────────────────────────────────────

    public function confirmDelivery()
    {
        $this->refreshData();

        if (empty($this->cart)) {
            $this->dispatch('alert', 'EL CARRITO ESTÁ VACÍO', 'error');
            return;
        }

        if (empty($this->workers_id)) {
            $this->dispatch('alert', 'SELECCIONA UN TRABAJADOR', 'error');
            return;
        }

        $this->validate(
            ['delivery_date' => 'required|date'],
            ['delivery_date.required' => 'La fecha de entrega es obligatoria.', 'delivery_date.date' => 'La fecha debe ser válida.']
        );

        DB::beginTransaction();
        try {
            $branch = Branche::find($this->branch_id);
            $branchCode = $branch && $branch->code ? $branch->code : 'SUC' . $this->branch_id;
            $currentYear = now()->format('y');

            $lastDelivery = Delivery::where('branch_id', $this->branch_id)
                ->where('delivery_number', 'like', "ENT-{$branchCode}-%-{$currentYear}")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastDelivery) {
                preg_match("/^ENT-{$branchCode}-(\d+)-{$currentYear}$/", $lastDelivery->delivery_number, $matches);
                $nextNumber = isset($matches[1]) ? ((int) $matches[1] + 1) : 1;
            }

            $deliveryNumber = "ENT-{$branchCode}-{$nextNumber}-{$currentYear}";
            while (Delivery::where('delivery_number', $deliveryNumber)->exists()) {
                $nextNumber++;
                $deliveryNumber = "ENT-{$branchCode}-{$nextNumber}-{$currentYear}";
            }

            $deliveryDateTime = $this->delivery_date . ' ' . now()->format('H:i:s');

            $delivery = Delivery::create([
                'delivery_number' => $deliveryNumber,
                'observations'    => $this->observations ?: null,
                'status'          => 1,
                'worker_id'       => $this->workers_id,
                'branch_id'       => $this->branch_id,
                'user_id'         => auth()->id(),
                'created_at'      => $deliveryDateTime,
                'updated_at'      => now(),
            ]);

            $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

            foreach ($this->cart as $item) {
                DeliveryDetail::create([
                    'delivery_id'  => $delivery->id,
                    'product_id'   => $item['id'],
                    'warehouse_id' => $warehouseId,
                    'sku_id'       => $item['sku_id'] ?? null,
                    'quantity'     => $item['quantity'],
                    'observations' => null,
                    'created_at'   => $deliveryDateTime,
                    'updated_at'   => now(),
                ]);

                if ($item['sku_id']) {
                    $sku = ProductSku::where('id', $item['sku_id'])->lockForUpdate()->first();
                    if ($sku) {
                        $sku->stock -= $item['quantity'];
                        $sku->save();
                    }
                    $inventory = Inventorie::where('product_id', $item['id'])
                        ->where('warehouse_id', $warehouseId)
                        ->lockForUpdate()->first();
                    if ($inventory) {
                        $inventory->stock_nolot -= $item['quantity'];
                        $inventory->save();
                    }
                } else {
                    $product = Product::find($item['id']);
                    $inventory = Inventorie::where('product_id', $item['id'])
                        ->where('warehouse_id', $warehouseId)
                        ->lockForUpdate()->first();
                    if ($inventory) {
                        $inventory->stock_nolot -= $item['quantity'];
                        $inventory->save();
                    }
                }

                $lastKardex = Kardex::where('product_id', $item['id'])
                    ->where('warehouse_id', $warehouseId)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $previousBalance = $lastKardex ? $lastKardex->balance : 0;

                Kardex::create([
                    'type'             => 'SALIDA',
                    'description'      => 'ENTREGA EPP - ' . $deliveryNumber,
                    'quantity_in'      => 0,
                    'quantity_out'     => $item['quantity'],
                    'balance'          => $previousBalance - $item['quantity'],
                    'product_id'       => $item['id'],
                    'user_id'          => auth()->id(),
                    'warehouse_id'     => $warehouseId,
                    'transaction_type' => 'deliveries',
                    'transaction_id'   => $delivery->id,
                    'status'           => 1,
                ]);
            }

            DB::commit();

            $totalItems = collect($this->cart)->sum('quantity');
            $this->logActivity(
                'ENTREGAS', 'CREAR',
                "Registró entrega EPP: {$deliveryNumber} ({$totalItems} ítems)",
                $delivery->id,
                null,
                ['delivery_number' => $deliveryNumber, 'branch_id' => $this->branch_id, 'worker_id' => $this->workers_id, 'items' => $totalItems]
            );

            $this->clearDeliveries();
            $this->delivery_date = now()->format('Y-m-d');
            $this->observations = '';
            $this->dispatch('processPrintBehavior', url: '#', behavior: 'none', message: 'ENTREGA REGISTRADA: ' . $deliveryNumber);
            $this->dispatch('focusDeliverySearchInput');

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al registrar la entrega: ' . $e->getMessage(), 'error');
        }
    }

    public function clearDeliveries()
    {
        $cartSessionKey = 'deliveries_cart_' . $this->branch_id;
        session()->forget($cartSessionKey);
        $this->clearWorkerSelection();
        $this->cart = [];
        $this->total_items = 0;
    }

    public function resetInputFields()
    {
        $this->search = '';
        $this->products = [];
    }
}
