<?php

namespace App\Livewire;

use App\Models\Delivery;
use App\Models\ProductVariant;
use App\Models\Worker;
use App\Services\CodeGenerator;
use App\Services\InventoryService;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\DB;
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
        $serialBalance = $this->serialBalanceExpression();
        $variants = ProductVariant::with([
            'product',
            'serializedItems' => function ($query) use ($selectedSerialIds, $serialBalance) {
                $query->where(function ($options) use ($selectedSerialIds, $serialBalance) {
                    $options->where(function ($available) use ($serialBalance) {
                        $available->where('status', 'available')->whereRaw("{$serialBalance} = 1");
                    });
                    if ($selectedSerialIds->isNotEmpty()) {
                        $options->orWhereIn('serialized_items.id', $selectedSerialIds);
                    }
                })->orderBy('serial_number');
            },
        ])->withSum('inventoryMovements as stock', 'quantity')->whereIn('id', $ids)->get();
        $productResults = collect();
        if (mb_strlen(trim($this->productSearch)) >= 2) {
            $term = '%'.trim($this->productSearch).'%';
            $productResults = ProductVariant::with('product')
                ->withSum('inventoryMovements as stock', 'quantity')
                ->withCount(['serializedItems as deliverable_serials_count' => fn ($query) => $query
                    ->where('status', 'available')
                    ->whereRaw("{$serialBalance} = 1")])
                ->where('status', true)
                ->whereHas('product', fn ($q) => $q->where('status', true))
                ->where(fn ($q) => $q->where('sku', 'like', $term)->orWhere('name', 'like', $term)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term))->orWhereHas('serializedItems', fn ($s) => $s->where('serial_number', 'like', $term)))
                ->limit(15)->get();
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
        $v = ProductVariant::with('product')->where('status', true)->whereHas('product', fn ($q) => $q->where('status', true))->findOrFail($id);
        if (collect($this->items)->contains('variant_id', $v->id)) {
            $this->dispatch('alert', 'Ese producto ya está agregado.', 'warning');

            return;
        }
        if ($v->product->tracking_type === 'serialized' && ! $v->serializedItems()
            ->where('status', 'available')
            ->whereRaw($this->serialBalanceExpression().' = 1')
            ->exists()) {
            $this->dispatch('alert', 'Este producto no tiene números de serie disponibles en almacén. Confirma primero su remito de ingreso o selecciona otro equipo.', 'warning');

            return;
        }
        $this->items[] = ['variant_id' => $v->id, 'quantity' => 1, 'serial_ids' => [], 'notes' => ''];
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
        $this->number = app(CodeGenerator::class)->normalizeSiteSuffix($this->number);
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

    public function deleteDraft(int $id): void
    {
        abort_unless(auth()->user()->can('eliminar-entrega'), 403);

        [$number, $before] = DB::transaction(function () use ($id) {
            $delivery = Delivery::with(['worker', 'items.variant.product', 'items.serializedItems'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($delivery->status !== 'draft') {
                throw ValidationException::withMessages([
                    'document' => 'Solo se pueden eliminar entregas que continúen en borrador.',
                ]);
            }

            if ($delivery->movements()->exists()) {
                throw ValidationException::withMessages([
                    'document' => 'Esta entrega tiene movimientos de inventario y no puede eliminarse.',
                ]);
            }

            $number = $delivery->number ?: 'BORRADOR #'.$delivery->id;
            $before = $this->snapshot($delivery);
            $delivery->delete();

            return [$number, $before];
        });

        $this->logActivity('ENTREGAS', 'ELIMINAR_BORRADOR', 'Eliminación de la entrega en borrador '.$number, $id, $before, ['eliminado' => true]);

        if ((int) $this->deliveryId === $id) {
            $this->resetForm();
        }
        if ((int) $this->detailId === $id) {
            $this->detailId = null;
        }

        $this->dispatch('alert', 'Entrega en borrador eliminada. La copia completa quedó registrada en el historial.', 'success');
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
        $d->loadMissing(['worker', 'creator', 'items.variant.product', 'items.serializedItems']);

        return [
            'número' => $d->number,
            'corrige_entrega_id' => $d->corrected_from_id,
            'trabajador' => $d->worker?->full_name,
            'documento' => $d->worker?->document,
            'fecha' => $d->delivery_date?->format('Y-m-d'),
            'motivo' => $d->reason,
            'observaciones' => $d->notes,
            'estado' => $d->status,
            'creado_por' => $d->creator?->login,
            'creado_el' => $d->created_at?->format('Y-m-d H:i:s'),
            'motivo_anulación' => $d->annul_reason,
            'detalles' => $d->items->map(fn ($item) => [
                'producto' => $item->variant?->product?->name,
                'sku' => $item->variant?->sku,
                'cantidad' => (float) $item->quantity,
                'observaciones' => $item->notes,
                'series' => $item->serializedItems->pluck('serial_number')->all(),
            ])->all(),
        ];
    }

    private function serialBalanceExpression(): string
    {
        return '(SELECT COALESCE(SUM(im.quantity), 0) FROM inventory_movements im WHERE im.serialized_item_id = serialized_items.id)';
    }
}
