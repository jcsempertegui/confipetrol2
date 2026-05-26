<?php

namespace App\Livewire;

use App\Traits\PaymentLogic;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Inventorie;
use App\Models\Movement;
use App\Models\Branche;
use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Kardex;
use App\Models\Worker;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Exception;

include_once(base_path('public/assets/plugins/literal.php'));

class SalesController extends Component
{
    use WithPagination;
    use PaymentLogic;

    public $searchType = 'name';
    public $products = [], $search, $searchTerm;
    public $product_id, $sale_price, $quantity;
    public $cart, $total, $items, $subtotal, $total_cart = 0, $discount = 0;

    public $workers_id;
    public $worker_document, $worker_name;
    public $workerSearchTerm = '';
    public $workerResults = [];
    public $showWorkerDropdown = false;

    public $boxExists = true;
    public $branch_id;

    public $selectedProduct = null;
    public $enable_staff_per_detail = 0;
    public $selectedCartKeyForEmployee = null;
    public $listEmployees = [];
    public $searchEmployee = '';

    public $sale_id;
    public $is_editing = false;
    public $sale_date;
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

    public function refreshData($branchId = null)
    {
        if ($branchId !== null) {
            $this->branch_id = $branchId;
            session()->put('branch_user_id', $branchId);
        } else {
            $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        }

        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;

        $this->cart = session()->get($cartSessionKey, []);
        $this->total = session()->get($totalSessionKey, []);

        $this->total_cart = $this->total['total'] ?? 0;
        $this->discount = $this->total['discount'] ?? 0;
        $this->subtotal = $this->total['subtotal'] ?? 0;
        $this->items = count($this->cart);

        $branch = Branche::find($this->branch_id);
        $this->enable_staff_per_detail = $branch ? $branch->enable_staff_per_detail : 0;
    }

    public function render()
    {
        $this->refreshData();
        $this->boxExists = true;

        return view('livewire.sales.sales', [
            'sales_cart' => $this->cart,
        ])->extends('layouts.theme.app');
    }

    public function mount($sale_id = null)
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);

        if (!$sale_id && session()->has('is_editing_' . $this->branch_id)) {
            session()->forget('sales_cart_' . $this->branch_id);
            session()->forget('sales_total_' . $this->branch_id);
            session()->forget('is_editing_' . $this->branch_id);
        }

        $this->boxExists = true;
        $this->sale_date = now()->format('Y-m-d');
        $this->refreshData();

        $branch = Branche::find($this->branch_id);
        $this->enable_staff_per_detail = $branch ? $branch->enable_staff_per_detail : 0;

        if ($sale_id) {
            $this->sale_id = $sale_id;
            $this->is_editing = true;
            session()->put('is_editing_' . $this->branch_id, true);
            $this->loadSaleForEdit($sale_id);
        }
    }

    private function loadSaleForEdit($sale_id)
    {
        $sale = Sale::with(['details.product', 'worker'])->findOrFail($sale_id);
        $this->sale_date = $sale->created_at ? $sale->created_at->format('Y-m-d') : now()->format('Y-m-d');
        $this->observations = $sale->observations ?? '';

        if ($sale->worker) {
            $this->workers_id = $sale->worker->id;
            $this->worker_document = $sale->worker->document;
            $this->worker_name = $sale->worker->name . ' ' . $sale->worker->last_name;
            $this->workerSearchTerm = $sale->worker->name . ' ' . $sale->worker->last_name . ' - ' . $sale->worker->document;
        }

        $payment = Payment::where('transaction_type', 'sales')->where('transaction_id', $sale->id)->first();
        $this->selectedPayment = $payment ? $payment->description : 'EFECTIVO';

        $cart = [];
        foreach ($sale->details as $detail) {
            $product = $detail->product;
            if (!$product) continue;

            $unit_id = $detail->unit_id;
            $unit_name = null;
            $unit_factor = 1;
            if ($unit_id) {
                $uData = Unit::find($unit_id);
                if ($uData) {
                    $unit_name = $uData->name;
                    $unit_factor = $uData->factor > 0 ? $uData->factor : 1;
                }
            }

            $employee_id = $detail->employee_id;
            $employee_name = null;
            if ($employee_id) {
                $emp = User::find($employee_id);
                if ($emp) $employee_name = trim($emp->name . ' ' . $emp->lastname);
            }

            $cartKey = (string) $product->id;
            if ($unit_id) $cartKey .= '_U' . $unit_id;

            $cart[$cartKey] = [
                'id' => $product->id,
                'cartKey' => $cartKey,
                'name' => $product->name,
                'code' => $product->code,
                'type' => $product->type,
                'purchase_price' => $detail->purchase_price,
                'sale_price' => $detail->sale_price,
                'original_sale_price' => $detail->sale_price,
                'prices' => [],
                'wholesale_min_quantity' => null,
                'is_wholesale' => false,
                'is_custom_price' => false,
                'selected_price_type' => 'normal',
                'quantity' => $detail->quantity,
                'subtotal' => $detail->subtotal,
                'unit_id' => $unit_id,
                'unit_name' => $unit_name,
                'unit_factor' => $unit_factor,
                'employee_id' => $employee_id,
                'employee_name' => $employee_name,
                'free_qty' => 0,
            ];
        }

        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;
        session()->put($cartSessionKey, $cart);

        $totalAmount = array_sum(array_column($cart, 'subtotal'));
        $discount = $sale->discount ?? 0;

        session()->put($totalSessionKey, [
            'subtotal' => $totalAmount,
            'total' => $totalAmount - $discount,
            'discount' => $discount,
        ]);
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

        $this->products = Product::with(['brands', 'categories', 'units'])
            ->select([
                'products.id',
                'products.code',
                'products.name',
                'products.lote',
                'products.type',
                'products.brand_id',
                'products.categorie_id',
                'products.unit_id',
                'products.image',
                'inventories.sale_price',
                'inventories.stock_lot',
                'inventories.stock_nolot',
            ])
            ->leftJoin('inventories', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'inventories.product_id')
                    ->where('inventories.warehouse_id', $warehouseId);
            })
            ->where('products.status', 1)
            ->whereIn('products.type', [0, 1])
            ->where(function ($q) use ($searchTerm) {
                $q->where('products.code', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('products.name', 'LIKE', '%' . $searchTerm . '%');
            })
            ->orderBy('products.name', 'asc')
            ->limit(7)
            ->get();
    }

    public function AddOrUpdate($product_id, $searchType = 'id')
    {
        if ($product_id === 'null' || $product_id === null) {
            $this->dispatch('alert', 'NO HAY PRODUCTOS DISPONIBLES O SELECCIONADOS', 'error');
            return;
        }

        $productCheck = Product::where('status', 1)
            ->whereIn('type', [0, 1])
            ->where(function ($query) use ($product_id, $searchType) {
                if ($searchType === 'id') {
                    $query->where('id', $product_id);
                } else {
                    $query->where('code', $product_id);
                }
            })->first();

        if (!$productCheck) {
            $this->dispatch('alert', 'NO HAY PRODUCTOS DISPONIBLES', 'error');
            return;
        }

        $this->processAddToCart($productCheck);
    }

    private function processAddToCart($productCheck)
    {
        $branch_id = $this->branch_id;
        $product_id = $productCheck->id;
        $cartKey = (string) $product_id;

        $defaultWarehouse = Warehouse::where('branch_id', $branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
        $inventory = Inventorie::where('product_id', $product_id)->where('warehouse_id', $warehouseId)->first();

        $purchasePrice = $inventory ? $inventory->purchase_price : 0;
        $salePrice = $inventory ? $inventory->sale_price : 0;

        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        if ($productCheck->type == 1) {
            if (isset($cart[$cartKey])) {
                $newQty = $cart[$cartKey]['quantity'] + 1;
                $cart[$cartKey]['quantity'] = $newQty;
                $cart[$cartKey]['subtotal'] = $newQty * $cart[$cartKey]['sale_price'];
                $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $newQty]);
            } else {
                $cart[$cartKey] = [
                    'id' => $productCheck->id,
                    'cartKey' => $cartKey,
                    'name' => $productCheck->name,
                    'code' => $productCheck->code,
                    'type' => 1,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice,
                    'original_sale_price' => $salePrice,
                    'prices' => [],
                    'wholesale_min_quantity' => null,
                    'is_wholesale' => false,
                    'is_custom_price' => false,
                    'selected_price_type' => 'normal',
                    'quantity' => 1,
                    'subtotal' => $salePrice,
                    'unit_id' => $productCheck->unit_id ?? null,
                    'unit_name' => null,
                    'unit_factor' => 1,
                    'employee_id' => null,
                    'employee_name' => null,
                    'free_qty' => 0,
                ];
            }
            $totalAmount = collect($cart)->sum('subtotal');
            session()->put($cartSessionKey, $cart);
            session()->put($totalSessionKey, ['subtotal' => $totalAmount, 'total' => $totalAmount, 'discount' => 0]);
            $this->dispatch('alert', 'SERVICIO AGREGADO AL CARRITO', 'success');
            $this->resetInputFields();
            $this->dispatch('focusSearchInput');
            return;
        }

        $productDetails = Product::select(
            'products.id',
            'products.name',
            'products.type',
            'products.lote',
            'products.code',
            'products.unit_id',
            'inventories.stock_lot',
            'inventories.stock_nolot'
        )
            ->join('inventories', function ($join) use ($product_id, $warehouseId) {
                $join->on('products.id', '=', 'inventories.product_id')
                    ->where('products.status', 1)
                    ->where('inventories.warehouse_id', $warehouseId);
            })
            ->where('products.status', 1)
            ->where('products.id', $product_id)
            ->first();

        if (!$productDetails) {
            $this->dispatch('alert', 'NO HAY PRODUCTOS DISPONIBLES', 'error');
            return;
        }

        $currentQuantityInCart = isset($cart[$cartKey]) ? $cart[$cartKey]['quantity'] : 0;
        $newQuantity = $currentQuantityInCart + 1;
        $availableStock = $productDetails->lote == 1 ? $productDetails->stock_lot : $productDetails->stock_nolot;

        if ($newQuantity > $availableStock) {
            $this->dispatch('alert', 'STOCK INSUFICIENTE PARA ESTE PRODUCTO', 'error');
            return;
        }

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = $newQuantity;
            $cart[$cartKey]['subtotal'] = $newQuantity * $cart[$cartKey]['sale_price'];
            $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $newQuantity]);
        } else {
            $cart[$cartKey] = [
                'id' => $productDetails->id,
                'cartKey' => $cartKey,
                'name' => $productDetails->name,
                'code' => $productDetails->code,
                'type' => $productDetails->type,
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'original_sale_price' => $salePrice,
                'prices' => [],
                'wholesale_min_quantity' => null,
                'is_wholesale' => false,
                'is_custom_price' => false,
                'selected_price_type' => 'normal',
                'quantity' => 1,
                'subtotal' => $salePrice,
                'unit_id' => $productDetails->unit_id ?? null,
                'unit_name' => null,
                'unit_factor' => 1,
                'employee_id' => null,
                'employee_name' => null,
                'free_qty' => 0,
            ];
        }

        $totalAmount = collect($cart)->sum('subtotal');
        session()->put($cartSessionKey, $cart);
        session()->put($totalSessionKey, ['subtotal' => $totalAmount, 'total' => $totalAmount, 'discount' => 0]);
        $this->dispatch('alert', 'PRODUCTO AGREGADO AL CARRITO', 'success');
        $this->resetInputFields();
        $this->dispatch('focusSearchInput');
    }

    public function codeSearch()
    {
        if (strlen($this->search) > 0) {
            $this->AddOrUpdate($this->search, 'code');
            $this->search = '';
            $this->products = [];
        }
    }

    public function updateQty($cartKey, $quantity)
    {
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        if (!is_numeric($quantity) || $quantity <= 0) {
            $this->dispatch('alert', 'EL VALOR DE LA CANTIDAD ES INCORRECTO', 'error');
            if (isset($cart[$cartKey])) {
                $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $cart[$cartKey]['quantity']]);
            }
            return;
        }

        if (!isset($cart[$cartKey])) return;

        $product_id = $cart[$cartKey]['id'];
        $product = Product::find($product_id);
        $previousQuantity = $cart[$cartKey]['quantity'] ?? 1;

        if ($product && $product->type == 0) {
            $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
            $inventory = Inventorie::where('product_id', $product_id)->where('warehouse_id', $warehouseId)->first();

            if (!$inventory) {
                $this->dispatch('alert', 'Inventario no encontrado', 'error');
                $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $previousQuantity]);
                return;
            }

            $availableStock = $product->lote == 1 ? $inventory->stock_lot : $inventory->stock_nolot;
            if ($quantity > $availableStock) {
                $this->dispatch('alert', 'NO HAY SUFICIENTE STOCK DISPONIBLE', 'error');
                $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $previousQuantity]);
                return;
            }
        }

        $totalSessionKey = 'sales_total_' . $this->branch_id;
        $cart[$cartKey]['quantity'] = $quantity;
        $cart[$cartKey]['subtotal'] = $quantity * $cart[$cartKey]['sale_price'];

        session()->put($cartSessionKey, $cart);
        $subtotalGeneral = collect($cart)->sum('subtotal');
        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = $totalData['discount'] ?? 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;
        session()->put($totalSessionKey, ['subtotal' => $subtotalGeneral, 'total' => $nuevoTotal, 'discount' => $descuentoPorcentaje]);

        $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $quantity]);
        $this->dispatch('alert', 'CANTIDAD ACTUALIZADA', 'success');
    }

    public function setCustomPrice($cartKey, $sale_price)
    {
        if (empty($sale_price) || $sale_price <= 0) {
            $this->dispatch('alert', 'EL PRECIO ES INCORRECTO', 'error');
            return;
        }
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        if (isset($cart[$cartKey])) {
            $newPrice = floatval($sale_price);
            $cart[$cartKey]['sale_price'] = $newPrice;
            $cart[$cartKey]['subtotal'] = $cart[$cartKey]['quantity'] * $newPrice;
            $cart[$cartKey]['is_custom_price'] = true;
        }

        session()->put($cartSessionKey, $cart);
        $subtotalGeneral = collect($cart)->sum('subtotal');
        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = $totalData['discount'] ?? 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;
        session()->put($totalSessionKey, ['subtotal' => $subtotalGeneral, 'total' => $nuevoTotal, 'discount' => $descuentoPorcentaje]);
        $this->dispatch('alert', 'PRECIO VENTA ACTUALIZADO', 'success');
    }

    public function setPredefinedPrice($cartKey, $selected_price, $priceName = 'normal')
    {
        if (empty($selected_price) || $selected_price <= 0) {
            $this->dispatch('alert', 'EL PRECIO ES INCORRECTO', 'error');
            return;
        }
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['sale_price'] = floatval($selected_price);
            $cart[$cartKey]['subtotal'] = $cart[$cartKey]['quantity'] * floatval($selected_price);
            $cart[$cartKey]['selected_price_type'] = $priceName;
        }

        session()->put($cartSessionKey, $cart);
        $subtotalGeneral = collect($cart)->sum('subtotal');
        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = $totalData['discount'] ?? 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;
        session()->put($totalSessionKey, ['subtotal' => $subtotalGeneral, 'total' => $nuevoTotal, 'discount' => $descuentoPorcentaje]);
        $this->dispatch('alert', 'PRECIO ACTUALIZADO', 'success');
    }

    public function updateDiscount($discount)
    {
        $discount = is_null($discount) || $discount === '' ? 0 : floatval($discount);
        $totalSessionKey = 'sales_total_' . $this->branch_id;
        $total = session()->get($totalSessionKey, []);

        if (!empty($total)) {
            $subtotal = $total['subtotal'];
            if ($discount > $subtotal) {
                $this->dispatch('alert', 'EL DESCUENTO NO PUEDE SER MAYOR AL SUBTOTAL', 'error');
                $this->dispatch('update-discount-input', ['discount' => $total['discount'] ?? 0]);
                return;
            }
            $total['discount'] = $discount;
            $total['total'] = $subtotal - $discount;
            $this->discount = $discount;
            $this->total_cart = $total['total'];
        }
        session()->put($totalSessionKey, $total);
        $this->dispatch('alert', 'DESCUENTO ACTUALIZADO', 'success');
    }

    public function removeItem($cartKey)
    {
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        if (isset($cart[$cartKey])) unset($cart[$cartKey]);
        session()->put($cartSessionKey, $cart);

        $subtotalGeneral = collect($cart)->sum('subtotal');
        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = $totalData['discount'] ?? 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;
        session()->put($totalSessionKey, ['subtotal' => $subtotalGeneral, 'total' => $nuevoTotal, 'discount' => $descuentoPorcentaje]);

        $this->dispatch('alert', 'PRODUCTO ELIMINADO DEL CARRITO', 'success');
        $this->dispatch('focusSearchInput');
    }

    public function confirPayment()
    {
        if (count($this->cart) == 0) {
            $this->dispatch('alert', 'CARRITO VACIO!!!', 'error');
            return;
        }

        if (empty($this->workers_id)) {
            $this->dispatch('alert', 'SELECCIONA UN TRABAJADOR', 'error');
            return;
        }

        if ($this->enable_staff_per_detail == 1) {
            foreach ($this->cart as $item) {
                if ($item['type'] == 1 && empty($item['employee_id'])) {
                    $this->dispatch('alert', 'DEBE ASIGNAR UN USUARIO AL SERVICIO: ' . $item['name'], 'error');
                    return;
                }
            }
        }

        if ($this->selectedPayment === 'EFECTIVO') {
            $this->efectivo = number_format((float) $this->total_cart, 2, '.', '');
            $this->calculatePayment();
        }

        $this->dispatch('paymentModal');
    }

    public function confirmSale()
    {
        $rules = ['sale_date' => 'required|date'];
        $messages = [
            'sale_date.required' => 'La fecha de venta es obligatoria.',
            'sale_date.date' => 'La fecha de venta debe ser válida.',
        ];

        if ($this->selectedPayment === 'EFECTIVO') {
            if (floatval($this->efectivo) < $this->total_cart) {
                $this->dispatch('alert', 'El efectivo debe ser mayor o igual al total.', 'error');
                return;
            }
        }
        if ($this->selectedPayment === 'MULTIPLE') {
            $totalPago = floatval($this->efectivo) + floatval($this->tarjeta) + floatval($this->qr);
            if ($totalPago < $this->total_cart) {
                $this->dispatch('alert', 'El monto pagado es menor al total de la venta.', 'error');
                return;
            }
        }

        $this->validate($rules, $messages);

        if ($this->is_editing && $this->sale_id) {
            $this->updateSale();
            return;
        }

        DB::beginTransaction();
        try {
            $branch = Branche::find($this->branch_id);
            $branchCode = $branch && $branch->code ? $branch->code : 'SUC' . $this->branch_id;
            $currentYear = now()->format('y');

            $lastSale = Sale::where('branch_id', $this->branch_id)
                ->where('sale_number', 'like', "{$branchCode}-%-{$currentYear}")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastSale) {
                preg_match("/^{$branchCode}-(\d+)-{$currentYear}$/", $lastSale->sale_number, $matches);
                $nextNumber = isset($matches[1]) ? ((int) $matches[1] + 1) : 1;
            }

            $saleNumber = "{$branchCode}-{$nextNumber}-{$currentYear}";
            while (Sale::where('sale_number', $saleNumber)->exists()) {
                $nextNumber++;
                $saleNumber = "{$branchCode}-{$nextNumber}-{$currentYear}";
            }

            $saleDateTime = $this->sale_date . ' ' . now()->format('H:i:s');

            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'total' => $this->total_cart,
                'discount' => $this->discount,
                'worker_id' => $this->workers_id,
                'branch_id' => $this->branch_id,
                'user_id' => auth()->id(),
                'observations' => $this->observations ?: null,
                'status' => 1,
                'created_at' => $saleDateTime,
                'updated_at' => now(),
            ]);

            $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

            foreach ($this->cart as $item) {
                $product = Product::find($item['id']);

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'warehouse_id' => $warehouseId,
                    'employee_id' => $item['employee_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'sale_price' => $item['sale_price'],
                    'price_type' => $item['selected_price_type'] ?? 'normal',
                    'subtotal' => $item['subtotal'],
                    'unit_id' => !empty($item['unit_id']) ? $item['unit_id'] : null,
                    'observations' => null,
                    'created_at' => $saleDateTime,
                    'updated_at' => now(),
                ]);

                if ($product && $product->type == 0) {
                    $stockToDeduct = $item['quantity'];
                    $inventory = Inventorie::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
                    if ($inventory) {
                        if ($product->lote == 1) {
                            $inventory->stock_lot -= $stockToDeduct;
                        } else {
                            $inventory->stock_nolot -= $stockToDeduct;
                        }
                        $inventory->save();
                    }

                    $lastKardex = Kardex::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->orderBy('created_at', 'desc')->first();
                    $previousBalance = $lastKardex ? $lastKardex->balance : 0;

                    Kardex::create([
                        'type' => 'SALIDA',
                        'description' => 'NOTA DE VENTA - ' . $sale->sale_number,
                        'quantity_in' => 0,
                        'quantity_out' => $stockToDeduct,
                        'balance' => $previousBalance - $stockToDeduct,
                        'price' => $item['purchase_price'],
                        'total' => $stockToDeduct * $item['purchase_price'],
                        'product_id' => $item['id'],
                        'user_id' => auth()->id(),
                        'warehouse_id' => $warehouseId,
                        'transaction_type' => 'sales',
                        'transaction_id' => $sale->id,
                        'status' => 1,
                    ]);
                }
            }

            $payment_id = null;
            if ($this->selectedPayment === 'MULTIPLE') {
                if ($this->efectivo > 0) {
                    $p = Payment::create(['description' => 'EFECTIVO', 'amount' => $this->efectivo, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    $payment_id = $p->id;
                }
                if ($this->tarjeta > 0) {
                    $p = Payment::create(['description' => 'TARJETA', 'amount' => $this->tarjeta, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    if (!$payment_id) $payment_id = $p->id;
                }
                if ($this->qr > 0) {
                    $p = Payment::create(['description' => 'QR', 'amount' => $this->qr, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    if (!$payment_id) $payment_id = $p->id;
                }
            } else {
                $payment = Payment::create(['description' => $this->selectedPayment, 'amount' => $this->total_cart, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                $payment_id = $payment->id;
            }

            Movement::create([
                'type' => 'INGRESO',
                'type_movements' => 'VENTA',
                'description' => 'NOTA DE VENTA - ' . $sale->sale_number,
                'transaction_id' => $sale->id,
                'transaction_type' => 'sales',
                'payment_id' => $payment_id,
                'branch_id' => $this->branch_id,
                'amount' => $this->total_cart,
                'user_id' => auth()->id(),
                'status' => 1,
            ]);

            DB::commit();

            $this->clearSales();
            $this->sale_date = now()->format('Y-m-d');
            $this->resetInputConfirmSale();
            $this->dispatch('processPrintBehavior', url: '#', behavior: 'none', message: 'VENTA REALIZADA CON ÉXITO');
            $this->dispatch('focusSearchInput');

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al crear la venta: ' . $e->getMessage(), 'error');
        }
    }

    private function updateSale()
    {
        DB::beginTransaction();
        try {
            $sale = Sale::findOrFail($this->sale_id);
            $originalCreatedAt = $this->sale_date . ' ' . $sale->created_at->format('H:i:s');

            $oldDetails = SaleDetail::with(['product'])->where('sale_id', $sale->id)->get();

            $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

            foreach ($oldDetails as $oldDetail) {
                if ($oldDetail->product && $oldDetail->product->type == 0) {
                    $stockToRestore = $oldDetail->quantity;
                    $inventory = Inventorie::where('product_id', $oldDetail->product_id)->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
                    if ($inventory) {
                        if ($oldDetail->product->lote == 1) {
                            $inventory->stock_lot += $stockToRestore;
                        } else {
                            $inventory->stock_nolot += $stockToRestore;
                        }
                        $inventory->save();
                    }

                    $lastKardex = Kardex::where('product_id', $oldDetail->product_id)->where('warehouse_id', $warehouseId)->orderBy('created_at', 'desc')->first();
                    $bal = $lastKardex ? $lastKardex->balance : 0;

                    Kardex::create([
                        'type' => 'AJUSTE',
                        'description' => 'MODIFICACIÓN VENTA (REVERSIÓN) - ' . $sale->sale_number,
                        'quantity_in' => $stockToRestore,
                        'quantity_out' => 0,
                        'balance' => $bal + $stockToRestore,
                        'price' => $oldDetail->purchase_price,
                        'total' => $stockToRestore * $oldDetail->purchase_price,
                        'product_id' => $oldDetail->product_id,
                        'user_id' => auth()->id(),
                        'warehouse_id' => $warehouseId,
                        'transaction_type' => 'sales',
                        'transaction_id' => $sale->id,
                        'status' => 1,
                    ]);
                }
            }

            SaleDetail::where('sale_id', $sale->id)->delete();

            $sale->update([
                'total' => $this->total_cart,
                'discount' => $this->discount,
                'observations' => $this->observations ?: null,
                'worker_id' => $this->workers_id,
                'created_at' => $originalCreatedAt,
                'updated_at' => now(),
            ]);

            foreach ($this->cart as $item) {
                $product = Product::find($item['id']);

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'warehouse_id' => $warehouseId,
                    'employee_id' => $item['employee_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'sale_price' => $item['sale_price'],
                    'price_type' => $item['selected_price_type'] ?? 'normal',
                    'subtotal' => $item['subtotal'],
                    'unit_id' => !empty($item['unit_id']) ? $item['unit_id'] : null,
                    'observations' => null,
                    'created_at' => $originalCreatedAt,
                    'updated_at' => now(),
                ]);

                if ($product && $product->type == 0) {
                    $stockToDeduct = $item['quantity'];
                    $inventory = Inventorie::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
                    if ($inventory) {
                        if ($product->lote == 1) {
                            $inventory->stock_lot -= $stockToDeduct;
                        } else {
                            $inventory->stock_nolot -= $stockToDeduct;
                        }
                        $inventory->save();
                    }

                    $lastKardex = Kardex::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->orderBy('created_at', 'desc')->first();
                    $previousBalance = $lastKardex ? $lastKardex->balance : 0;

                    Kardex::create([
                        'type' => 'AJUSTE',
                        'description' => 'MODIFICACIÓN VENTA (NUEVO) - ' . $sale->sale_number,
                        'quantity_in' => 0,
                        'quantity_out' => $stockToDeduct,
                        'balance' => $previousBalance - $stockToDeduct,
                        'price' => $item['purchase_price'],
                        'total' => $stockToDeduct * $item['purchase_price'],
                        'product_id' => $item['id'],
                        'user_id' => auth()->id(),
                        'warehouse_id' => $warehouseId,
                        'transaction_type' => 'sales',
                        'transaction_id' => $sale->id,
                        'status' => 1,
                    ]);
                }
            }

            Payment::where('transaction_type', 'sales')->where('transaction_id', $sale->id)->delete();
            Movement::where('transaction_type', 'sales')->where('transaction_id', $sale->id)->delete();

            $payment_id = null;
            if ($this->selectedPayment === 'MULTIPLE') {
                if ($this->efectivo > 0) {
                    $p = Payment::create(['description' => 'EFECTIVO', 'amount' => $this->efectivo, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    $payment_id = $p->id;
                }
                if ($this->tarjeta > 0) {
                    $p = Payment::create(['description' => 'TARJETA', 'amount' => $this->tarjeta, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    if (!$payment_id) $payment_id = $p->id;
                }
                if ($this->qr > 0) {
                    $p = Payment::create(['description' => 'QR', 'amount' => $this->qr, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    if (!$payment_id) $payment_id = $p->id;
                }
            } else {
                $payment = Payment::create(['description' => $this->selectedPayment, 'amount' => $this->total_cart, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                $payment_id = $payment->id;
            }

            Movement::create([
                'type' => 'INGRESO',
                'type_movements' => 'VENTA',
                'description' => 'MODIFICACIÓN VENTA - ' . $sale->sale_number,
                'transaction_id' => $sale->id,
                'transaction_type' => 'sales',
                'payment_id' => $payment_id,
                'branch_id' => $this->branch_id,
                'amount' => $this->total_cart,
                'user_id' => auth()->id(),
                'status' => 1,
            ]);

            DB::commit();

            $this->clearSales();
            $this->resetInputConfirmSale();
            $this->dispatch('processPrintBehavior', url: '#', behavior: 'none', message: 'VENTA ACTUALIZADA CON ÉXITO');
            return redirect()->route('sales_lists');

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al actualizar: ' . $e->getMessage(), 'error');
        }
    }

    public function clearSales()
    {
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;

        session()->forget($cartSessionKey);
        session()->forget($totalSessionKey);
        session()->forget('is_editing_' . $this->branch_id);

        $this->clearWorkerSelection();

        if ($this->is_editing) {
            return redirect()->route('sales_lists');
        }
    }

    public function resetInputConfirmSale()
    {
        $this->resetValidation();
        $this->resetPayment();
        $this->clearWorkerSelection();
        $this->discount = 0;
        $this->total_cart = 0;
        $this->subtotal = 0;
        $this->searchTerm = '';
        $this->observations = '';
    }

    public function resetInputFields()
    {
        $this->search = '';
        $this->products = [];
    }

    public function openEmployeeModal($cartKey)
    {
        $this->selectedCartKeyForEmployee = $cartKey;
        $this->searchEmployee = '';
        $this->updateEmployeeList();
        $this->dispatch('show-employee-modal');
    }

    public function updatedSearchEmployee()
    {
        $this->updateEmployeeList();
    }

    private function updateEmployeeList()
    {
        $query = User::where('branch_id', $this->branch_id)->where('status', 1);
        if (strlen($this->searchEmployee) > 0) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchEmployee . '%')
                    ->orWhere('lastname', 'like', '%' . $this->searchEmployee . '%')
                    ->orWhere('document', 'like', '%' . $this->searchEmployee . '%')
                    ->orWhere('email', 'like', '%' . $this->searchEmployee . '%');
            });
        }
        $this->listEmployees = $query->limit(10)->get();
    }

    public function setEmployee($employeeId)
    {
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        if (isset($cart[$this->selectedCartKeyForEmployee])) {
            $employee = User::find($employeeId);
            if ($employee) {
                $cart[$this->selectedCartKeyForEmployee]['employee_id'] = $employee->id;
                $cart[$this->selectedCartKeyForEmployee]['employee_name'] = trim($employee->name . ' ' . $employee->lastname);
                session()->put($cartSessionKey, $cart);
                $this->refreshData();
                $this->dispatch('close-employee-modal');
                $this->dispatch('alert', 'USUARIO ASIGNADO', 'success');
            }
        }
    }
}
