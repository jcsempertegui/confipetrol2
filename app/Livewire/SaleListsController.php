<?php

namespace App\Livewire;

use App\Models\Branche;
use App\Models\Kardex;
use App\Models\Printer;
use App\Models\Product;
use App\Models\Lot;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Inventorie;
use App\Models\Movement;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\DetailSku;
use App\Models\ProductSku;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleListsController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $name, $sale_id, $sale;
    public $sale_details = [];
    public $sale_number, $total, $discount = 0, $customer, $user;

    public $sale_payments = [];
    public $searchTerm;
    public $filter_status = '1';
    public $filter_payment = '';
    protected $listeners = ['delete'];
    public $branch_id;

    public $fromDate, $toDate;

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterPayment()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filter_status = '1';
        $this->filter_payment = '';
        $this->resetPage();
    }

    public function refreshData($branchId = null)
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $this->resetPage();
    }

    public function mount()
    {
        $this->sale_details = [];
        $this->sale_payments = [];
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function render()
    {
        $sales = $this->SalesByDate();
        $defaultPrinterType = Printer::where('branch_id', $this->branch_id)->where('is_default', 1)->value('type') ?? 'ticket';

        return view('livewire.sales.sale_lists', [
            'sales' => $sales,
            'startCount' => $sales->total() - ($sales->currentPage() - 1) * $sales->perPage(),
            'defaultPrinterType' => $defaultPrinterType
        ])
            ->extends('layouts.theme.app');
    }

    public function SalesByDate()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();

        return Sale::select('sales.*', 'cash_boxes.is_open as cash_box_is_open')
            ->leftJoin('cash_boxes', 'sales.cash_box_id', '=', 'cash_boxes.id')
            ->with([
                'user:id,name,login',
                'branch:id,name,pos_type',
                'customer:id,name',
                'payments' => function ($query) {
                    $query->select('id', 'transaction_id', 'transaction_type', 'description', 'amount')
                        ->where('transaction_type', 'sales');
                }
            ])
            ->where('sales.branch_id', $this->branch_id)
            ->when($this->filter_status !== '', function ($q) {
                $q->where('sales.status', $this->filter_status);
            })
            ->when($this->filter_payment !== '', function ($q) {
                $q->whereHas('payments', function ($query) {
                    $query->where('description', $this->filter_payment);
                });
            })
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('sales.created_at', [$fromDate, $toDate]);
            })
            ->where(function ($query) {
                if (strlen($this->searchTerm) > 0) {
                    $query->where('sales.sale_number', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('customer', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('branch', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('user', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                }
            })
            ->orderBy('sales.id', 'desc')
            ->paginate($this->perPage);
    }

    public function detailSales($sale_id)
    {
        $this->reset(['sale', 'sale_details', 'sale_payments', 'discount']);

        $this->sale = Sale::select('sales.id', 'sales.sale_number', 'sales.total', 'sales.discount', 'users.login as user', 'customers.name', 'sales.user_id', 'sales.customer_id')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.id', $sale_id)
            ->first();

        if ($this->sale) {
            $this->sale_number = $this->sale->sale_number;
            $this->total = $this->sale->total;
            $this->discount = $this->sale->discount ?? 0;
            $this->customer = $this->sale->name;
            $this->user = $this->sale->user;

            $this->sale_payments = Payment::where('transaction_id', $this->sale->id)
                ->where('transaction_type', 'sales')
                ->get();
        }

        $this->sale_details = SaleDetail::with([
            'product' => function ($q) {
                $q->select('id', 'name');
            },
            'unit:id,name',
            'detailLots.lot',
            'detailSkus.productSku.color',
            'detailSkus.productSku.size'
        ])
            ->where('sale_id', $sale_id)
            ->get();
    }

    public function printSalePdf($id, $branch_id)
    {
        $type = request()->query('type', 'ticket');
        $sale_id = Crypt::decrypt($id);
        $settings = Setting::where('branch_id', $branch_id)->first();
        $branch = Branche::find($branch_id);
        $printer = Printer::where('branch_id', $branch_id)->where('status', 1)->first();

        $sale = Sale::select(
            'sales.id',
            'sales.sale_number',
            'sales.created_at',
            'sales.total',
            'sales.discount',
            'sales.observations',
            'users.login as user',
            'customers.name',
            'customers.document'
        )
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.id', $sale_id)
            ->first();

        if (!$sale) {
            abort(404, 'Venta no encontrada.');
        }

        $sale_details = SaleDetail::select(
            'sale_details.id',
            'sale_details.quantity',
            'sale_details.sale_price',
            'sale_details.subtotal',
            'sale_details.product_id',
            'products.name as product_name',
            'products.code as product_code'
        )
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->where('sale_details.sale_id', $sale_id)
            ->with(['detailSkus.productSku.color', 'detailSkus.productSku.size'])
            ->get();

        $payments = Payment::where('transaction_id', $sale_id)
            ->where('transaction_type', 'sales')
            ->get();

        $defaultPrinter = Printer::where('branch_id', $branch_id)->where('is_default', 1)->first();
        $printerType = $defaultPrinter ? $defaultPrinter->type : 'ticket';

        if ($type === 'ticket' || ($type === 'ticket' && $printerType === 'ticket')) {
            $docSettingsNota = \App\Models\DocumentSetting::where('branch_id', $branch_id)->where('document_type', 'nota_venta')->first();

            $altoTotal = 380;
            foreach ($sale_details as $item) {
                $altoTotal += 40;
                if ($item->detailSkus && $item->detailSkus->count() > 0)
                    $altoTotal += 15;
                if (strlen($item->product_name ?? '') > 20)
                    $altoTotal += 20;
            }
            if ($payments->count() > 0) {
                $altoTotal += ($payments->count() * 15);
            }
            if (!empty($sale->observations))
                $altoTotal += 40;

            $pdf = PDF::loadView('rooms.ticketPdf', [
                'sale' => $sale,
                'sale_details' => $sale_details,
                'payments' => $payments,
                'printer' => $printer,
                'settings' => $settings,
                'branchInfo' => $branch,
                'docSettingsNota' => $docSettingsNota
            ])
                ->setOption('dpi', 150)
                ->setOption('defaultFont', 'sans-serif')
                ->setOption('encoding', 'UTF-8')
                ->setOption('margin-top', 0)
                ->setOption('margin-right', 0)
                ->setOption('margin-bottom', 0)
                ->setOption('margin-left', 0)
                ->setPaper([0, 0, 226.77, $altoTotal], 'portrait')
                ->setWarnings(false);

            $fileName = 'nota_venta_' . $sale_id . '_' . date('Y-m-d_H-i-s') . '.pdf';
            return $pdf->stream($fileName);
        } else {
            $pdf = PDF::loadView('rooms.salePdf', [
                'sale' => $sale,
                'sale_details' => $sale_details,
                'payments' => $payments,
                'printer' => $printer,
                'settings' => $settings,
                'branch' => $branch
            ])
                ->setOption('defaultFont', 'sans-serif')
                ->setPaper('letter', 'portrait')
                ->setWarnings(false);

            $fileName = 'recibo_venta_' . $sale_id . '_' . date('Y-m-d_H-i-s') . '.pdf';
            return $pdf->stream($fileName);
        }
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function delete($sale_id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::find($sale_id);

            if (!$sale) {
                $this->dispatch('alert', 'Venta no encontrada', 'error');
                DB::rollBack();
                return;
            }

            $cashBox = DB::table('cash_boxes')->where('id', $sale->cash_box_id)->first();
            if ($cashBox && $cashBox->is_open == 0) {
                $this->dispatch('alert', 'No se puede anular la venta porque la caja ya está cerrada.', 'error');
                DB::rollBack();
                return;
            }

            $sale->update(['status' => 0]);

            $saleDetails = SaleDetail::with(['product', 'detailLots.lot'])
                ->where('sale_id', $sale_id)
                ->get();

            foreach ($saleDetails as $detail) {
                $product = $detail->product;

                if (!$product)
                    continue;

                if ($product->type == 0) {
                    $uFactor = 1;
                    if ($detail->unit_id) {
                        $u = Unit::find($detail->unit_id);
                        if ($u) $uFactor = $u->factor > 0 ? $u->factor : 1;
                    }
                    $stockToRestore = $detail->quantity * $uFactor;

                    $pDetails = PurchaseDetail::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->orderBy('created_at', 'desc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($pDetails as $pd) {
                        if ($stockToRestore <= 0) break;
                        $puFactor = 1;
                        if ($pd->unit_id) {
                            $pu = Unit::find($pd->unit_id);
                            if ($pu) $puFactor = $pu->factor > 0 ? $pu->factor : 1;
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

                    // Se restaura el valor correcto original del $stockToRestore para los siguientes pasos
                    $stockToRestore = $detail->quantity * $uFactor;

                    $detailSkus = DetailSku::where('detail_type', SaleDetail::class)
                        ->where('detail_id', $detail->id)
                        ->get();

                    foreach ($detailSkus as $ds) {
                        $skuRecord = ProductSku::find($ds->sku_id);
                        if ($skuRecord) {
                            $skuRecord->stock += $ds->quantity; // Esto ya incluye el factor de conversión desde la creación
                            $skuRecord->save();
                        }
                    }

                    if ($product->lote == 1) {
                        if ($detail->detailLots->count() > 0) {
                            foreach ($detail->detailLots as $detailLot) {
                                $lot = $detailLot->lot;
                                if ($lot) {
                                    $lot->quantity += $detailLot->quantity;
                                    $lot->save();
                                }
                            }
                        } else {
                            $lots = Lot::where('product_id', $detail->product_id)
                                ->orderBy('expiration_date', 'desc')
                                ->get();

                            $remainingQuantity = $stockToRestore; // CORRECCIÓN: Toma en cuenta el factor
                            foreach ($lots as $lot) {
                                if ($remainingQuantity <= 0)
                                    break;

                                if ($lot->quantity >= $remainingQuantity) {
                                    $lot->quantity += $remainingQuantity;
                                    $lot->save();
                                    $remainingQuantity = 0;
                                } else {
                                    $lot->quantity += $remainingQuantity;
                                    $lot->save();
                                    $remainingQuantity = 0;
                                }
                            }
                        }
                    }

                    $inventory = Inventorie::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->first();

                    if ($inventory) {
                        if ($product->lote == 1) {
                            $inventory->stock_lot += $stockToRestore; // CORRECCIÓN: Toma en cuenta el factor
                        } else {
                            $inventory->stock_nolot += $stockToRestore; // CORRECCIÓN: Toma en cuenta el factor
                        }
                        $inventory->save();
                    } else {
                        Inventorie::create([
                            'stock_lot' => ($product->lote == 1) ? $stockToRestore : 0, // CORRECCIÓN: Toma en cuenta el factor
                            'stock_nolot' => ($product->lote == 0) ? $stockToRestore : 0, // CORRECCIÓN: Toma en cuenta el factor
                            'product_id' => $detail->product_id,
                            'warehouse_id' => $detail->warehouse_id,
                        ]);
                    }

                    $lastKardex = Kardex::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $previousBalance = $lastKardex ? $lastKardex->balance : 0;
                    $newBalance = $previousBalance + $stockToRestore; // CORRECCIÓN: Toma en cuenta el factor

                    $lotId = null;
                    if ($product->lote == 1) {
                        $lotId = $detail->detailLots->first() ? $detail->detailLots->first()->lot_id : null;
                        if (!$lotId) {
                            $firstLot = Lot::where('product_id', $detail->product_id)->first();
                            $lotId = $firstLot ? $firstLot->id : null;
                        }
                    }

                    Kardex::create([
                        'type' => 'ENTRADA',
                        'description' => 'VENTA ANULADA - ' . $sale->sale_number,
                        'quantity_in' => $stockToRestore, // CORRECCIÓN: Toma en cuenta el factor
                        'balance' => $newBalance,
                        'price' => $detail->sale_price,
                        'total' => $detail->subtotal,
                        'product_id' => $detail->product_id,
                        'lot_id' => $lotId,
                        'user_id' => auth()->id(),
                        'warehouse_id' => $detail->warehouse_id,
                        'transaction_type' => 'sales',
                        'transaction_id' => $sale->id,
                        'status' => 1,
                    ]);
                }
            }

            Movement::where('transaction_type', 'sales')
                ->where('transaction_id', $sale->id)
                ->update(['status' => 0]);

            DB::commit();
            $this->dispatch('alert', 'LA VENTA HA SIDO ANULADA', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al anular venta: ' . $e->getMessage(), 'error');
        }
    }
}