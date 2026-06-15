<?php

namespace App\Livewire;

use App\Models\Delivery;
use App\Models\DeliveryDetail;
use App\Models\Inventorie;
use App\Models\Kardex;
use App\Models\ProductSku;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryListsController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $delivery_id, $delivery;
    public $delivery_details = [];
    public $delivery_number, $worker_name, $user_name, $observations_detail;

    public $searchTerm;
    public $filter_status = '1';
    protected $listeners = ['deleteDelivery'];
    public $branch_id;

    public $fromDate, $toDate;
    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }

    public function mount()
    {
        $this->delivery_details = [];
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function render()
    {
        $deliveries = $this->DeliveriesByDate();
        return view('livewire.deliveries.delivery_lists', [
            'deliveries'  => $deliveries,
            'startCount'  => $deliveries->total() - ($deliveries->currentPage() - 1) * $deliveries->perPage(),
        ])->extends('layouts.theme.app');
    }

    public function DeliveriesByDate()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate   = Carbon::parse($this->toDate ?? now())->endOfDay();

        return Delivery::with([
            'user:id,name,login',
            'branch:id,name',
            'worker:id,name,last_name',
        ])
            ->withCount('details')
            ->withSum('details', 'quantity')
            ->where('branch_id', $this->branch_id)
            ->when($this->filter_status !== '', fn ($q) => $q->where('status', $this->filter_status))
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('created_at', [$fromDate, $toDate]))
            ->where(function ($query) {
                if (strlen($this->searchTerm) > 0) {
                    $query->where('delivery_number', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('worker', fn ($q) =>
                            $q->where('name', 'like', '%' . $this->searchTerm . '%')
                              ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%')
                        );
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    public function detailDelivery($delivery_id)
    {
        $this->reset(['delivery', 'delivery_details', 'delivery_number', 'worker_name', 'user_name', 'observations_detail']);

        $this->delivery = Delivery::with(['user:id,name,login', 'worker:id,name,last_name'])
            ->find($delivery_id);

        if ($this->delivery) {
            $this->delivery_number    = $this->delivery->delivery_number;
            $this->worker_name        = $this->delivery->worker
                ? ($this->delivery->worker->name . ' ' . $this->delivery->worker->last_name)
                : 'S/N';
            $this->user_name          = $this->delivery->user->login ?? $this->delivery->user->name ?? 'S/N';
            $this->observations_detail = $this->delivery->observations ?? '';
        }

        $this->delivery_details = DeliveryDetail::with([
            'product:id,name,code',
            'sku.size:id,name',
            'sku.color:id,name',
        ])
            ->where('delivery_id', $delivery_id)
            ->get();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function deleteDelivery($delivery_id)
    {
        DB::beginTransaction();
        try {
            $delivery = Delivery::find($delivery_id);
            if (!$delivery) {
                $this->dispatch('alert', 'Entrega no encontrada', 'error');
                DB::rollBack();
                return;
            }

            $delivery->update(['status' => 0]);

            $details = DeliveryDetail::with(['product'])->where('delivery_id', $delivery_id)->get();

            foreach ($details as $detail) {
                if ($detail->sku_id) {
                    $sku = ProductSku::find($detail->sku_id);
                    if ($sku) {
                        $sku->stock += $detail->quantity;
                        $sku->save();
                    }
                    $inventory = Inventorie::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->first();
                    if ($inventory) {
                        $inventory->stock_nolot += $detail->quantity;
                        $inventory->save();
                    }
                } else {
                    $inventory = Inventorie::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->first();
                    if ($inventory) {
                        $inventory->stock_nolot += $detail->quantity;
                        $inventory->save();
                    }
                }

                $lastKardex = Kardex::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $detail->warehouse_id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $newBalance = ($lastKardex ? $lastKardex->balance : 0) + $detail->quantity;

                Kardex::create([
                    'type'             => 'ENTRADA',
                    'description'      => 'ENTREGA EPP ANULADA - ' . $delivery->delivery_number,
                    'quantity_in'      => $detail->quantity,
                    'quantity_out'     => 0,
                    'balance'          => $newBalance,
                    'product_id'       => $detail->product_id,
                    'user_id'          => auth()->id(),
                    'warehouse_id'     => $detail->warehouse_id,
                    'transaction_type' => 'deliveries',
                    'transaction_id'   => $delivery->id,
                    'status'           => 1,
                ]);
            }

            DB::commit();

            $this->logActivity(
                'ENTREGAS', 'ANULAR',
                "Anuló entrega EPP: {$delivery->delivery_number}",
                $delivery->id,
                ['status' => 1],
                ['status' => 0]
            );

            $this->dispatch('alert', 'LA ENTREGA HA SIDO ANULADA Y EL STOCK RESTAURADO', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al anular entrega: ' . $e->getMessage(), 'error');
        }
    }
}
