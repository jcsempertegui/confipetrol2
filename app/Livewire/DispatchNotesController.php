<?php

namespace App\Livewire;

use App\Models\DispatchNote;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class DispatchNotesController extends Component
{
    use AuditLog, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $noteId;

    public $correctedFromId;

    public $detailId;

    public $number = '';

    public $type = 'entry';

    public $document_date = '';

    public $counterparty = '';

    public $reason = '';

    public $notes = '';

    public $annulReason = '';

    public $searchTerm = '';

    public $typeFilter = '';

    public $statusFilter = '';

    public $productSearch = '';

    public array $items = [];

    public function mount(): void
    {
        $this->document_date = now()->format('Y-m-d');
    }

    public function render()
    {
        $dispatchNotes = DispatchNote::with(['items.variant.product', 'creator'])->withCount('items')
            ->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->where('number', 'like', '%'.$this->searchTerm.'%')->orWhere('counterparty', 'like', '%'.$this->searchTerm.'%')))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('document_date')->latest('id')->paginate(15);
        $ids = collect($this->items)->pluck('variant_id')->filter();
        $variants = ProductVariant::with(['product', 'serializedItems' => fn ($q) => $q->where('status', '!=', 'inactive')])->withSum('inventoryMovements as stock', 'quantity')->whereIn('id', $ids)->get();
        $productResults = collect();
        if (mb_strlen(trim($this->productSearch)) >= 2) {
            $term = '%'.trim($this->productSearch).'%';
            $productResults = ProductVariant::with('product')->withSum('inventoryMovements as stock', 'quantity')->where('status', true)->whereHas('product', fn ($q) => $q->where('status', true))
                ->where(fn ($q) => $q->where('sku', 'like', $term)->orWhere('name', 'like', $term)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term))->orWhereHas('serializedItems', fn ($s) => $s->where('serial_number', 'like', $term)))->limit(15)->get();
        }
        $selectedDetail = $this->detailId ? DispatchNote::with(['items.variant.product', 'items.serializedItems', 'creator', 'confirmer', 'annuller', 'correctedFrom', 'correction'])->find($this->detailId) : null;

        return view('livewire.dispatch-notes.dispatch-notes', compact('dispatchNotes', 'variants', 'productResults', 'selectedDetail'))->extends('layouts.theme.app');
    }

    public function updated($property): void
    {
        if (str_contains($property, 'Filter') || $property === 'searchTerm') {
            $this->resetPage();
        }
    }

    public function addProduct(int $id): void
    {
        $variant = ProductVariant::where('status', true)->whereHas('product', fn ($q) => $q->where('status', true))->findOrFail($id);
        if (collect($this->items)->contains('variant_id', $variant->id)) {
            $this->dispatch('alert', 'Ese producto ya está agregado.', 'warning');

            return;
        } $this->items[] = ['variant_id' => $variant->id, 'quantity' => 1, 'serial_ids' => [], 'notes' => ''];
        $this->productSearch = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->noteId ? 'editar-remito' : 'crear-remito'), 403);
        if ($this->noteId) {
            abort_unless(DispatchNote::whereKey($this->noteId)->where('status', 'draft')->exists(), 422);
        }
        if ($this->correctedFromId) {
            $originalType = DispatchNote::whereKey($this->correctedFromId)->value('type');
            if ($originalType !== $this->type) {
                throw ValidationException::withMessages(['type' => 'El tipo de un remito no puede cambiarse durante una corrección.']);
            }
        }
        $this->number = Str::upper(trim($this->number));
        $data = $this->validate([
            'number' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9._\/-]*$/', Rule::unique('dispatch_notes', 'number')->ignore($this->noteId)],
            'type' => 'required|in:entry,exit', 'document_date' => 'required|date|before_or_equal:today',
            'counterparty' => 'required|string|max:180', 'reason' => 'nullable|string|max:180', 'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1', 'items.*.variant_id' => 'required|integer|distinct|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.001|max:999999999',
            'items.*.notes' => 'nullable|string|max:500', 'items.*.serial_ids' => 'array', 'items.*.serial_ids.*' => 'integer|distinct|exists:serialized_items,id',
        ]);
        $before = $this->noteId ? $this->snapshot(DispatchNote::with('items.serializedItems')->findOrFail($this->noteId)) : null;
        $note = DB::transaction(function () use ($data) {
            $attributes = collect($data)->only(['number', 'type', 'document_date', 'counterparty', 'reason', 'notes'])->all() + ['status' => 'draft'];
            $attributes['number'] = $attributes['number'] ?: null;
            $note = $this->noteId ? DispatchNote::findOrFail($this->noteId) : new DispatchNote(['created_by' => auth()->id()]);
            $note->fill($attributes)->save();
            $note->items()->delete();
            foreach ($data['items'] as $row) {
                $variant = ProductVariant::with('product')->findOrFail($row['variant_id']);
                $serialIds = collect($row['serial_ids'] ?? [])->filter()->unique()->values();
                if ($variant->product->tracking_type === 'serialized') {
                    if ($serialIds->isEmpty()) {
                        throw ValidationException::withMessages(['items' => 'Seleccione los números de serie del producto '.$variant->sku.'.']);
                    }
                    $row['quantity'] = $serialIds->count();
                } elseif ($serialIds->isNotEmpty()) {
                    throw ValidationException::withMessages(['items' => 'El producto '.$variant->sku.' no utiliza números de serie.']);
                }
                $item = $note->items()->create(['product_variant_id' => $row['variant_id'], 'quantity' => $row['quantity'], 'notes' => $row['notes'] ?: null]);
                $item->serializedItems()->sync($serialIds);
            }

            return $note->fresh(['items.variant.product', 'items.serializedItems']);
        });
        $this->logActivity('REMITOS', $this->noteId ? 'EDITAR' : 'CREAR', 'Remito en borrador', $note->id, $before, $this->snapshot($note));
        $this->resetForm();
        $this->dispatch('alert', 'Remito guardado como borrador.', 'success');
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('editar-remito'), 403);
        $note = DispatchNote::with('items.serializedItems')->findOrFail($id);
        abort_unless($note->status === 'draft', 422);
        $this->noteId = $note->id;
        $this->correctedFromId = $note->corrected_from_id;
        foreach (['number', 'type', 'counterparty', 'reason', 'notes'] as $field) {
            $this->{$field} = $note->{$field} ?? '';
        } $this->document_date = $note->document_date->format('Y-m-d');
        $this->items = $note->items->map(fn ($i) => ['variant_id' => $i->product_variant_id, 'quantity' => (float) $i->quantity, 'serial_ids' => $i->serializedItems->pluck('id')->all(), 'notes' => $i->notes ?? ''])->all();
        $this->detailId = null;
        $this->dispatch('document-form-opened', target: 'dispatch-note-form');
    }

    public function viewDetail(int $id): void
    {
        abort_unless(auth()->user()->can('ver-remito'), 403);
        $this->detailId = DispatchNote::findOrFail($id)->id;
    }

    public function correct(int $id): void
    {
        abort_unless(auth()->user()->can('editar-remito'), 403);
        $original = DispatchNote::with(['items.serializedItems', 'correction'])->findOrFail($id);
        abort_unless($original->status === 'confirmed', 422);

        if ($original->correction?->status === 'draft') {
            $this->edit($original->correction->id);
            $this->dispatch('alert', 'Ya existía una corrección en borrador. Puedes continuar editándola.', 'info');

            return;
        }

        $before = $this->snapshot($original);
        $copy = DB::transaction(function () use ($original) {
            $draft = DispatchNote::create(['corrected_from_id' => $original->id, 'type' => $original->type, 'document_date' => $original->document_date, 'counterparty' => $original->counterparty, 'reason' => $original->reason, 'notes' => trim(($original->notes ? $original->notes."\n" : '').'Corrección del remito '.$original->number), 'status' => 'draft', 'created_by' => auth()->id()]);
            foreach ($original->items as $item) {
                $newItem = $draft->items()->create(['product_variant_id' => $item->product_variant_id, 'quantity' => $item->quantity, 'notes' => $item->notes]);
                $newItem->serializedItems()->sync($item->serializedItems->pluck('id'));
            }

            return $draft->fresh(['items.variant.product', 'items.serializedItems']);
        });
        $this->logActivity('REMITOS', 'INICIAR_CORRECCION', 'Inicio de corrección trazable del remito '.$original->number, $copy->id, $before, ['remito_original_vigente' => $original->number, 'nuevo_borrador_id' => $copy->id]);
        $this->edit($copy->id);
        $this->dispatch('alert', 'Se creó una versión editable. El remito original seguirá vigente hasta que confirmes los cambios.', 'success');
    }

    public function confirm(int $id, InventoryService $service): void
    {
        abort_unless(auth()->user()->can('confirmar-remito'), 403);
        $note = DispatchNote::with(['items.serializedItems', 'correctedFrom.items.serializedItems'])->findOrFail($id);
        $isCorrection = filled($note->corrected_from_id);
        $before = $isCorrection
            ? ['versión_editada' => $this->snapshot($note), 'original_vigente' => $this->snapshot($note->correctedFrom)]
            : $this->snapshot($note);
        $note = $service->confirm($note, auth()->id());
        $after = $isCorrection
            ? ['versión_confirmada' => $this->snapshot($note), 'original_inactivo' => $this->snapshot($note->correctedFrom->fresh())]
            : $this->snapshot($note);
        $this->logActivity('REMITOS', $isCorrection ? 'CONFIRMAR_CORRECCION' : 'CONFIRMAR', ($isCorrection ? 'Confirmación de corrección ' : 'Confirmación del remito ').$note->number, $note->id, $before, $after);
        $this->dispatch('alert', $isCorrection ? 'Corrección confirmada. Se aplicó únicamente la diferencia al inventario y el original quedó inactivo.' : 'Remito confirmado. El inventario fue actualizado.', 'success');
    }

    public function annul(int $id, InventoryService $service): void
    {
        abort_unless(auth()->user()->can('anular-remito'), 403);
        $this->validate(['annulReason' => 'required|string|min:10|max:500']);
        $note = DispatchNote::findOrFail($id);
        $before = $this->snapshot($note->load('items.serializedItems'));
        $note = $service->annul($note, auth()->id(), $this->annulReason);
        $this->logActivity('REMITOS', 'ANULAR', 'Anulación del remito '.$note->number, $note->id, $before, $this->snapshot($note));
        $this->annulReason = '';
        $this->dispatch('alert', 'Remito anulado mediante movimientos inversos.', 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['noteId', 'correctedFromId', 'number', 'counterparty', 'reason', 'notes', 'items', 'productSearch']);
        $this->type = 'entry';
        $this->document_date = now()->format('Y-m-d');
        $this->resetValidation();
    }

    private function snapshot(DispatchNote $note): array
    {
        $note->loadMissing(['items.variant.product', 'items.serializedItems']);

        return ['número' => $note->number, 'corrige_remito_id' => $note->corrected_from_id, 'tipo' => $note->type, 'fecha' => $note->document_date?->format('Y-m-d'), 'contraparte' => $note->counterparty, 'motivo' => $note->reason, 'estado' => $note->status, 'motivo_anulación' => $note->annul_reason, 'detalles' => $note->items->map(fn ($i) => ['producto' => $i->variant?->product?->name, 'sku' => $i->variant?->sku, 'cantidad' => (float) $i->quantity, 'series' => $i->serializedItems->pluck('serial_number')->all()])->all()];
    }
}
