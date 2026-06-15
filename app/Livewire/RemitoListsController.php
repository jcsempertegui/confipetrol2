<?php

namespace App\Livewire;

use App\Models\Remito;
use App\Models\RemitoDetail;
use App\Models\Inventorie;
use App\Models\Kardex;
use App\Models\Product;
use App\Models\ProductSku;
use App\Traits\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RemitoListsController extends Component
{
    use WithPagination, AuditLog;
    protected $paginationTheme = 'bootstrap';

    public $remito_id, $remito;
    public $remito_details = [];
    public $remito_number, $tipo_detail, $user_name, $observations_detail;
    public $contrato_detail, $senores_detail, $atencion_detail, $campo_detail;
    public $n_orden_detail, $despachado_detail, $transportado_detail, $placa_detail;

    public $searchTerm;
    public $filter_status = '1';
    public $filter_tipo = '';
    protected $listeners = ['deleteRemito'];
    public $branch_id;

    public $fromDate, $toDate;
    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }
    public function updatedFilterTipo() { $this->resetPage(); }

    public function mount()
    {
        $this->remito_details = [];
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function render()
    {
        $remitos = $this->RemitosByDate();
        return view('livewire.remitos.remito_lists', [
            'remitos'    => $remitos,
            'startCount' => $remitos->total() - ($remitos->currentPage() - 1) * $remitos->perPage(),
        ])->extends('layouts.theme.app');
    }

    public function RemitosByDate()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate   = Carbon::parse($this->toDate ?? now())->endOfDay();

        return Remito::with([
            'user:id,name,login',
            'branch:id,name',
        ])
            ->withCount('details')
            ->withSum('details', 'quantity')
            ->where('branch_id', $this->branch_id)
            ->when($this->filter_status !== '', fn ($q) => $q->where('status', $this->filter_status))
            ->when($this->filter_tipo !== '', fn ($q) => $q->where('tipo', $this->filter_tipo))
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('created_at', [$fromDate, $toDate]))
            ->where(function ($query) {
                if (strlen($this->searchTerm) > 0) {
                    $query->where('remito_number', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('contrato', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('senores', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('n_orden', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('campo', 'like', '%' . $this->searchTerm . '%');
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    public function detailRemito($remito_id)
    {
        $this->reset([
            'remito', 'remito_details', 'remito_number', 'tipo_detail', 'user_name',
            'observations_detail', 'contrato_detail', 'senores_detail', 'atencion_detail',
            'campo_detail', 'n_orden_detail', 'despachado_detail', 'transportado_detail', 'placa_detail',
        ]);

        $this->remito = Remito::with(['user:id,name,login'])->find($remito_id);

        if ($this->remito) {
            $this->remito_number       = $this->remito->remito_number;
            $this->tipo_detail         = $this->remito->tipo;
            $this->user_name           = $this->remito->user->login ?? $this->remito->user->name ?? 'S/N';
            $this->observations_detail = $this->remito->observations ?? '';
            $this->contrato_detail     = $this->remito->contrato ?? '';
            $this->senores_detail      = $this->remito->senores ?? '';
            $this->atencion_detail     = $this->remito->atencion ?? '';
            $this->campo_detail        = $this->remito->campo ?? '';
            $this->n_orden_detail      = $this->remito->n_orden ?? '';
            $this->despachado_detail   = $this->remito->despachado_por ?? '';
            $this->transportado_detail = $this->remito->transportado_por ?? '';
            $this->placa_detail        = $this->remito->placa ?? '';
        }

        $this->remito_details = RemitoDetail::with([
            'product:id,name,code',
            'sku.size:id,name',
            'sku.color:id,name',
        ])
            ->where('remito_id', $remito_id)
            ->get();
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function deleteRemito($remito_id)
    {
        DB::beginTransaction();
        try {
            $remito = Remito::find($remito_id);
            if (!$remito) {
                $this->dispatch('alert', 'Remito no encontrado', 'error');
                DB::rollBack();
                return;
            }

            $remito->update(['status' => 0]);

            $details = RemitoDetail::with(['product'])->where('remito_id', $remito_id)->get();
            $isIngreso = $remito->tipo === 'INGRESO';

            foreach ($details as $detail) {
                // Revertir stock (inverso del tipo original)
                if ($detail->sku_id) {
                    $sku = ProductSku::find($detail->sku_id);
                    if ($sku) {
                        $sku->stock = $isIngreso
                            ? $sku->stock - $detail->quantity
                            : $sku->stock + $detail->quantity;
                        $sku->save();
                    }
                    $inventory = Inventorie::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->first();
                    if ($inventory) {
                        $inventory->stock_nolot = $isIngreso
                            ? $inventory->stock_nolot - $detail->quantity
                            : $inventory->stock_nolot + $detail->quantity;
                        $inventory->save();
                    }
                } else {
                    $product = $detail->product;
                    $inventory = Inventorie::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $detail->warehouse_id)
                        ->first();
                    if ($inventory) {
                        $inventory->stock_nolot = $isIngreso
                            ? $inventory->stock_nolot - $detail->quantity
                            : $inventory->stock_nolot + $detail->quantity;
                        $inventory->save();
                    }
                }

                // Kardex de reversión
                $lastKardex = Kardex::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $detail->warehouse_id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $newBalance = $isIngreso
                    ? ($lastKardex ? $lastKardex->balance : 0) - $detail->quantity
                    : ($lastKardex ? $lastKardex->balance : 0) + $detail->quantity;

                Kardex::create([
                    'type'             => $isIngreso ? 'SALIDA' : 'ENTRADA',
                    'description'      => 'REMITO ANULADO - ' . $remito->remito_number,
                    'quantity_in'      => $isIngreso ? 0 : $detail->quantity,
                    'quantity_out'     => $isIngreso ? $detail->quantity : 0,
                    'balance'          => $newBalance,
                    'product_id'       => $detail->product_id,
                    'user_id'          => auth()->id(),
                    'warehouse_id'     => $detail->warehouse_id,
                    'transaction_type' => 'remitos',
                    'transaction_id'   => $remito->id,
                    'status'           => 1,
                ]);
            }

            DB::commit();

            $this->logActivity(
                'REMITOS', 'ANULAR',
                "Anuló remito {$remito->tipo}: {$remito->remito_number}",
                $remito->id,
                ['status' => 1],
                ['status' => 0]
            );

            $this->dispatch('alert', 'EL REMITO HA SIDO ANULADO Y EL STOCK REVERTIDO', 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', 'Error al anular remito: ' . $e->getMessage(), 'error');
        }
    }
}
