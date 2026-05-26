<?php

namespace App\Livewire;

use App\Models\Kardex;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Inventorie;
use App\Models\Movement;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\Worker;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SaleListsController extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $name, $sale_id, $sale;
    public $sale_details = [];
    public $sale_number, $total, $discount = 0, $worker, $user;

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

        return view('livewire.sales.sale_lists', [
            'sales' => $sales,
            'startCount' => $sales->total() - ($sales->currentPage() - 1) * $sales->perPage(),
        ])
            ->extends('layouts.theme.app');
    }

    public function SalesByDate()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();

        return Sale::with([
            'user:id,name,login',
            'branch:id,name,pos_type',
            'worker:id,name,last_name',
            'payments' => function ($query) {
                $query->select('id', 'transaction_id', 'transaction_type', 'description', 'amount')
                    ->where('transaction_type', 'sales');
            }
        ])
            ->where('branch_id', $this->branch_id)
            ->when($this->filter_status !== '', function ($q) {
                $q->where('status', $this->filter_status);
            })
            ->when($this->filter_payment !== '', function ($q) {
                $q->whereHas('payments', function ($query) {
                    $query->where('description', $this->filter_payment);
                });
            })
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })
            ->where(function ($query) {
                if (strlen($this->searchTerm) > 0) {
                    $query->where('sale_number', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('worker', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%')
                              ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('branch', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('user', function ($q) {
                            $q->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    public function detailSales($sale_id)
    {
        $this->reset(['sale', 'sale_details', 'sale_payments', 'discount']);

        $this->sale = Sale::with(['user:id,name,login', 'worker:id,name,last_name'])
            ->where('id', $sale_id)
            ->first();

        if ($this->sale) {
            $this->sale_number = $this->sale->sale_number;
            $this->total = $this->sale->total;
            $this->discount = $this->sale->discount ?? 0;
            $this->worker = $this->sale->worker
                ? ($this->sale->worker->name . ' ' . $this->sale->worker->last_name)
                : 'S/N';
            $this->user = $this->sale->user->login ?? $this->sale->user->name ?? 'S/N';

            $this->sale_payments = Payment::where('transaction_id', $this->sale->id)
                ->where('transaction_type', 'sales')
                ->get();
        }

        $this->sale_details = SaleDetail::with([
            'product:id,name',
            'unit:id,name',
        ])
            ->where('sale_id', $sale_id)
            ->get();
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

            $sale->update(['status' => 0]);

            $saleDetails = SaleDetail::with(['product'])
                ->where('sale_id', $sale_id)
                ->get();

            foreach ($saleDetails as $detail) {
                $product = $detail->product;

                if (!$product) continue;

                if ($product->type == 0) {
                    $uFactor = 1;
                    if ($detail->unit_id) {
                        $u = Unit::find($detail->unit_id);
                        if ($u) $uFactor = $u->factor > 0 ? $u->factor : 1;
                    }
                    $stockToRestore = $detail->quantity * $uFactor;

                    $inventory = Inventorie::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->first();

                    if ($inventory) {
                        $inventory->stock_nolot += $stockToRestore;
                        $inventory->save();
                    } else {
                        Inventorie::create([
                            'stock_lot' => 0,
                            'stock_nolot' => $stockToRestore,
                            'product_id' => $detail->product_id,
                            'warehouse_id' => $detail->warehouse_id,
                        ]);
                    }

                    $lastKardex = Kardex::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $newBalance = ($lastKardex ? $lastKardex->balance : 0) + $stockToRestore;

                    Kardex::create([
                        'type' => 'ENTRADA',
                        'description' => 'VENTA ANULADA - ' . $sale->sale_number,
                        'quantity_in' => $stockToRestore,
                        'balance' => $newBalance,
                        'price' => $detail->sale_price,
                        'total' => $detail->subtotal,
                        'product_id' => $detail->product_id,
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
