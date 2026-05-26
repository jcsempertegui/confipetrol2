<?php

namespace App\Livewire;

use App\Models\ProductComponent;
use Livewire\Attributes\On;
use App\Models\Kardex;
use App\Models\Printer;
use App\Traits\PaymentLogic;
use Livewire\Component;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Inventorie;
use App\Models\Movement;
use App\Models\CashBoxe;
use App\Models\Branche;
use App\Models\Lot;
use App\Models\Payment;
use App\Models\Credit;
use App\Models\DetailLot;
use App\Models\User;
use App\Models\DetailSku;
use App\Models\ProductSku;
use App\Models\Warehouse;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
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

    public $name, $document_type, $document, $phone, $email, $address, $status, $customer_id;
    public $customer_document, $customer_lastname, $customers_id;
    public $isEditMode = false;

    public $customerSearchTerm = '';
    public $customerResults = [];
    public $showCustomerDropdown = false;

    public $boxExists;
    public $initial_amount = '';
    public $branch_id;

    public $selectedProduct = null;
    public $stockDetails = [];
    public $observations = '';

    public $listSkus = [];
    public $selectedSku = null;

    public $listProductUnits = [];
    public $selectedProductUnit = null;

    public $enable_staff_per_detail = 0;
    public $selectedCartKeyForEmployee = null;
    public $listEmployees = [];
    public $searchEmployee = '';

    public $loyalty_program = 0;
    public $loyalty_summary = [];

    public $sale_id;
    public $is_editing = false;
    public $activeLotCartKey = null;
    public $sale_date;

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

    public function updatedCustomerSearchTerm()
    {
        if (strlen($this->customerSearchTerm) >= 1) {
            $this->customerResults = Customer::select('id', 'name', 'document')
                ->where('status', 1)
                ->where(function ($q) {
                    $q->where('document', 'LIKE', '%' . $this->customerSearchTerm . '%')
                        ->orWhere('name', 'LIKE', '%' . $this->customerSearchTerm . '%');
                })
                ->limit(10)
                ->get();
            $this->showCustomerDropdown = true;
        } else {
            $this->customerResults = [];
            $this->showCustomerDropdown = false;
        }
    }

    public function selectCustomer($id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $this->customers_id = $customer->id;
            $this->customer_document = $customer->document;
            $this->customer_lastname = $customer->name;
            $this->customerSearchTerm = $customer->name . ' - ' . $customer->document;
            $this->customerResults = Customer::where('id', $customer->id)->get();
            $this->showCustomerDropdown = false;
            $this->applyLoyalty();
            $this->dispatch('alert', 'CLIENTE SELECCIONADO', 'success');
        }
    }

    public function clearCustomerSearch()
    {
        if ($this->customers_id) {
            $this->customerSearchTerm = $this->customer_lastname . ' - ' . $this->customer_document;
        } else {
            $this->setDefaultCustomer();
        }
        $this->showCustomerDropdown = false;
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
        $this->loyalty_program = $branch ? $branch->loyalty_program : 0;
    }

    public function openCashBox()
    {
        $rules = [
            'initial_amount' => 'required|numeric|min:0|max:99999999.99|regex:/^\d{1,8}(\.\d{1,2})?$/',
        ];

        $messages = [
            'initial_amount.required' => 'El monto inicial es requerido.',
            'initial_amount.numeric' => 'El monto inicial debe ser un número.',
            'initial_amount.max' => 'El monto inicial no puede superar 99,999,999.99',
            'initial_amount.regex' => 'El monto inicial debe tener máximo 8 dígitos y 2 decimales.',
        ];

        $this->validate($rules, $messages);

        $branch = Branche::find($this->branch_id);
        $pos_type = $branch ? $branch->pos_type : 1;

        if ($pos_type == 4) {
            $cashBox = CashBoxe::where('branch_id', $this->branch_id)->where('is_open', 1)->where('status', 1)->first();
        } else {
            $cashBox = CashBoxe::where('user_id', auth()->id())->where('branch_id', $this->branch_id)->where('is_open', 1)->where('status', 1)->first();
        }

        if ($cashBox) {
            $this->initial_amount = '';
            $this->dispatch('alert', 'POR FAVOR, CIERRE LA CAJA ACTUAL ANTES DE ABRIR UNA NUEVA.', 'error');
            return;
        }

        $newCashBox = CashBoxe::create([
            'initial_amount' => $this->initial_amount,
            'branch_id' => $this->branch_id,
            'user_id' => auth()->id(),
            'is_open' => 1,
            'status' => 1
        ]);

        Movement::create([
            'type' => 'INGRESO',
            'type_movements' => 'APERTURA DE CAJA',
            'description' => '',
            'transaction_id' => $newCashBox->id,
            'transaction_type' => 'cash_boxes',
            'branch_id' => $this->branch_id,
            'amount' => $this->initial_amount,
            'cash_box_id' => $newCashBox->id,
            'user_id' => auth()->id(),
            'status' => 1
        ]);

        $this->initial_amount = '';
        $this->boxExists = true;
        $this->dispatch('cash_boxeStoreOrUpdate', 'CAJA APERTURADA CON ÉXITO.');
    }

    private function getOpenCashBox()
    {
        $branch = Branche::find($this->branch_id);
        if (!$branch || $branch->requires_cashbox == 0) {
            return (object) ['id' => null];
        }

        $pos_type = (int) $branch->pos_type;
        if ($pos_type == 4) {
            return CashBoxe::select('id', 'branch_id', 'is_open', 'status')->where('branch_id', $this->branch_id)->where('is_open', 1)->where('status', 1)->first();
        } else {
            return CashBoxe::select('id', 'user_id', 'branch_id', 'is_open', 'status')->where('user_id', auth()->id())->where('branch_id', $this->branch_id)->where('is_open', 1)->where('status', 1)->first();
        }
    }

    public function applyLoyalty()
    {
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        $this->loyalty_summary = [];
        $subtotalGeneral = 0;

        if (!$this->loyalty_program || !$this->customers_id || $this->customers_id == 1) {
            foreach ($cart as $key => &$item) {
                $item['free_qty'] = 0;
                $item['subtotal'] = $item['quantity'] * $item['sale_price'];
                $subtotalGeneral += $item['subtotal'];
            }
            $this->updateTotalsAfterLoyalty($cart, $subtotalGeneral, $cartSessionKey, $totalSessionKey);
            return;
        }

        $cartProductQtys = [];
        foreach ($cart as $key => $item) {
            $pId = $item['id'];
            if (!isset($cartProductQtys[$pId])) {
                $cartProductQtys[$pId] = 0;
            }
            $unitFactor = isset($item['unit_factor']) && $item['unit_factor'] > 0 ? $item['unit_factor'] : 1;
            $cartProductQtys[$pId] += ($item['quantity'] * $unitFactor);
        }

        $freeCutsToApplyPerProduct = [];
        $productsLoyalty = Product::whereIn('id', array_keys($cartProductQtys))->get()->keyBy('id');

        foreach ($cartProductQtys as $pId => $cartQty) {
            $product = $productsLoyalty->get($pId);
            if ($product && $product->has_loyalty == 1 && $product->loyalty_req_qty > 0) {
                $reqQty = $product->loyalty_req_qty;
                $cycle = $reqQty + 1;

                $pastCuts = DB::table('sale_details')
                    ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                    ->join('products', 'sale_details.product_id', '=', 'products.id')
                    ->leftJoin('units', 'sale_details.unit_id', '=', 'units.id')
                    ->where('sales.customer_id', $this->customers_id)
                    ->where('sales.status', 1)
                    ->where('sale_details.product_id', $pId)
                    ->sum(DB::raw('sale_details.quantity * COALESCE(units.factor, 1)'));

                $totalFreeEarned = floor(($pastCuts + $cartQty) / $cycle);
                $pastFreeEarned = floor($pastCuts / $cycle);
                $freeAvailable = max(0, $totalFreeEarned - $pastFreeEarned);
                $progress = $pastCuts % $cycle;

                $this->loyalty_summary[$pId] = [
                    'name' => $product->name,
                    'req_qty' => $reqQty,
                    'past_qty' => $pastCuts,
                    'progress' => $progress,
                    'free_available' => $freeAvailable,
                ];

                $freeCutsToApplyPerProduct[$pId] = $freeAvailable;
            }
        }

        foreach ($cart as $key => &$item) {
            $pId = $item['id'];
            if (isset($freeCutsToApplyPerProduct[$pId]) && $freeCutsToApplyPerProduct[$pId] > 0) {
                $unitFactor = isset($item['unit_factor']) && $item['unit_factor'] > 0 ? $item['unit_factor'] : 1;
                $baseFreeNeeded = $freeCutsToApplyPerProduct[$pId];
                $itemBaseQty = $item['quantity'] * $unitFactor;
                $freeBaseForThisItem = min($itemBaseQty, $baseFreeNeeded);
                $freeForThisItemInUnits = floor($freeBaseForThisItem / $unitFactor);
                $paidQty = $item['quantity'] - $freeForThisItemInUnits;

                $item['subtotal'] = $paidQty * $item['sale_price'];
                $item['free_qty'] = $freeForThisItemInUnits;
                $freeCutsToApplyPerProduct[$pId] -= ($freeForThisItemInUnits * $unitFactor);
            } else {
                $item['free_qty'] = 0;
                $item['subtotal'] = $item['quantity'] * $item['sale_price'];
            }
            $subtotalGeneral += $item['subtotal'];
        }

        $this->updateTotalsAfterLoyalty($cart, $subtotalGeneral, $cartSessionKey, $totalSessionKey);
    }

    private function updateTotalsAfterLoyalty($cart, $subtotalGeneral, $cartSessionKey, $totalSessionKey)
    {
        session()->put($cartSessionKey, $cart);
        $this->cart = $cart;
        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = isset($totalData['discount']) ? $totalData['discount'] : 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;

        $totalData['subtotal'] = $subtotalGeneral;
        $totalData['total'] = $nuevoTotal;
        session()->put($totalSessionKey, $totalData);

        $this->total_cart = $nuevoTotal;
        $this->subtotal = $subtotalGeneral;
        $this->items = count($cart);
    }

    public function setDefaultCustomer()
    {
        $customers = Customer::select('id', 'name', 'document')->where('status', 1)->where('id', 1)->first();
        if ($customers) {
            $this->customers_id = $customers->id;
            $this->customer_document = $customers->document;
            $this->customer_lastname = $customers->name;
            $this->customerSearchTerm = $customers->name . ' - ' . $customers->document;
            $this->customerResults = Customer::where('id', $customers->id)->get();
        } else {
            $this->customerResults = [];
        }
        $this->showCustomerDropdown = false;
        $this->applyLoyalty();
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
                'inventories.stock_nolot'
            ])
            ->leftJoin('inventories', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'inventories.product_id')
                    ->where('inventories.warehouse_id', $warehouseId);
            })
            ->where('products.status', 1)
            ->whereIn('products.type', [0, 1, 5])
            ->where(function ($q) use ($searchTerm) {
                $q->where('products.code', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('products.name', 'LIKE', '%' . $searchTerm . '%');
            })
            ->orderBy('products.name', 'asc')
            ->limit(7)
            ->get();
    }

    public function render()
    {
        $this->refreshData();

        $branch = Branche::find($this->branch_id);
        $requires_cashbox = $branch ? $branch->requires_cashbox : 1;

        if ($requires_cashbox == 1) {
            $this->boxExists = (bool) optional($this->getOpenCashBox())->id;
        } else {
            $this->boxExists = true;
        }

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

        $branch = Branche::find($this->branch_id);
        $requires_cashbox = $branch ? $branch->requires_cashbox : 1;
        if ($requires_cashbox == 1) {
            $this->boxExists = (bool) optional($this->getOpenCashBox())->id;
        } else {
            $this->boxExists = true;
        }

        $this->sale_date = now()->format('Y-m-d');
        $this->setDefaultCustomer();
        $this->refreshData();
        $this->applyLoyalty();

        $this->enable_staff_per_detail = $branch ? $branch->enable_staff_per_detail : 0;

        if ($sale_id) {
            $this->sale_id = $sale_id;
            $this->is_editing = true;
            session()->put('is_editing_' . $this->branch_id, true);
            $this->loadSaleForEdit($sale_id);
        }
    }

    private function calculateLotAllocation($productId, $quantity, $primaryLotId = null)
    {
        $lots = Lot::where('product_id', $productId)
            ->where('branch_id', $this->branch_id)
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', now()->toDateString());
            })
            ->orderBy('expiration_date', 'asc')
            ->get();

        if ($primaryLotId) {
            $manualLot = $lots->firstWhere('id', $primaryLotId);
            $others = $lots->where('id', '!=', $primaryLotId);
            if ($manualLot) {
                $lots = collect([$manualLot])->merge($others);
            }
        }

        $allocated = [];
        $remaining = $quantity;

        foreach ($lots as $l) {
            if ($remaining <= 0)
                break;
            $take = min($l->quantity, $remaining);
            $allocated[] = [
                'id' => $l->id,
                'lot_number' => $l->lot_number,
                'expiration_date' => $l->expiration_date,
                'quantity' => $take
            ];
            $remaining -= $take;
        }
        return $allocated;
    }

    public function openLotModal($cartKey)
    {
        $this->activeLotCartKey = $cartKey;
        $this->dispatch('show-lot-modal');
    }

    public function setPrimaryLot($cartKey, $lotId)
    {
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);
        if (isset($cart[$cartKey]) && !empty($cart[$cartKey]['available_lots'])) {
            foreach ($cart[$cartKey]['available_lots'] as $avLot) {
                if ($avLot['id'] == $lotId) {
                    $cart[$cartKey]['lot_info'] = [
                        'lot_id' => $avLot['id'],
                        'lot_number' => $avLot['lot_number'],
                        'expiration_date' => $avLot['expiration_date'],
                        'is_expired' => $avLot['expiration_date'] ? (now()->toDateString() >= $avLot['expiration_date']) : false,
                    ];
                    $unitFactor = isset($cart[$cartKey]['unit_factor']) && $cart[$cartKey]['unit_factor'] > 0 ? $cart[$cartKey]['unit_factor'] : 1;
                    $baseQuantity = $cart[$cartKey]['quantity'] * $unitFactor;
                    $cart[$cartKey]['allocated_lots'] = $this->calculateLotAllocation($cart[$cartKey]['id'], $baseQuantity, $lotId);
                    break;
                }
            }
            session()->put($cartSessionKey, $cart);
            $this->refreshData();
            $this->dispatch('close-lot-modal');
        }
    }

    private function loadSaleForEdit($sale_id)
    {
        $sale = Sale::with(['details.product', 'customer'])->findOrFail($sale_id);
        $this->sale_date = $sale->created_at ? $sale->created_at->format('Y-m-d') : now()->format('Y-m-d');
        $this->observations = $sale->observations ?? '';

        if ($sale->customer) {
            $this->customers_id = $sale->customer->id;
            $this->customer_document = $sale->customer->document;
            $this->customer_lastname = $sale->customer->name;
            $this->customerSearchTerm = $sale->customer->name . ' - ' . $sale->customer->document;
        }

        $payment = Payment::where('transaction_type', 'sales')->where('transaction_id', $sale->id)->first();
        $this->selectedPayment = $payment ? $payment->description : 'EFECTIVO';

        if ($this->selectedPayment === 'CREDITO') {
            $credit = Credit::where('creditable_type', 'sales')->where('creditable_id', $sale->id)->first();
            $this->due_date = $credit ? $credit->due_date : '';
        }

        $cart = [];
        foreach ($sale->details as $detail) {
            $product = $detail->product;
            if (!$product)
                continue;

            $additionalPrices = DB::table('product_prices')
                ->where('product_id', $product->id)
                ->where('status', 1)
                ->get()
                ->map(function ($p) {
                    return (array) $p; })
                ->toArray();

            $lotInfo = null;
            $allocatedLots = [];
            $availableLots = [];

            if ($product->lote == 1) {
                $dl = DetailLot::where('detail_type', SaleDetail::class)->where('detail_id', $detail->id)->get();
                $primaryLotId = null;
                if ($dl->count() > 0) {
                    foreach ($dl as $d) {
                        $l = Lot::find($d->lot_id);
                        if ($l) {
                            $allocatedLots[] = [
                                'id' => $l->id,
                                'lot_number' => $l->lot_number,
                                'expiration_date' => $l->expiration_date,
                                'quantity' => $d->quantity
                            ];
                            if (!$primaryLotId)
                                $primaryLotId = $l->id;
                        }
                    }
                    if (count($allocatedLots) > 0) {
                        $cartDate = $allocatedLots[0]['expiration_date'];
                        $lotInfo = [
                            'lot_id' => $allocatedLots[0]['id'],
                            'lot_number' => $allocatedLots[0]['lot_number'],
                            'expiration_date' => $cartDate,
                            'is_expired' => $cartDate ? (now()->toDateString() >= $cartDate) : false,
                        ];
                    }
                }
                $validLots = Lot::where('product_id', $product->id)
                    ->where('branch_id', $this->branch_id)
                    ->where('quantity', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('expiration_date')->orWhere('expiration_date', '>', now()->toDateString());
                    })->orderBy('expiration_date', 'asc')->get();

                foreach ($validLots as $l) {
                    $availableLots[] = [
                        'id' => $l->id,
                        'lot_number' => $l->lot_number,
                        'expiration_date' => $l->expiration_date,
                    ];
                }
                if ($lotInfo && !collect($availableLots)->contains('id', $lotInfo['lot_id'])) {
                    $availableLots[] = [
                        'id' => $lotInfo['lot_id'],
                        'lot_number' => $lotInfo['lot_number'],
                        'expiration_date' => $lotInfo['expiration_date'],
                    ];
                }
            }

            $sku_id = null;
            $sku_name = null;
            $ds = DetailSku::where('detail_type', SaleDetail::class)->where('detail_id', $detail->id)->first();
            if ($ds) {
                $sku_id = $ds->sku_id;
                $sku = ProductSku::with(['size', 'color'])->find($sku_id);
                if ($sku) {
                    $sizeName = $sku->size ? $sku->size->name : '';
                    $colorName = $sku->color ? $sku->color->name : '';
                    $skuName = trim($sizeName . ($sizeName && $colorName ? ' - ' : '') . $colorName);
                }
            }

            $employee_id = $detail->employee_id;
            $employee_name = null;
            if ($employee_id) {
                $emp = User::find($employee_id);
                if ($emp)
                    $employee_name = trim($emp->name . ' ' . $emp->lastname);
            }

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

            $cartKey = $sku_id ? $product->id . '_' . $sku_id : (string) $product->id;
            if ($unit_id) {
                $cartKey .= '_U' . $unit_id;
            }

            $cart[$cartKey] = [
                'id' => $product->id,
                'cartKey' => $cartKey,
                'name' => $product->name,
                'code' => $product->code,
                'type' => $product->type,
                'purchase_price' => $detail->purchase_price,
                'sale_price' => $detail->sale_price,
                'original_sale_price' => $detail->sale_price,
                'prices' => $additionalPrices,
                'wholesale_min_quantity' => $detail->wholesale_min_quantity,
                'is_wholesale' => $detail->price_type == 'wholesale',
                'is_custom_price' => $detail->price_type == 'custom',
                'selected_price_type' => $detail->price_type,
                'quantity' => $detail->quantity,
                'subtotal' => $detail->subtotal,
                'lot_info' => $lotInfo,
                'allocated_lots' => $allocatedLots,
                'available_lots' => $availableLots,
                'sku_id' => $sku_id,
                'sku_name' => $sku_name,
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
        $totalWithDiscount = $totalAmount - $discount;

        session()->put($totalSessionKey, [
            'subtotal' => $totalAmount,
            'total' => $totalWithDiscount,
            'discount' => $discount,
        ]);
        $this->applyLoyalty();
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

        $alertType = 'success';
        $alertMessage = 'PRECIO ACTUALIZADO';

        if (isset($cart[$cartKey])) {
            $newPrice = floatval($selected_price);
            if ($newPrice < $cart[$cartKey]['purchase_price']) {
                $alertType = 'warning';
                $alertMessage = 'PRECIO ACTUALIZADO (MENOR AL DE COMPRA)';
            }
            $cart[$cartKey]['sale_price'] = $newPrice;
            $cart[$cartKey]['subtotal'] = $cart[$cartKey]['quantity'] * $newPrice;
            $cart[$cartKey]['is_custom_price'] = false;
            $cart[$cartKey]['is_wholesale'] = false;
            $cart[$cartKey]['selected_price_type'] = $priceName;
        }

        session()->put($cartSessionKey, $cart);
        $subtotalGeneral = 0;
        foreach ($cart as $item) {
            $subtotalGeneral += $item['subtotal'];
        }
        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = isset($totalData['discount']) ? $totalData['discount'] : 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;
        $totalData = [
            'subtotal' => $subtotalGeneral,
            'total' => $nuevoTotal,
            'discount' => $descuentoPorcentaje,
        ];
        session()->put($totalSessionKey, $totalData);
        $this->applyLoyalty();
        $this->dispatch('alert', $alertMessage, $alertType);
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

        $alertType = 'success';
        $alertMessage = 'PRECIO VENTA ACTUALIZADO';

        if (isset($cart[$cartKey])) {
            $newPrice = floatval($sale_price);
            if ($newPrice < $cart[$cartKey]['purchase_price']) {
                $alertType = 'warning';
                $alertMessage = 'PRECIO ACTUALIZADO (MENOR AL DE COMPRA)';
            }

            $cart[$cartKey]['sale_price'] = $newPrice;
            $cart[$cartKey]['subtotal'] = $cart[$cartKey]['quantity'] * $newPrice;
            $cart[$cartKey]['is_custom_price'] = true;
            $cart[$cartKey]['is_wholesale'] = false;
            $cart[$cartKey]['show_custom_input'] = false;
        }

        session()->put($cartSessionKey, $cart);
        $subtotalGeneral = 0;
        foreach ($cart as $item) {
            $subtotalGeneral += $item['subtotal'];
        }

        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = isset($totalData['discount']) ? $totalData['discount'] : 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;

        $totalData = [
            'subtotal' => $subtotalGeneral,
            'total' => $nuevoTotal,
            'discount' => $descuentoPorcentaje,
            'show_custom_input' => false,
        ];
        session()->put($totalSessionKey, $totalData);
        $this->applyLoyalty();
        $this->dispatch('alert', $alertMessage, $alertType);
    }

    public function AddOrUpdate($product_id, $searchType = 'id')
    {
        $branch_id = $this->branch_id;

        if ($product_id === 'null' || $product_id === null) {
            $this->dispatch('alert', 'NO HAY PRODUCTOS DISPONIBLES O SELECCIONADOS', 'error');
            return;
        }

        $productCheck = Product::where('status', 1)
            ->whereIn('type', [0, 1, 5])
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

        if ($productCheck->type == 5) {
            $this->processAddToCart($productCheck, null, null);
            return;
        }

        $hasSkus = DB::table('product_skus')->where('product_id', $productCheck->id)->where('branch_id', $branch_id)->where('stock', '>', 0)->exists();
        $hasProductUnits = DB::table('product_units')->where('product_id', $productCheck->id)->where('status', 1)->exists();

        if ($hasSkus) {
            $defaultWarehouse = Warehouse::where('branch_id', $branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

            $this->selectedProduct = Product::with([
                'inventories' => function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }
            ])->find($productCheck->id);

            $this->listSkus = ProductSku::with(['size', 'color'])->where('product_id', $productCheck->id)->where('branch_id', $branch_id)->where('stock', '>', 0)->get();
            $this->selectedSku = null;
            $this->search = '';
            $this->products = [];
            $this->dispatch('openSkuModal');
            return;
        } elseif ($hasProductUnits) {
            $defaultWarehouse = Warehouse::where('branch_id', $branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

            $this->selectedProduct = Product::with([
                'units',
                'inventories' => function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }
            ])->find($productCheck->id);

            $currentStock = 0;
            $basePrice = 0;
            $invDirect = Inventorie::where('product_id', $productCheck->id)->where('warehouse_id', $warehouseId)->first();

            if ($invDirect) {
                $currentStock = $this->selectedProduct->lote == 1 ? (int) $invDirect->stock_lot : (int) $invDirect->stock_nolot;
                $basePrice = $invDirect->sale_price;
            } elseif ($this->selectedProduct && $this->selectedProduct->inventories) {
                $inv = is_iterable($this->selectedProduct->inventories) ? $this->selectedProduct->inventories->first() : $this->selectedProduct->inventories;
                if ($inv) {
                    $currentStock = $this->selectedProduct->lote == 1 ? (int) $inv->stock_lot : (int) $inv->stock_nolot;
                    $basePrice = $inv->sale_price;
                }
            }

            $unitsList = [];
            $baseUnitName = $this->selectedProduct->units ? $this->selectedProduct->units->name : 'UNIDAD';
            $unitsList[] = [
                'id' => 0,
                'name' => $baseUnitName,
                'factor' => 1,
                'price' => $basePrice,
                'stock' => $currentStock
            ];

            $additionalUnits = DB::table('product_units')
                ->join('units', 'product_units.unit_id', '=', 'units.id')
                ->where('product_units.product_id', $productCheck->id)
                ->where('product_units.status', 1)
                ->select('product_units.id', 'units.name', 'product_units.price', 'product_units.purchase_price', 'units.factor')
                ->get();

            foreach ($additionalUnits as $au) {
                $factor = $au->factor > 0 ? $au->factor : 1;
                $stockDisp = floor($currentStock / $factor);
                $priceDisp = $au->price > 0 ? $au->price : $basePrice * $factor;
                $unitsList[] = [
                    'id' => $au->id,
                    'name' => $au->name,
                    'factor' => $au->factor,
                    'price' => $priceDisp,
                    'stock' => $stockDisp
                ];
            }

            $this->listProductUnits = $unitsList;
            $this->selectedProductUnit = null;
            $this->search = '';
            $this->products = [];
            $this->dispatch('openProductUnitSaleModal');
            return;
        }

        $this->processAddToCart($productCheck, null, null);
    }

    public function confirmSku()
    {
        if (!$this->selectedSku) {
            $this->addError('selectedSku', 'Seleccione una opción.');
            return;
        }
        $sku = ProductSku::with(['size', 'color'])->find($this->selectedSku);
        if (!$sku || $sku->stock <= 0) {
            $this->dispatch('alert', 'SKU SIN STOCK', 'error');
            return;
        }
        $this->processAddToCart($this->selectedProduct, $sku, null);
        $this->dispatch('closeSkuModal');
    }

    public function confirmProductUnit()
    {
        if ($this->selectedProductUnit === null || $this->selectedProductUnit === '') {
            $this->addError('selectedProductUnit', 'Seleccione una opción.');
            return;
        }
        if ($this->selectedProductUnit == 0) {
            $baseUnitName = $this->selectedProduct->units ? $this->selectedProduct->units->name : 'UNIDAD';
            $baseUnitId = $this->selectedProduct->unit_id ?? null;
            $unit = (object) [
                'id' => $baseUnitId,
                'name' => $baseUnitName,
                'price' => null,
                'purchase_price' => null,
                'factor' => 1
            ];
            $this->processAddToCart($this->selectedProduct, null, $unit);
        } else {
            $unit = DB::table('product_units')
                ->join('units', 'product_units.unit_id', '=', 'units.id')
                ->where('product_units.id', $this->selectedProductUnit)
                ->select('units.id', 'product_units.price', 'product_units.purchase_price', 'units.name', 'units.factor')
                ->first();

            if (!$unit) {
                $this->dispatch('alert', 'UNIDAD NO ENCONTRADA', 'error');
                return;
            }
            $this->processAddToCart($this->selectedProduct, null, $unit);
        }
        $this->dispatch('closeProductUnitSaleModal');
    }

    private function processAddToCart($productCheck, $sku = null, $unit = null)
    {
        $branch_id = $this->branch_id;
        $product_id = $productCheck->id;

        $cartKey = $sku ? $product_id . '_' . $sku->id : (string) $product_id;
        if ($unit) {
            $cartKey .= '_U' . $unit->id;
        }

        $additionalPrices = DB::table('product_prices')
            ->where('product_id', $product_id)
            ->where('status', 1)
            ->get()->map(function ($p) {
                return (array) $p; })->toArray();

        $defaultWarehouse = Warehouse::where('branch_id', $branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;
        $inventory = Inventorie::where('product_id', $product_id)->where('warehouse_id', $warehouseId)->first();

        $basePurchasePrice = $inventory ? $inventory->purchase_price : 0;
        $baseSalePrice = $inventory ? $inventory->sale_price : 0;
        $unitFactor = $unit ? ($unit->factor > 0 ? $unit->factor : 1) : 1;

        if ($unit && isset($unit->purchase_price) && $unit->purchase_price > 0) {
            $purchasePrice = $unit->purchase_price;
        } else {
            $purchasePrice = $basePurchasePrice * $unitFactor;
        }

        if ($unit && isset($unit->price) && $unit->price > 0) {
            $salePrice = $unit->price;
        } else {
            $salePrice = $baseSalePrice * $unitFactor;
        }

        if ($productCheck->type == 5) {
            $cartSessionKey = 'sales_cart_' . $this->branch_id;
            $totalSessionKey = 'sales_total_' . $this->branch_id;
            $cart = session()->get($cartSessionKey, []);

            $currentComboQty = isset($cart[$cartKey]) ? (int) $cart[$cartKey]['quantity'] : 0;
            $newComboQty = $currentComboQty + 1;
            $components = ProductComponent::where('product_id', $product_id)->get();

            foreach ($components as $comp) {
                $compProduct = Product::find($comp->component_id);
                if (!$compProduct || $compProduct->type == 1)
                    continue;

                $requiredQtyPerUnit = (float) $comp->quantity;
                if ($compProduct->lote == 1) {
                    $availableStock = Lot::where('product_id', $compProduct->id)->where('branch_id', $this->branch_id)->where('quantity', '>', 0)->sum('quantity');
                } else {
                    $compInvDirect = Inventorie::where('product_id', $compProduct->id)->where('warehouse_id', $warehouseId)->first();
                    $availableStock = $compInvDirect ? (float) $compInvDirect->stock_nolot : 0;
                }

                $alreadyInCart = 0;
                foreach ($cart as $ck => $cartItem) {
                    if ((string) $ck == (string) $cartKey)
                        continue;
                    if ($cartItem['type'] == 5 || $cartItem['type'] == 2) {
                        $otherComponents = ProductComponent::where('product_id', $cartItem['id'])->get();
                        foreach ($otherComponents as $oc) {
                            if ((int) $oc->component_id === (int) $compProduct->id) {
                                $alreadyInCart += (float) $cartItem['quantity'] * (float) $oc->quantity;
                            }
                        }
                    }
                }

                $neededForThisCombo = $newComboQty * $requiredQtyPerUnit;
                $totalNeeded = $alreadyInCart + $neededForThisCombo;

                if ($totalNeeded > $availableStock) {
                    $this->dispatch('alert', 'STOCK INSUFICIENTE: Falta ' . $compProduct->name . ' (Disponible: ' . (int) $availableStock . ', Necesario: ' . (int) $totalNeeded . ')', 'error');
                    return;
                }
            }

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
                    'type' => 5,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice,
                    'original_sale_price' => $salePrice,
                    'prices' => $additionalPrices,
                    'wholesale_min_quantity' => null,
                    'is_wholesale' => false,
                    'quantity' => 1,
                    'subtotal' => $salePrice,
                    'lot_info' => null,
                    'allocated_lots' => [],
                    'available_lots' => [],
                    'sku_id' => null,
                    'sku_name' => null,
                    'unit_id' => null,
                    'unit_name' => null,
                    'unit_factor' => 1,
                    'employee_id' => null,
                    'employee_name' => null,
                    'is_custom_price' => false,
                    'selected_price_type' => 'normal',
                    'free_qty' => 0,
                ];
            }

            $totalAmount = collect($cart)->sum('subtotal');
            session()->put($cartSessionKey, $cart);
            session()->put($totalSessionKey, ['subtotal' => $totalAmount, 'total' => $totalAmount, 'discount' => 0]);
            $this->applyLoyalty();
            $this->dispatch('alert', 'COMBO AGREGADO AL CARRITO', 'success');
            $this->resetInputFields();
            $this->dispatch('focusSearchInput');
            return;
        }

        if ($productCheck->type == 1) {
            $cartSessionKey = 'sales_cart_' . $this->branch_id;
            $totalSessionKey = 'sales_total_' . $this->branch_id;
            $cart = session()->get($cartSessionKey, []);

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
                    'prices' => $additionalPrices,
                    'wholesale_min_quantity' => null,
                    'is_wholesale' => false,
                    'quantity' => 1,
                    'subtotal' => $salePrice,
                    'lot_info' => null,
                    'allocated_lots' => [],
                    'available_lots' => [],
                    'sku_id' => null,
                    'sku_name' => null,
                    'unit_id' => $unit ? $unit->id : null,
                    'unit_name' => $unit ? $unit->name : null,
                    'unit_factor' => $unitFactor,
                    'employee_id' => null,
                    'employee_name' => null,
                    'is_custom_price' => false,
                    'selected_price_type' => 'normal',
                ];
            }

            $totalAmount = collect($cart)->sum('subtotal');
            session()->put($cartSessionKey, $cart);
            session()->put($totalSessionKey, ['subtotal' => $totalAmount, 'total' => $totalAmount, 'discount' => 0]);
            $this->applyLoyalty();
            $this->dispatch('alert', 'SERVICIO AGREGADO AL CARRITO', 'success');
            $this->resetInputFields();
            $this->dispatch('focusSearchInput');
            return;
        }

        $productDetails = Product::select('products.id', 'products.name', 'products.type', 'products.lote', 'products.code', 'inventories.stock_lot', 'inventories.stock_nolot')
            ->join('inventories', function ($join) use ($product_id, $warehouseId) {
                $join->on('products.id', '=', 'inventories.product_id')
                    ->where('products.status', 1)
                    ->whereIn('products.type', [0, 1])
                    ->where('inventories.warehouse_id', $warehouseId);
            })->where('products.status', 1)->where('products.id', $product_id)->first();

        if (!$productDetails) {
            $this->dispatch('alert', 'NO HAY PRODUCTOS DISPONIBLES', 'error');
            return;
        }

        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);

        $currentQuantityInCart = isset($cart[$cartKey]) ? $cart[$cartKey]['quantity'] : 0;
        $newQuantity = $currentQuantityInCart + 1;

        if ($sku && $newQuantity > $sku->stock) {
            $this->dispatch('alert', 'STOCK INSUFICIENTE PARA ESTA TALLA/COLOR', 'error');
            return;
        }

        $baseQuantity = $newQuantity * $unitFactor;

        if ($productDetails->lote == 1) {
            $validLotsStock = Lot::where('product_id', $productDetails->id)
                ->where('branch_id', $this->branch_id)->where('quantity', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expiration_date')->orWhere('expiration_date', '>', now()->toDateString());
                })->sum('quantity');

            if ($baseQuantity > $validLotsStock) {
                $this->dispatch('alert', 'STOCK VIGENTE INSUFICIENTE (HAY LOTES VENCIDOS)', 'error');
                return;
            }
        } else {
            if ($baseQuantity > $productDetails->stock_nolot) {
                $this->dispatch('alert', 'STOCK INSUFICIENTE PARA ESTE PRODUCTO', 'error');
                return;
            }
        }

        $basePriceToUse = $salePrice;
        if ($sku && !is_null($sku->price)) {
            $basePriceToUse = $sku->price;
        }

        $currentPrice = $basePriceToUse;
        $isWholesalePrice = false;
        $wholesaleMinApplied = null;

        foreach ($additionalPrices as $ap) {
            if ($ap['type'] === 'wholesale') {
                $min = $ap['min_quantity'];
                $max = $ap['max_quantity'];
                if ($min && $newQuantity >= $min) {
                    if (!$max || $newQuantity <= $max) {
                        $currentPrice = $ap['price'];
                        $isWholesalePrice = true;
                        $wholesaleMinApplied = $min;
                        break;
                    }
                }
            }
        }

        $lotInfo = null;
        $allocatedLots = [];
        $availableLots = [];

        if ($productDetails->lote == 1) {
            $lots = Lot::where('product_id', $productDetails->id)->where('branch_id', $this->branch_id)->where('quantity', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expiration_date')->orWhere('expiration_date', '>', now()->toDateString());
                })->orderBy('expiration_date', 'asc')->get();

            if ($lots->count() > 0) {
                $primaryLotId = isset($cart[$cartKey]['lot_info']['lot_id']) ? $cart[$cartKey]['lot_info']['lot_id'] : $lots->first()->id;
                $firstLot = $lots->firstWhere('id', $primaryLotId) ?? $lots->first();
                $lotInfo = [
                    'lot_id' => $firstLot->id,
                    'lot_number' => $firstLot->lot_number,
                    'expiration_date' => $firstLot->expiration_date,
                    'is_expired' => $firstLot->expiration_date ? (now()->toDateString() >= $firstLot->expiration_date) : false,
                ];
                foreach ($lots as $l) {
                    $availableLots[] = [
                        'id' => $l->id,
                        'lot_number' => $l->lot_number,
                        'expiration_date' => $l->expiration_date,
                    ];
                }
                $allocatedLots = $this->calculateLotAllocation($productDetails->id, $baseQuantity, $primaryLotId);
            }
        }

        $skuName = null;
        if ($sku) {
            $sizeName = $sku->size ? $sku->size->name : '';
            $colorName = $sku->color ? $sku->color->name : '';
            $skuName = trim($sizeName . ($sizeName && $colorName ? ' - ' : '') . $colorName);
        }

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = $newQuantity;
            if ($isWholesalePrice) {
                $cart[$cartKey]['sale_price'] = $currentPrice;
                $cart[$cartKey]['is_wholesale'] = true;
                $cart[$cartKey]['wholesale_min_quantity'] = $wholesaleMinApplied;
                $cart[$cartKey]['is_custom_price'] = false;
                $cart[$cartKey]['selected_price_type'] = 'normal';
            } else {
                $hasCustomPrice = isset($cart[$cartKey]['is_custom_price']) && $cart[$cartKey]['is_custom_price'] === true;
                $hasSelectedPrice = isset($cart[$cartKey]['selected_price_type']) && $cart[$cartKey]['selected_price_type'] !== 'normal';
                $wasWholesale = $cart[$cartKey]['is_wholesale'] ?? false;

                if ($hasCustomPrice || $hasSelectedPrice) {
                    $newPriceToSet = $wasWholesale ? $cart[$cartKey]['original_sale_price'] : $cart[$cartKey]['sale_price'];
                } else {
                    $newPriceToSet = $cart[$cartKey]['original_sale_price'];
                }
                $cart[$cartKey]['sale_price'] = $newPriceToSet;
                $cart[$cartKey]['is_wholesale'] = false;
                $cart[$cartKey]['wholesale_min_quantity'] = null;
            }
            $cart[$cartKey]['subtotal'] = $newQuantity * $cart[$cartKey]['sale_price'];
            if ($productDetails->lote == 1) {
                $cart[$cartKey]['lot_info'] = $lotInfo;
                $cart[$cartKey]['available_lots'] = $availableLots;
                $cart[$cartKey]['allocated_lots'] = $allocatedLots;
            }
            $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $newQuantity]);
        } else {
            $cart[$cartKey] = [
                'id' => $productDetails->id,
                'cartKey' => $cartKey,
                'name' => $productDetails->name,
                'code' => $productDetails->code,
                'type' => $productDetails->type,
                'purchase_price' => $purchasePrice,
                'sale_price' => $currentPrice,
                'original_sale_price' => $basePriceToUse,
                'prices' => $additionalPrices,
                'wholesale_min_quantity' => $wholesaleMinApplied,
                'is_wholesale' => $isWholesalePrice,
                'is_custom_price' => false,
                'selected_price_type' => 'normal',
                'quantity' => 1,
                'subtotal' => $currentPrice,
                'lot_info' => $lotInfo,
                'allocated_lots' => $allocatedLots,
                'available_lots' => $availableLots,
                'sku_id' => $sku ? $sku->id : null,
                'sku_name' => $skuName,
                'unit_id' => $unit ? $unit->id : null,
                'unit_name' => $unit ? $unit->name : null,
                'unit_factor' => $unitFactor,
                'employee_id' => null,
                'employee_name' => null,
                'free_qty' => 0,
            ];
        }

        $totalAmount = collect($cart)->sum('subtotal');
        session()->put($cartSessionKey, $cart);
        session()->put($totalSessionKey, ['subtotal' => $totalAmount, 'total' => $totalAmount, 'discount' => 0]);
        $this->applyLoyalty();
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

        $product_id = $cart[$cartKey]['id'];
        $sku_id = $cart[$cartKey]['sku_id'] ?? null;
        $product = Product::find($product_id);

        if (!$product) {
            $this->dispatch('alert', 'Producto no encontrado', 'error');
            if (isset($cart[$cartKey])) {
                $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $cart[$cartKey]['quantity']]);
            }
            return;
        }

        $previousQuantity = $cart[$cartKey]['quantity'] ?? 1;
        $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
        $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

        if ($product->type == 5) {
            $components = ProductComponent::where('product_id', $product_id)->get();
            foreach ($components as $comp) {
                $compProduct = Product::find($comp->component_id);
                if (!$compProduct || $compProduct->type == 1)
                    continue;
                $requiredQtyPerUnit = (float) $comp->quantity;
                if ($compProduct->lote == 1) {
                    $availableStock = Lot::where('product_id', $compProduct->id)->where('branch_id', $this->branch_id)->where('quantity', '>', 0)->sum('quantity');
                } else {
                    $compInvDirect = Inventorie::where('product_id', $compProduct->id)->where('warehouse_id', $warehouseId)->first();
                    $availableStock = $compInvDirect ? (float) $compInvDirect->stock_nolot : 0;
                }
                $alreadyInCart = 0;
                foreach ($cart as $ck => $cartItem) {
                    if ((string) $ck == (string) $cartKey)
                        continue;
                    if ($cartItem['type'] == 5 || $cartItem['type'] == 2) {
                        $otherComponents = ProductComponent::where('product_id', $cartItem['id'])->get();
                        foreach ($otherComponents as $oc) {
                            if ((int) $oc->component_id === (int) $compProduct->id) {
                                $alreadyInCart += (float) $cartItem['quantity'] * (float) $oc->quantity;
                            }
                        }
                    }
                }
                $neededForThisCombo = $quantity * $requiredQtyPerUnit;
                $totalNeeded = $alreadyInCart + $neededForThisCombo;
                if ($totalNeeded > $availableStock) {
                    $this->dispatch('alert', 'STOCK INSUFICIENTE: Falta ' . $compProduct->name . ' (Disponible: ' . (int) $availableStock . ', Necesario: ' . (int) $totalNeeded . ')', 'error');
                    $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $previousQuantity]);
                    return;
                }
            }
        }

        if ($product->type == 0) {
            $unitFactor = $cart[$cartKey]['unit_factor'] ?? 1;
            $baseQuantity = $quantity * $unitFactor;

            if ($sku_id) {
                $sku = ProductSku::find($sku_id);
                if ($sku && $baseQuantity > $sku->stock) {
                    $this->dispatch('alert', 'NO HAY SUFICIENTE STOCK PARA ESTA TALLA/COLOR', 'error');
                    $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $previousQuantity]);
                    return;
                }
            }

            $inventory = Inventorie::where('product_id', $product_id)->where('warehouse_id', $warehouseId)->first();
            if (!$inventory) {
                $this->dispatch('alert', 'Inventario no encontrado', 'error');
                $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $previousQuantity]);
                return;
            }

            if ($product->lote == 1) {
                $validLotsStock = Lot::where('product_id', $product_id)->where('branch_id', $this->branch_id)->where('quantity', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('expiration_date')->orWhere('expiration_date', '>', now()->toDateString());
                    })->sum('quantity');

                if ($baseQuantity > $validLotsStock) {
                    $this->dispatch('alert', 'NO HAY SUFICIENTE STOCK VIGENTE DE LOTE DISPONIBLE', 'error');
                    $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $previousQuantity]);
                    return;
                }
                $primaryLotId = $cart[$cartKey]['lot_info']['lot_id'] ?? null;
                $cart[$cartKey]['allocated_lots'] = $this->calculateLotAllocation($product->id, $baseQuantity, $primaryLotId);
            } else {
                if ($baseQuantity > $inventory->stock_nolot) {
                    $this->dispatch('alert', 'NO HAY SUFICIENTE STOCK DISPONIBLE', 'error');
                    $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $previousQuantity]);
                    return;
                }
            }
        }

        $totalSessionKey = 'sales_total_' . $this->branch_id;

        if (isset($cart[$cartKey])) {
            $shouldUseWholesale = false;
            $newPrice = $cart[$cartKey]['original_sale_price'];
            $wholesaleMinApplied = null;
            $wasWholesale = $cart[$cartKey]['is_wholesale'] ?? false;

            if (isset($cart[$cartKey]['prices'])) {
                foreach ($cart[$cartKey]['prices'] as $ap) {
                    if ($ap['type'] === 'wholesale') {
                        $min = $ap['min_quantity'];
                        $max = $ap['max_quantity'];
                        if ($min && $quantity >= $min) {
                            if (!$max || $quantity <= $max) {
                                $shouldUseWholesale = true;
                                $newPrice = $ap['price'];
                                $wholesaleMinApplied = $min;
                                $cart[$cartKey]['is_custom_price'] = false;
                                $cart[$cartKey]['selected_price_type'] = 'normal';
                                break;
                            }
                        }
                    }
                }
            }

            if (!$shouldUseWholesale) {
                $hasCustomPrice = isset($cart[$cartKey]['is_custom_price']) && $cart[$cartKey]['is_custom_price'] === true;
                $hasSelectedPrice = isset($cart[$cartKey]['selected_price_type']) && $cart[$cartKey]['selected_price_type'] !== 'normal';
                if ($hasCustomPrice || $hasSelectedPrice) {
                    $newPrice = $wasWholesale ? $cart[$cartKey]['original_sale_price'] : $cart[$cartKey]['sale_price'];
                } else {
                    $newPrice = $cart[$cartKey]['original_sale_price'];
                }
            }

            $cart[$cartKey]['quantity'] = $quantity;
            $cart[$cartKey]['sale_price'] = $newPrice;
            $cart[$cartKey]['is_wholesale'] = $shouldUseWholesale;
            $cart[$cartKey]['wholesale_min_quantity'] = $wholesaleMinApplied;
            $cart[$cartKey]['subtotal'] = $quantity * $newPrice;

            if ($shouldUseWholesale && !$wasWholesale) {
                $this->dispatch('alert', 'PRECIO POR MAYOR APLICADO AUTOMÁTICAMENTE', 'success');
            }
        }

        session()->put($cartSessionKey, $cart);
        $subtotalGeneral = 0;
        foreach ($cart as $item) {
            $subtotalGeneral += $item['subtotal'];
        }

        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = isset($totalData['discount']) ? $totalData['discount'] : 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;

        $totalData = [
            'subtotal' => $subtotalGeneral,
            'total' => $nuevoTotal,
            'discount' => $descuentoPorcentaje,
        ];
        session()->put($totalSessionKey, $totalData);
        $this->applyLoyalty();

        $this->dispatch('update-qty-input', ['productId' => $cartKey, 'qty' => $quantity]);
        $this->dispatch('alert', 'CANTIDAD ACTUALIZADA', 'success');
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
                $this->dispatch('update-discount-input', ['discount' => isset($total['discount']) ? $total['discount'] : 0]);
                return;
            }
            $discountAmount = $discount;
            $newTotal = $subtotal - $discountAmount;
            $total['discount'] = $discount;
            $total['total'] = $newTotal;
            $this->discount = $discount;
            $this->total_cart = $newTotal;
        }

        session()->put($totalSessionKey, $total);
        $this->dispatch('alert', 'DESCUENTO ACTUALIZADO', 'success');
    }

    public function removeItem($cartKey)
    {
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;

        $cart = session()->get($cartSessionKey, []);
        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
        }
        session()->put($cartSessionKey, $cart);

        $subtotalGeneral = 0;
        foreach ($cart as $item) {
            $subtotalGeneral += $item['subtotal'];
        }

        $totalData = session()->get($totalSessionKey, []);
        $descuentoPorcentaje = isset($totalData['discount']) ? $totalData['discount'] : 0;
        $nuevoTotal = $subtotalGeneral - $descuentoPorcentaje;

        $totalData = [
            'subtotal' => $subtotalGeneral,
            'total' => $nuevoTotal,
            'discount' => $descuentoPorcentaje,
        ];
        session()->put($totalSessionKey, $totalData);
        $this->applyLoyalty();

        $this->dispatch('alert', 'PRODUCTO ELIMINADO DEL CARRITO', 'success');
        $this->dispatch('focusSearchInput');
    }

    public function confirPayment()
    {
        $branch = Branche::find($this->branch_id);
        $requires_cashbox = $branch ? $branch->requires_cashbox : 1;

        if ($requires_cashbox == 1) {
            $cashBox = $this->getOpenCashBox();
            if (!$cashBox || !isset($cashBox->id)) {
                $this->dispatch('alert', 'POR FAVOR, ABRA UNA CAJA PARA CONTINUAR.', 'error');
                return;
            }
        }

        if (count($this->cart) == 0) {
            $this->dispatch('alert', 'CARRITO VACIO!!!', 'error');
            return;
        }

        if (empty($this->customers_id)) {
            $this->dispatch('alert', 'SELECCIONA UN CLIENTE', 'error');
            return;
        }

        foreach ($this->cart as $item) {
            if (isset($item['lot_info']) && $item['lot_info']) {
                if (!empty($item['lot_info']['expiration_date']) && $item['lot_info']['is_expired']) {
                    $this->dispatch('alert', 'PRODUCTOS VENCIDOS EN EL CARRITO', 'error');
                    return;
                }
            }
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

    private function updateSale()
    {
        DB::beginTransaction();

        try {
            $sale = Sale::findOrFail($this->sale_id);
            $originalCreatedAt = $this->sale_date . ' ' . $sale->created_at->format('H:i:s');

            $oldDetails = SaleDetail::with(['product', 'detailLots.lot', 'detailSkus.productSku'])->where('sale_id', $sale->id)->get();

            $defaultWarehouse = Warehouse::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

            foreach ($oldDetails as $oldDetail) {
                if ($oldDetail->product && $oldDetail->product->type == 0) {
                    $uFactor = 1;
                    if ($oldDetail->unit_id) {
                        $u = Unit::find($oldDetail->unit_id);
                        if ($u)
                            $uFactor = $u->factor > 0 ? $u->factor : 1;
                    }
                    $stockToRestore = $oldDetail->quantity * $uFactor;
                    $pDetails = PurchaseDetail::where('product_id', $oldDetail->product_id)
                        ->where('warehouse_id', $oldDetail->warehouse_id)
                        ->orderBy('created_at', 'desc')
                        ->lockForUpdate()
                        ->get();
                    foreach ($pDetails as $pd) {
                        if ($stockToRestore <= 0)
                            break;
                        $puFactor = 1;
                        if ($pd->unit_id) {
                            $pu = Unit::find($pd->unit_id);
                            if ($pu)
                                $puFactor = $pu->factor > 0 ? $pu->factor : 1;
                        }
                        $maxCapacity = ($pd->quantity + $pd->bonus) * $puFactor;
                        $availableSpace = $maxCapacity - $pd->remaining_quantity;
                        if ($availableSpace > 0) {
                            $restore = min($availableSpace, $stockToRestore);
                            $pd->remaining_quantity += $restore;
                            $pd->save();
                            $stockToRestore -= $restore;
                        }
                    }
                }
            }

            foreach ($oldDetails as $detail) {
                $product = $detail->product;
                if (!$product)
                    continue;

                if ($product->type == 0) {
                    $uFactor = 1;
                    if ($detail->unit_id) {
                        $u = Unit::find($detail->unit_id);
                        if ($u)
                            $uFactor = $u->factor > 0 ? $u->factor : 1;
                    }
                    $stockToRestore = $detail->quantity * $uFactor;

                    $detailSkus = DetailSku::where('detail_type', SaleDetail::class)->where('detail_id', $detail->id)->get();
                    foreach ($detailSkus as $ds) {
                        $skuRecord = ProductSku::find($ds->sku_id);
                        if ($skuRecord) {
                            $skuRecord->stock += $ds->quantity;
                            $skuRecord->save();
                        }
                    }

                    $lotId = null;
                    if ($product->lote == 1) {
                        if ($detail->detailLots->count() > 0) {
                            foreach ($detail->detailLots as $dl) {
                                $lot = $dl->lot;
                                if ($lot) {
                                    $lot->quantity += $dl->quantity;
                                    $lot->save();
                                    $lotId = $lot->id;
                                }
                            }
                        } else {
                            $lot = Lot::where('product_id', $product->id)->where('branch_id', $this->branch_id)->orderBy('created_at', 'desc')->first();
                            if ($lot) {
                                $lot->quantity += $stockToRestore;
                                $lot->save();
                                $lotId = $lot->id;
                            }
                        }
                    }

                    $inventory = Inventorie::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
                    if ($inventory) {
                        if ($product->lote == 1) {
                            $inventory->stock_lot += $stockToRestore;
                        } else {
                            $inventory->stock_nolot += $stockToRestore;
                        }
                        $inventory->save();
                    }

                    $lastKardex = Kardex::where('product_id', $product->id)->where('warehouse_id', $warehouseId)->orderBy('created_at', 'desc')->first();
                    $bal = $lastKardex ? $lastKardex->balance : 0;
                    $baseUnitCost = $uFactor > 0 ? round($detail->purchase_price / $uFactor, 2) : $detail->purchase_price;

                    Kardex::create([
                        'type' => 'AJUSTE',
                        'description' => 'MODIFICACIÓN VENTA (REVERSIÓN) - ' . $sale->sale_number,
                        'quantity_in' => $stockToRestore,
                        'quantity_out' => 0,
                        'balance' => $bal + $stockToRestore,
                        'price' => $baseUnitCost,
                        'total' => $stockToRestore * $baseUnitCost,
                        'product_id' => $product->id,
                        'lot_id' => $lotId,
                        'user_id' => auth()->id(),
                        'warehouse_id' => $warehouseId,
                        'transaction_type' => 'sales',
                        'transaction_id' => $sale->id,
                        'status' => 1,
                    ]);
                }
            }

            SaleDetail::where('sale_id', $sale->id)->delete();
            DetailSku::where('detail_type', SaleDetail::class)->whereIn('detail_id', $oldDetails->pluck('id'))->delete();
            DetailLot::where('detail_type', SaleDetail::class)->whereIn('detail_id', $oldDetails->pluck('id'))->delete();

            $sale->update([
                'total' => $this->total_cart,
                'discount' => $this->discount,
                'observations' => $this->observations ?: null,
                'customer_id' => $this->customers_id,
                'created_at' => $originalCreatedAt,
                'updated_at' => now(),
            ]);

            foreach ($this->cart as $item) {
                $priceType = 'normal';
                $wholesaleMinApplied = null;

                if (isset($item['is_wholesale']) && $item['is_wholesale'] === true) {
                    $priceType = 'wholesale';
                    $wholesaleMinApplied = $item['wholesale_min_quantity'] ?? null;
                } elseif (isset($item['is_custom_price']) && $item['is_custom_price'] === true) {
                    $priceType = 'custom';
                } elseif (isset($item['selected_price_type'])) {
                    $priceType = $item['selected_price_type'];
                }

                $product = Product::find($item['id']);
                $calculatedPurchasePrice = $item['purchase_price'];

                if ($product && $product->type == 0) {
                    $unitFactor = isset($item['unit_factor']) && $item['unit_factor'] > 0 ? $item['unit_factor'] : 1;
                    if (empty($item['unit_factor']) && !empty($item['unit_id'])) {
                        $u = Unit::find($item['unit_id']);
                        if ($u)
                            $unitFactor = $u->factor > 0 ? $u->factor : 1;
                    }
                    $stockToDeduct = $item['quantity'] * $unitFactor;

                    $pDetails = PurchaseDetail::where('product_id', $item['id'])
                        ->where('warehouse_id', $warehouseId)
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $qtyRestante = $stockToDeduct;
                    foreach ($pDetails as $pd) {
                        if ($qtyRestante <= 0)
                            break;
                        $take = min($pd->remaining_quantity, $qtyRestante);
                        $pd->remaining_quantity -= $take;
                        $pd->save();
                        $qtyRestante -= $take;
                    }

                    $inv = Inventorie::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->first();
                    $baseUnitCost = $inv ? $inv->purchase_price : ($item['purchase_price'] / $unitFactor);
                    $calculatedPurchasePrice = $baseUnitCost * $unitFactor;
                }

                $detail = SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'warehouse_id' => $warehouseId,
                    'employee_id' => $item['employee_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $calculatedPurchasePrice,
                    'sale_price' => $item['sale_price'],
                    'price_type' => $priceType,
                    'wholesale_min_quantity' => $wholesaleMinApplied,
                    'subtotal' => $item['subtotal'],
                    'unit_id' => !empty($item['unit_id']) ? $item['unit_id'] : null,
                    'observations' => null,
                    'created_at' => $originalCreatedAt,
                    'updated_at' => now(),
                ]);

                if (isset($item['sku_id']) && $item['sku_id']) {
                    DetailSku::create([
                        'detail_type' => SaleDetail::class,
                        'detail_id' => $detail->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $stockToDeduct ?? $item['quantity'],
                    ]);
                    ProductSku::where('id', $item['sku_id'])->decrement('stock', $stockToDeduct ?? $item['quantity']);
                }

                if ($product && $product->type == 0) {
                    if ($product->lote == 1 && isset($item['allocated_lots'])) {
                        foreach ($item['allocated_lots'] as $alloc) {
                            $availableLot = Lot::where('id', $alloc['id'])->lockForUpdate()->first();
                            if ($availableLot) {
                                $availableLot->quantity -= $alloc['quantity'];
                                $availableLot->save();
                                DetailLot::create([
                                    'detail_type' => SaleDetail::class,
                                    'detail_id' => $detail->id,
                                    'lot_id' => $alloc['id'],
                                    'quantity' => $alloc['quantity']
                                ]);
                            }
                        }
                    }

                    $inventory = Inventorie::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
                    if ($inventory) {
                        if ($product->lote == 1) {
                            $inventory->stock_lot -= $stockToDeduct;
                        } else {
                            $inventory->stock_nolot -= $stockToDeduct;
                        }
                        $inventory->save();
                    } else {
                        Inventorie::create([
                            'stock_lot' => ($product->lote == 1) ? -$stockToDeduct : 0,
                            'stock_nolot' => ($product->lote == 0) ? -$stockToDeduct : 0,
                            'product_id' => $item['id'],
                            'warehouse_id' => $warehouseId,
                        ]);
                    }

                    $lotIdForKardex = null;
                    if ($product->lote == 1 && isset($item['allocated_lots']) && count($item['allocated_lots']) > 0) {
                        $lotIdForKardex = $item['allocated_lots'][0]['id'];
                    }

                    $lastKardex = Kardex::where('product_id', $item['id'])
                        ->where('warehouse_id', $warehouseId)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $previousBalance = $lastKardex ? $lastKardex->balance : 0;
                    $newBalance = $previousBalance - $stockToDeduct;
                    $baseCostForKardex = $calculatedPurchasePrice / $unitFactor;

                    Kardex::create([
                        'type' => 'AJUSTE',
                        'description' => 'MODIFICACIÓN VENTA (NUEVO) - ' . $sale->sale_number,
                        'quantity_in' => 0,
                        'quantity_out' => $stockToDeduct,
                        'balance' => $newBalance,
                        'price' => $baseCostForKardex,
                        'total' => $stockToDeduct * $baseCostForKardex,
                        'product_id' => $item['id'],
                        'lot_id' => $lotIdForKardex,
                        'user_id' => auth()->id(),
                        'warehouse_id' => $warehouseId,
                        'transaction_type' => 'sales',
                        'transaction_id' => $sale->id,
                        'status' => 1,
                    ]);
                } elseif ($product && $product->type == 5) {
                    $ingredients = ProductComponent::where('product_id', $product->id)->get();
                    foreach ($ingredients as $ingRel) {
                        $ingProduct = Product::find($ingRel->component_id);
                        if (!$ingProduct || $ingProduct->type == 1)
                            continue;
                        $qtyToDeduct = $item['quantity'] * $ingRel->quantity;

                        if ($ingProduct->lote == 1) {
                            $lots = Lot::where('product_id', $ingProduct->id)->where('branch_id', $this->branch_id)->orderBy('expiration_date', 'desc')->get();
                            $remainingQuantity = $qtyToDeduct;
                            foreach ($lots as $lot) {
                                if ($lot->quantity >= $remainingQuantity) {
                                    $lot->quantity -= $remainingQuantity;
                                    $lot->save();
                                    break;
                                } else {
                                    $remainingQuantity -= $lot->quantity;
                                    $lot->quantity = 0;
                                    $lot->save();
                                }
                            }
                        }

                        $ingInventory = Inventorie::where('product_id', $ingProduct->id)->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
                        if ($ingInventory) {
                            if ($ingProduct->lote == 1) {
                                $ingInventory->stock_lot -= $qtyToDeduct;
                            } else {
                                $ingInventory->stock_nolot -= $qtyToDeduct;
                            }
                            $ingInventory->save();
                        } else {
                            Inventorie::create([
                                'stock_lot' => ($ingProduct->lote == 1) ? -$qtyToDeduct : 0,
                                'stock_nolot' => ($ingProduct->lote == 0) ? -$qtyToDeduct : 0,
                                'product_id' => $ingProduct->id,
                                'warehouse_id' => $warehouseId,
                            ]);
                        }

                        $ingPurchasePrice = $ingInventory ? $ingInventory->purchase_price : 0;
                        $lastKardex = Kardex::where('product_id', $ingProduct->id)->where('warehouse_id', $warehouseId)->orderBy('created_at', 'desc')->first();
                        $previousBalance = $lastKardex ? $lastKardex->balance : 0;
                        $newBalance = $previousBalance - $qtyToDeduct;
                        $ingLotId = null;
                        if ($ingProduct->lote == 1) {
                            $latestLot = Lot::where('product_id', $ingProduct->id)->where('branch_id', $this->branch_id)->orderBy('expiration_date', 'desc')->first();
                            $ingLotId = $latestLot ? $latestLot->id : null;
                        }

                        Kardex::create([
                            'type' => 'SALIDA',
                            'description' => 'COMBO VENTA MOD - ' . $sale->sale_number,
                            'quantity_in' => 0,
                            'quantity_out' => $qtyToDeduct,
                            'balance' => $newBalance,
                            'price' => $ingPurchasePrice,
                            'total' => $qtyToDeduct * $ingPurchasePrice,
                            'product_id' => $ingProduct->id,
                            'lot_id' => $ingLotId,
                            'user_id' => auth()->id(),
                            'branch_id' => $this->branch_id,
                            'warehouse_id' => $warehouseId,
                            'transaction_type' => 'sales',
                            'transaction_id' => $sale->id,
                            'status' => 1,
                        ]);
                    }
                }
            }

            Payment::where('transaction_type', 'sales')->where('transaction_id', $sale->id)->delete();
            Movement::where('transaction_type', 'sales')->where('transaction_id', $sale->id)->delete();
            Credit::where('creditable_type', 'sales')->where('creditable_id', $sale->id)->delete();

            $payment_id = null;
            if ($this->selectedPayment === 'MULTIPLE') {
                if ($this->efectivo > 0) {
                    $p = Payment::create(['description' => 'EFECTIVO', 'amount' => $this->efectivo, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    $payment_id = $p->id;
                }
                if ($this->tarjeta > 0) {
                    $p = Payment::create(['description' => 'TARJETA', 'amount' => $this->tarjeta, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    if (!$payment_id)
                        $payment_id = $p->id;
                }
                if ($this->qr > 0) {
                    $p = Payment::create(['description' => 'QR', 'amount' => $this->qr, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    if (!$payment_id)
                        $payment_id = $p->id;
                }
            } else {
                $payment = Payment::create(['description' => $this->selectedPayment, 'amount' => $this->total_cart, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                $payment_id = $payment->id;
            }

            if ($this->selectedPayment === 'CREDITO') {
                Credit::create([
                    'amount' => $this->total_cart,
                    'creditable_type' => 'sales',
                    'creditable_id' => $sale->id,
                    'due_date' => $this->due_date,
                    'user_id' => auth()->id(),
                    'branch_id' => $this->branch_id,
                ]);
            }

            $branch = Branche::find($this->branch_id);
            $requires_cashbox = $branch ? $branch->requires_cashbox : 1;

            if ($requires_cashbox == 1) {
                $cashBox = $this->getOpenCashBox();
                if ($cashBox && isset($cashBox->id)) {
                    Movement::create([
                        'type' => 'INGRESO',
                        'type_movements' => 'VENTA',
                        'description' => 'MODIFICACIÓN VENTA - ' . $sale->sale_number,
                        'transaction_id' => $sale->id,
                        'transaction_type' => 'sales',
                        'payment_id' => $payment_id,
                        'branch_id' => $this->branch_id,
                        'amount' => $this->total_cart,
                        'cash_box_id' => $cashBox->id,
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            $this->clearSales();
            $this->resetInputConfirmSale();

            $id = Crypt::encrypt($sale->id);
            $defaultPrinter = Printer::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $printBehavior = $defaultPrinter ? $defaultPrinter->print_behavior : 'pdf';
            $printerType = $defaultPrinter ? $defaultPrinter->type : 'ticket';

            $pdfUrl = url(route('printSalePdf', [
                'id' => $id,
                'branch_id' => $this->branch_id
            ])) . '?type=' . $printerType;

            $this->dispatch('processPrintBehavior', url: $pdfUrl, behavior: $printBehavior, message: 'VENTA ACTUALIZADA CON ÉXITO');
            return redirect()->route('sales_lists');

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al actualizar: ' . $e->getMessage(), 'error');
        }
    }

    public function confirmSale()
    {
        $rules = [
            'due_date' => 'required_if:selectedPayment,CREDITO',
            'sale_date' => 'required|date',
        ];

        $messages = [
            'due_date.required_if' => 'La fecha límite de pago es obligatoria.',
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
            $requires_cashbox = $branch ? $branch->requires_cashbox : 1;

            $cashBox = null;
            if ($requires_cashbox == 1) {
                $cashBox = $this->getOpenCashBox();
                if (!$cashBox || !isset($cashBox->id)) {
                    $this->dispatch('alert', 'POR FAVOR, ABRA UNA CAJA PARA CONTINUAR.', 'error');
                    DB::rollBack();
                    return;
                }
            }

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
                'customer_id' => $this->customers_id,
                'cash_box_id' => $cashBox ? $cashBox->id : null,
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
                $priceType = 'normal';
                $wholesaleMinApplied = null;

                if (isset($item['is_wholesale']) && $item['is_wholesale'] === true) {
                    $priceType = 'wholesale';
                    $wholesaleMinApplied = $item['wholesale_min_quantity'] ?? null;
                } elseif (isset($item['is_custom_price']) && $item['is_custom_price'] === true) {
                    $priceType = 'custom';
                } elseif (isset($item['selected_price_type'])) {
                    $priceType = $item['selected_price_type'];
                }

                $product = Product::find($item['id']);
                $calculatedPurchasePrice = $item['purchase_price'];

                if ($product && $product->type == 0) {
                    $unitFactor = isset($item['unit_factor']) && $item['unit_factor'] > 0 ? $item['unit_factor'] : 1;
                    if (empty($item['unit_factor']) && !empty($item['unit_id'])) {
                        $u = Unit::find($item['unit_id']);
                        if ($u)
                            $unitFactor = $u->factor > 0 ? $u->factor : 1;
                    }
                    $stockToDeduct = $item['quantity'] * $unitFactor;

                    $pDetails = PurchaseDetail::where('product_id', $item['id'])
                        ->where('warehouse_id', $warehouseId)
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $qtyRestante = $stockToDeduct;
                    foreach ($pDetails as $pd) {
                        if ($qtyRestante <= 0)
                            break;
                        $take = min($pd->remaining_quantity, $qtyRestante);
                        $pd->remaining_quantity -= $take;
                        $pd->save();
                        $qtyRestante -= $take;
                    }

                    $inv = Inventorie::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->first();
                    $baseUnitCost = $inv ? $inv->purchase_price : ($item['purchase_price'] / $unitFactor);
                    $calculatedPurchasePrice = $baseUnitCost * $unitFactor;
                }

                $detail = SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'warehouse_id' => $warehouseId,
                    'employee_id' => $item['employee_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $calculatedPurchasePrice,
                    'sale_price' => $item['sale_price'],
                    'price_type' => $priceType,
                    'wholesale_min_quantity' => $wholesaleMinApplied,
                    'subtotal' => $item['subtotal'],
                    'unit_id' => !empty($item['unit_id']) ? $item['unit_id'] : null,
                    'observations' => null,
                    'created_at' => $saleDateTime,
                    'updated_at' => now(),
                ]);

                if (isset($item['sku_id']) && $item['sku_id']) {
                    DetailSku::create([
                        'detail_type' => SaleDetail::class,
                        'detail_id' => $detail->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $stockToDeduct ?? $item['quantity'],
                    ]);
                    ProductSku::where('id', $item['sku_id'])->decrement('stock', $stockToDeduct ?? $item['quantity']);
                }

                if ($product && $product->type == 0) {
                    if ($product->lote == 1 && isset($item['allocated_lots'])) {
                        foreach ($item['allocated_lots'] as $alloc) {
                            $availableLot = Lot::where('id', $alloc['id'])->lockForUpdate()->first();
                            if ($availableLot) {
                                $availableLot->quantity -= $alloc['quantity'];
                                $availableLot->save();
                                DetailLot::create([
                                    'detail_type' => SaleDetail::class,
                                    'detail_id' => $detail->id,
                                    'lot_id' => $alloc['id'],
                                    'quantity' => $alloc['quantity']
                                ]);
                            }
                        }
                    }

                    $inventory = Inventorie::where('product_id', $item['id'])->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
                    if ($inventory) {
                        if ($product->lote == 1) {
                            $inventory->stock_lot -= $stockToDeduct;
                        } else {
                            $inventory->stock_nolot -= $stockToDeduct;
                        }
                        $inventory->save();
                    } else {
                        Inventorie::create([
                            'stock_lot' => ($product->lote == 1) ? -$stockToDeduct : 0,
                            'stock_nolot' => ($product->lote == 0) ? -$stockToDeduct : 0,
                            'product_id' => $item['id'],
                            'warehouse_id' => $warehouseId,
                        ]);
                    }

                    $lotIdForKardex = null;
                    if ($product->lote == 1 && isset($item['allocated_lots']) && count($item['allocated_lots']) > 0) {
                        $lotIdForKardex = $item['allocated_lots'][0]['id'];
                    }

                    $lastKardex = Kardex::where('product_id', $item['id'])
                        ->where('warehouse_id', $warehouseId)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $previousBalance = $lastKardex ? $lastKardex->balance : 0;
                    $newBalance = $previousBalance - $stockToDeduct;
                    $baseCostForKardex = $calculatedPurchasePrice / $unitFactor;

                    Kardex::create([
                        'type' => 'SALIDA',
                        'description' => 'NOTA DE VENTA - ' . $sale->sale_number,
                        'quantity_in' => 0,
                        'quantity_out' => $stockToDeduct,
                        'balance' => $newBalance,
                        'price' => $baseCostForKardex,
                        'total' => $stockToDeduct * $baseCostForKardex,
                        'product_id' => $item['id'],
                        'lot_id' => $lotIdForKardex,
                        'user_id' => auth()->id(),
                        'branch_id' => $this->branch_id,
                        'warehouse_id' => $warehouseId,
                        'transaction_type' => 'sales',
                        'transaction_id' => $sale->id,
                        'status' => 1,
                    ]);
                } elseif ($product && $product->type == 5) {
                    $ingredients = ProductComponent::where('product_id', $product->id)->get();
                    foreach ($ingredients as $ingRel) {
                        $ingProduct = Product::find($ingRel->component_id);
                        if (!$ingProduct || $ingProduct->type == 1)
                            continue;
                        $qtyToDeduct = $item['quantity'] * $ingRel->quantity;

                        if ($ingProduct->lote == 1) {
                            $lots = Lot::where('product_id', $ingProduct->id)->where('branch_id', $this->branch_id)->orderBy('expiration_date', 'desc')->get();
                            $remainingQuantity = $qtyToDeduct;
                            foreach ($lots as $lot) {
                                if ($lot->quantity >= $remainingQuantity) {
                                    $lot->quantity -= $remainingQuantity;
                                    $lot->save();
                                    break;
                                } else {
                                    $remainingQuantity -= $lot->quantity;
                                    $lot->quantity = 0;
                                    $lot->save();
                                }
                            }
                        }

                        $ingInventory = Inventorie::where('product_id', $ingProduct->id)->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
                        if ($ingInventory) {
                            if ($ingProduct->lote == 1) {
                                $ingInventory->stock_lot -= $qtyToDeduct;
                            } else {
                                $ingInventory->stock_nolot -= $qtyToDeduct;
                            }
                            $ingInventory->save();
                        } else {
                            Inventorie::create([
                                'stock_lot' => ($ingProduct->lote == 1) ? -$qtyToDeduct : 0,
                                'stock_nolot' => ($ingProduct->lote == 0) ? -$qtyToDeduct : 0,
                                'product_id' => $ingProduct->id,
                                'warehouse_id' => $warehouseId,
                            ]);
                        }

                        $ingPurchasePrice = $ingInventory ? $ingInventory->purchase_price : 0;
                        $lastKardex = Kardex::where('product_id', $ingProduct->id)->where('warehouse_id', $warehouseId)->orderBy('created_at', 'desc')->first();
                        $previousBalance = $lastKardex ? $lastKardex->balance : 0;
                        $newBalance = $previousBalance - $qtyToDeduct;
                        $ingLotId = null;
                        if ($ingProduct->lote == 1) {
                            $latestLot = Lot::where('product_id', $ingProduct->id)->where('branch_id', $this->branch_id)->orderBy('expiration_date', 'desc')->first();
                            $ingLotId = $latestLot ? $latestLot->id : null;
                        }

                        Kardex::create([
                            'type' => 'SALIDA',
                            'description' => 'COMBO VENTA - ' . $sale->sale_number,
                            'quantity_in' => 0,
                            'quantity_out' => $qtyToDeduct,
                            'balance' => $newBalance,
                            'price' => $ingPurchasePrice,
                            'total' => $qtyToDeduct * $ingPurchasePrice,
                            'product_id' => $ingProduct->id,
                            'lot_id' => $ingLotId,
                            'user_id' => auth()->id(),
                            'branch_id' => $this->branch_id,
                            'warehouse_id' => $warehouseId,
                            'transaction_type' => 'sales',
                            'transaction_id' => $sale->id,
                            'status' => 1,
                        ]);
                    }
                }
            }

            $payment_id = null;
            if ($this->selectedPayment === 'MULTIPLE') {
                if ($this->efectivo > 0) {
                    $payment = Payment::create(['description' => 'EFECTIVO', 'amount' => $this->efectivo, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    $payment_id = $payment->id;
                }
                if ($this->tarjeta > 0) {
                    $payment = Payment::create(['description' => 'TARJETA', 'amount' => $this->tarjeta, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    if (!$payment_id)
                        $payment_id = $payment->id;
                }
                if ($this->qr > 0) {
                    $payment = Payment::create(['description' => 'QR', 'amount' => $this->qr, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                    if (!$payment_id)
                        $payment_id = $payment->id;
                }
            } else {
                $payment = Payment::create(['description' => $this->selectedPayment, 'amount' => $this->total_cart, 'transaction_type' => 'sales', 'transaction_id' => $sale->id]);
                $payment_id = $payment->id;
            }

            if ($this->selectedPayment === 'CREDITO') {
                Credit::create([
                    'amount' => $this->total_cart,
                    'creditable_type' => 'sales',
                    'creditable_id' => $sale->id,
                    'due_date' => $this->due_date,
                    'user_id' => auth()->id(),
                    'branch_id' => $this->branch_id,
                ]);
            }

            if ($cashBox && isset($cashBox->id)) {
                Movement::create([
                    'type' => 'INGRESO',
                    'type_movements' => 'VENTA',
                    'description' => 'NOTA DE VENTA - ' . $sale->sale_number,
                    'transaction_id' => $sale->id,
                    'transaction_type' => 'sales',
                    'payment_id' => $payment_id,
                    'branch_id' => $this->branch_id,
                    'amount' => $this->total_cart,
                    'cash_box_id' => $cashBox->id,
                    'user_id' => auth()->id(),
                ]);
            }

            DB::commit();

            $this->clearSales();
            $this->sale_date = now()->format('Y-m-d');
            $this->resetInputConfirmSale();

            $id = Crypt::encrypt($sale->id);
            $defaultPrinter = Printer::where('branch_id', $this->branch_id)->where('is_default', 1)->first();
            $printBehavior = $defaultPrinter ? $defaultPrinter->print_behavior : 'pdf';
            $printerType = $defaultPrinter ? $defaultPrinter->type : 'ticket';

            $pdfUrl = url(route('printSalePdf', [
                'id' => $id,
                'branch_id' => $this->branch_id
            ])) . '?type=' . $printerType;

            $this->dispatch('processPrintBehavior', url: $pdfUrl, behavior: $printBehavior, message: 'VENTA REALIZADA CON ÉXITO');
            $this->dispatch('focusSearchInput');

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al crear la venta: ' . $e->getMessage(), 'error');
        }
    }

    public function clearSales()
    {
        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $totalSessionKey = 'sales_total_' . $this->branch_id;

        session()->forget($cartSessionKey);
        session()->forget($totalSessionKey);
        session()->forget('is_editing_' . $this->branch_id);

        $this->listSkus = [];
        $this->selectedSku = null;
        $this->listProductUnits = [];
        $this->selectedProductUnit = null;

        $this->applyLoyalty();

        if ($this->is_editing) {
            return redirect()->route('sales_lists');
        }
    }

    public function resetInputConfirmSale()
    {
        $this->resetValidation();
        $this->resetPayment();
        $this->setDefaultCustomer();
        $this->applyLoyalty();
        $this->discount = 0;
        $this->total_cart = 0;
        $this->subtotal = 0;
        $this->searchTerm = '';
        $this->observations = '';
        $this->listSkus = [];
        $this->selectedSku = null;
        $this->listProductUnits = [];
        $this->selectedProductUnit = null;
    }

    public function resetInputFields()
    {
        $this->search = '';
        $this->products = [];
    }

    public function calculateSalePrice()
    {
        if (is_numeric($this->purchase_price) && is_numeric($this->profit)) {
            $this->sale_price = round($this->purchase_price * (1 + ($this->profit / 100)), 2);
        } else {
            $this->sale_price = null;
        }
    }

    public function calculatePurchasePrice()
    {
        if (is_numeric($this->sale_price) && is_numeric($this->profit)) {
            if (!is_numeric($this->purchase_price)) {
                $this->purchase_price = round($this->sale_price / (1 + ($this->profit / 100)), 2);
            } else {
                $this->profit = round((($this->sale_price / $this->purchase_price) - 1) * 100, 2);
            }
        } else {
            $this->purchase_price = null;
        }
    }

    public function editCustomer($id)
    {
        if ($id == 1) {
            $this->dispatch('alert', 'NO SE PUEDE EDITAR EL CLIENTE POR DEFECTO', 'error');
            return;
        }
        $this->resetValidation();
        $customer = Customer::findOrFail($id);
        $this->customer_id = $id;
        $this->name = $customer->name;
        $this->document_type = $customer->document_type;
        $this->document = $customer->document;
        $this->phone = $customer->phone;
        $this->email = $customer->email;
        $this->address = $customer->address;
        $this->isEditMode = true;
    }

    public function storeCustomer()
    {
        $this->email = empty(trim($this->email)) ? null : trim($this->email);
        $this->phone = empty(trim($this->phone)) ? null : trim($this->phone);

        $rules = [
            'name' => 'required|min:3',
            'document_type' => 'required',
            'document' => 'required|numeric|digits_between:7,12|unique:customers,document' . ($this->isEditMode ? ',' . $this->customer_id : ''),
            'phone' => 'nullable|numeric|digits_between:7,8',
            'email' => 'nullable|email|unique:customers,email' . ($this->isEditMode ? ',' . $this->customer_id : ''),
        ];

        $messages = [
            'name.required' => 'El nombre es requerido',
            'name.min' => 'El nombre debe tener al menos 3 caracteres',
            'document.unique' => 'El documento ya está en uso',
            'document_type.required' => 'El tipo de documento es requerido',
            'document.required' => 'El documento es requerido',
            'document.numeric' => 'El documento debe contener solo números.',
            'document.digits_between' => 'El documento debe tener al menos entre 7 y 12 dígitos.',
            'phone.numeric' => 'El teléfono debe contener solo números.',
            'phone.digits_between' => 'El teléfono debe tener entre 7 y 8 dígitos.',
            'email.email' => 'El correo electrónico debe tener un formato válido',
            'email.unique' => 'El correo electrónico ya está en uso',
        ];

        $this->validate($rules, $messages);

        $customersData = [
            'name' => $this->name,
            'document_type' => $this->document_type,
            'document' => $this->document,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
        ];

        if ($this->isEditMode) {
            if ($this->customer_id == 1) {
                $this->dispatch('alert', 'NO SE PUEDE EDITAR EL CLIENTE POR DEFECTO', 'error');
                return;
            }
            $customer = Customer::updateOrCreate(['id' => $this->customer_id], $customersData);
            $message = 'CLIENTE ACTUALIZADO CON ÉXITO.';
        } else {
            $customer = Customer::create($customersData);
            $message = 'CLIENTE CREADO CON ÉXITO.';
        }

        $this->resetInputCustomer();

        $this->customers_id = $customer->id;
        $this->customer_document = $customer->document;
        $this->customer_lastname = $customer->name;
        $this->customerSearchTerm = $customer->name . ' - ' . $customer->document;
        $this->customerResults = Customer::where('id', $customer->id)->get();
        $this->showCustomerDropdown = false;
        $this->applyLoyalty();

        $this->dispatch('customerStore', $message, 'success');
    }

    public function resetInputCustomer()
    {
        $this->resetValidation();
        $this->name = '';
        $this->document_type = '';
        $this->document = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->isEditMode = false;
    }

    public function openEmployeeModal($cartKey)
    {
        $this->selectedCartKeyForEmployee = $cartKey;
        $this->searchEmployee = '';
        $this->updateEmployeeList();

        $cartSessionKey = 'sales_cart_' . $this->branch_id;
        $cart = session()->get($cartSessionKey, []);
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