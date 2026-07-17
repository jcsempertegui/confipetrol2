<?php

namespace App\Livewire;

use App\Models\Delivery;
use App\Models\ProductVariant;
use App\Models\Worker;
use App\Services\InventoryService;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveriesController extends Component
{
    use AuditLog,WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $deliveryId;

    public $detailId;

    public $number = '';

    public $worker_id = '';

    public $workerSearch = '';

    public $delivery_date = '';

    public $reason = '';

    public $notes = '';

    public $annulReason = '';

    public $searchTerm = '';

    public $statusFilter = '';

    public $productSearch = '';

    public array $items = [];

    public function mount()
    {
        $this->delivery_date = now()->format('Y-m-d');
    }

    public function render()
    {
        $deliveries = Delivery::with('worker')->withCount('items')->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->where('number', 'like', '%'.$this->searchTerm.'%')->orWhereHas('worker', fn ($w) => $w->where('name', 'like', '%'.$this->searchTerm.'%')->orWhere('lastname', 'like', '%'.$this->searchTerm.'%')->orWhere('document', 'like', '%'.$this->searchTerm.'%'))))->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))->latest('delivery_date')->latest('id')->paginate(15);
        $selectedWorker = $this->worker_id ? Worker::find($this->worker_id) : null;
        $workerResults = collect();
        if (! $selectedWorker && mb_strlen(trim($this->workerSearch)) >= 2) {
            $workerTerm = '%'.trim($this->workerSearch).'%';
            $workerResults = Worker::where('status', true)->where(fn ($q) => $q->where('name', 'like', $workerTerm)->orWhere('lastname', 'like', $workerTerm)->orWhere('document', 'like', $workerTerm))->orderBy('lastname')->orderBy('name')->limit(12)->get();
        }
        $ids = collect($this->items)->pluck('variant_id')->filter();
        $selectedSerialIds = collect($this->items)->flatMap(fn ($item) => $item['serial_ids'] ?? [])->filter()->unique();
        $variants = ProductVariant::with(['product', 'serializedItems' => fn ($q) => $q->where('status', 'available')->when($selectedSerialIds->isNotEmpty(), fn ($serials) => $serials->orWhereIn('id', $selectedSerialIds))])->withSum('inventoryMovements as stock', 'quantity')->whereIn('id', $ids)->get();
        $productResults = collect();
        if (mb_strlen(trim($this->productSearch)) >= 2) {
            $term = '%'.trim($this->productSearch).'%';
            $productResults = ProductVariant::with('product')->withSum('inventoryMovements as stock', 'quantity')->where('status', true)->whereHas('product', fn ($q) => $q->where('status', true))->where(fn ($q) => $q->where('sku', 'like', $term)->orWhere('name', 'like', $term)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term))->orWhereHas('serializedItems', fn ($s) => $s->where('serial_number', 'like', $term)))->limit(15)->get();
        }

        $selectedDetail = $this->detailId ? Delivery::with(['worker', 'items.variant.product', 'items.serializedItems', 'creator', 'confirmer', 'annuller', 'correctedFrom', 'correction'])->find($this->detailId) : null;

        return view('livewire.deliveries.deliveries', compact('deliveries', 'selectedWorker', 'workerResults', 'variants', 'productResults', 'selectedDetail'))->extends('layouts.theme.app');
    }

    public function updated($p)
    {
        if (in_array($p, ['searchTerm', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function addProduct($id)
    {
        $v = ProductVariant::where('status', true)->whereHas('product', fn ($q) => $q->where('status', true))->findOrFail($id);
        if (collect($this->items)->contains('variant_id', $v->id)) {
            $this->dispatch('alert', 'Ese producto ya está agregado.', 'warning');

            return;
        }$this->items[] = ['variant_id' => $v->id, 'quantity' => 1, 'serial_ids' => [], 'notes' => ''];
        $this->productSearch = '';
    }

    public function removeItem($i)
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
    }

    public function selectWorker($id)
    {
        $w = Worker::where('status', true)->findOrFail($id);
        $this->worker_id = $w->id;
        $this->workerSearch = '';
    }

    public function clearWorker()
    {
        $this->worker_id = '';
        $this->workerSearch = '';
    }

    public function save()
    {
        abort_unless(auth()->user()->can($this->deliveryId ? 'editar-entrega' : 'crear-entrega'), 403);
        if ($this->deliveryId) {
            abort_unless(Delivery::whereKey($this->deliveryId)->where('status', 'draft')->exists(), 422);
        }
        $this->number = Str::upper(trim($this->number));
        $this->validate(['number' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9._\/-]*$/', Rule::unique('deliveries', 'number')->ignore($this->deliveryId)]]);
        $data = $this->validate(['worker_id' => 'required|exists:workers,id', 'delivery_date' => 'required|date|before_or_equal:today', 'reason' => 'nullable|string|max:180', 'notes' => 'nullable|string|max:2000', 'items' => 'required|array|min:1', 'items.*.variant_id' => 'required|integer|distinct|exists:product_variants,id', 'items.*.quantity' => 'required|numeric|min:0.001|max:999999999', 'items.*.serial_ids' => 'array', 'items.*.serial_ids.*' => 'integer|distinct|exists:serialized_items,id', 'items.*.notes' => 'nullable|string|max:500']);
        if (! Worker::whereKey($data['worker_id'])->where('status', true)->exists()) {
            throw ValidationException::withMessages(['worker_id' => 'Seleccione un trabajador activo.']);
        }
        $before = $this->deliveryId ? $this->snapshot(Delivery::findOrFail($this->deliveryId)) : null;
        $delivery = DB::transaction(function () use ($data) {
            $attrs = collect($data)->only(['worker_id', 'delivery_date', 'reason', 'notes'])->all() + ['status' => 'draft'];
            $d = $this->deliveryId ? Delivery::findOrFail($this->deliveryId) : new Delivery(['created_by' => auth()->id()]);
            $d->fill($attrs)->save();
            $d->items()->delete();
            foreach ($data['items'] as $row) {
                $v = ProductVariant::with('product')->findOrFail($row['variant_id']);
                $ids = collect($row['serial_ids'] ?? [])->filter()->unique();
                if ($v->product->tracking_type === 'serialized') {
                    if ($ids->isEmpty()) {
                        throw ValidationException::withMessages(['items' => 'Seleccione series para '.$v->sku]);
                    }$row['quantity'] = $ids->count();
                } elseif ($ids->isNotEmpty()) {
                    throw ValidationException::withMessages(['items' => 'El producto '.$v->sku.' no usa series.']);
                }$item = $d->items()->create(['product_variant_id' => $v->id, 'quantity' => $row['quantity'], 'notes' => $row['notes'] ?: null]);
                $item->serializedItems()->sync($ids);
            }

            return $d->fresh(['worker', 'items.variant.product', 'items.serializedItems']);
        });
        $delivery->update(['number' => $this->number ?: null]);
        $delivery = $delivery->fresh(['worker', 'items.variant.product', 'items.serializedItems']);
        $this->logActivity('ENTREGAS', $this->deliveryId ? 'EDITAR' : 'CREAR', 'Entrega en borrador para '.$delivery->worker->full_name, $delivery->id, $before, $this->snapshot($delivery));
        $this->resetForm();
        $this->dispatch('alert', 'Entrega guardada como borrador.', 'success');
    }

    public function edit($id)
    {
        abort_unless(auth()->user()->can('editar-entrega'), 403);
        $d = Delivery::with('items.serializedItems')->findOrFail($id);
        abort_unless($d->status === 'draft', 422);
        $this->deliveryId = $d->id;
        $this->number = $d->number ?? '';
        $this->worker_id = $d->worker_id;
        $this->delivery_date = $d->delivery_date->format('Y-m-d');
        $this->reason = $d->reason ?? '';
        $this->notes = $d->notes ?? '';
        $this->items = $d->items->map(fn ($i) => ['variant_id' => $i->product_variant_id, 'quantity' => (float) $i->quantity, 'serial_ids' => $i->serializedItems->pluck('id')->all(), 'notes' => $i->notes ?? ''])->all();
        $this->detailId = null;
        $this->dispatch('document-form-opened', target: 'delivery-form');
    }

    public function viewDetail($id)
    {
        abort_unless(auth()->user()->can('ver-entrega'), 403);
        $this->detailId = Delivery::findOrFail($id)->id;
    }

    public function correct($id)
    {
        abort_unless(auth()->user()->can('editar-entrega'), 403);
        $original = Delivery::with(['worker', 'items.serializedItems', 'correction'])->findOrFail($id);
        abort_unless($original->status === 'confirmed', 422);

        if ($original->correction?->status === 'draft') {
            $this->edit($original->correction->id);
            $this->dispatch('alert', 'Ya existía una corrección en borrador. Puedes continuar editándola.', 'info');

            return;
        }

        $before = $this->snapshot($original);
        $copy = DB::transaction(function () use ($original) {
            $draft = Delivery::create(['corrected_from_id' => $original->id, 'worker_id' => $original->worker_id, 'delivery_date' => $original->delivery_date, 'reason' => $original->reason, 'notes' => trim(($original->notes ? $original->notes."\n" : '').'Corrección de la entrega '.$original->number), 'status' => 'draft', 'created_by' => auth()->id()]);
            foreach ($original->items as $item) {
                $newItem = $draft->items()->create(['product_variant_id' => $item->product_variant_id, 'quantity' => $item->quantity, 'notes' => $item->notes]);
                $newItem->serializedItems()->sync($item->serializedItems->pluck('id'));
            }

            return $draft->fresh(['worker', 'items.variant.product', 'items.serializedItems']);
        });
        $this->logActivity('ENTREGAS', 'INICIAR_CORRECCION', 'Inicio de corrección trazable de la entrega '.$original->number, $copy->id, $before, ['entrega_original_vigente' => $original->number, 'nuevo_borrador_id' => $copy->id]);
        $this->edit($copy->id);
        $this->dispatch('alert', 'Se creó una versión editable. La entrega original seguirá vigente hasta que confirmes los cambios.', 'success');
    }

    public function confirm($id, InventoryService $s)
    {
        abort_unless(auth()->user()->can('confirmar-entrega'), 403);
        $d = Delivery::with(['items.serializedItems', 'correctedFrom.items.serializedItems'])->findOrFail($id);
        $isCorrection = filled($d->corrected_from_id);
        $before = $isCorrection
            ? ['versión_editada' => $this->snapshot($d), 'original_vigente' => $this->snapshot($d->correctedFrom)]
            : $this->snapshot($d);
        $d = $s->confirmDelivery($d, auth()->id());
        $after = $isCorrection
            ? ['versión_confirmada' => $this->snapshot($d), 'original_inactivo' => $this->snapshot($d->correctedFrom->fresh())]
            : $this->snapshot($d);
        $this->logActivity('ENTREGAS', $isCorrection ? 'CONFIRMAR_CORRECCION' : 'CONFIRMAR', ($isCorrection ? 'Confirmación de corrección ' : 'Confirmación de ').$d->number.' para '.$d->worker->full_name, $d->id, $before, $after);
        $this->dispatch('alert', $isCorrection ? 'Corrección confirmada. Se aplicó únicamente la diferencia al inventario y el original quedó inactivo.' : 'Entrega confirmada e inventario actualizado.', 'success');
    }

    public function annul($id, InventoryService $s)
    {
        abort_unless(auth()->user()->can('anular-entrega'), 403);
        $this->validate(['annulReason' => 'required|string|min:10|max:500']);
        $d = Delivery::findOrFail($id);
        $before = $this->snapshot($d);
        $d = $s->annulDelivery($d, auth()->id(), $this->annulReason);
        $this->logActivity('ENTREGAS', 'ANULAR', 'Anulación de '.$d->number, $d->id, $before, $this->snapshot($d));
        $this->annulReason = '';
        $this->dispatch('alert', 'Entrega anulada y stock devuelto.', 'success');
    }

    public function resetForm()
    {
        $this->reset(['deliveryId', 'number', 'worker_id', 'workerSearch', 'reason', 'notes', 'items', 'productSearch']);
        $this->delivery_date = now()->format('Y-m-d');
        $this->resetValidation();
    }

    private function snapshot(Delivery $d)
    {
        $d->loadMissing(['worker', 'items.variant.product', 'items.serializedItems']);

        return ['número' => $d->number, 'corrige_entrega_id' => $d->corrected_from_id, 'trabajador' => $d->worker?->full_name, 'documento' => $d->worker?->document, 'fecha' => $d->delivery_date?->format('Y-m-d'), 'motivo' => $d->reason, 'estado' => $d->status, 'motivo_anulación' => $d->annul_reason, 'detalles' => $d->items->map(fn ($i) => ['producto' => $i->variant?->product?->name, 'sku' => $i->variant?->sku, 'cantidad' => (float) $i->quantity, 'series' => $i->serializedItems->pluck('serial_number')->all()])->all()];
    }
}
