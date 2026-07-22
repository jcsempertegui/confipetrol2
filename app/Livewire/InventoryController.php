<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $searchTerm = '';

    public $categoryFilter = '';

    public $stockFilter = '';

    public $selectedVariantId;

    public $fromDate = '';

    public $toDate = '';

    public $movementFilter = '';

    public function mount(): void
    {
        $this->fromDate = now()->subDays(30)->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $variants = ProductVariant::query()->with(['product.category', 'attributeValues.attribute'])
            ->withSum('inventoryMovements as stock', 'quantity')
            ->withCount(['serializedItems as serialized_available_count' => fn ($q) => $q->where('status', 'available'), 'serializedItems as serialized_assigned_count' => fn ($q) => $q->where('status', 'assigned')])
            ->when($this->searchTerm, function ($q) {
                $term = '%'.trim($this->searchTerm).'%';
                $q->where(fn ($x) => $x->where('sku', 'like', $term)->orWhere('name', 'like', $term)
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term)));
            })->when($this->categoryFilter, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $this->categoryFilter)))
            ->when($this->stockFilter === 'positive', fn ($q) => $q->having('stock', '>', 0))
            ->when($this->stockFilter === 'zero', fn ($q) => $q->havingRaw('COALESCE(stock, 0) = 0'))
            ->orderBy('sku')->paginate(25, ['*'], 'stockPage');

        $selectedVariant = $this->selectedVariantId ? ProductVariant::with(['product.category'])->withSum('inventoryMovements as stock', 'quantity')->find($this->selectedVariantId) : null;
        $movements = null;
        if ($selectedVariant) {
            $query = InventoryMovement::with(['dispatchNote', 'delivery.worker', 'serializedItem', 'creator'])
                ->where('product_variant_id', $selectedVariant->id)
                ->when($this->fromDate, fn ($q) => $q->whereDate('occurred_at', '>=', $this->fromDate))
                ->when($this->toDate, fn ($q) => $q->whereDate('occurred_at', '<=', $this->toDate))
                ->when($this->movementFilter, fn ($q) => $q->where('movement_type', $this->movementFilter))
                ->latest('id');
            $movements = $query->paginate(30, ['*'], 'kardexPage');
            foreach ($movements as $movement) {
                $movement->balance_after = (float) InventoryMovement::where('product_variant_id', $selectedVariant->id)
                    ->where('id', '<=', $movement->id)->sum('quantity');
            }
        }

        return view('livewire.inventory.inventory', ['variants' => $variants, 'categories' => Category::orderBy('name')->get(), 'selectedVariant' => $selectedVariant, 'movements' => $movements])->extends('layouts.theme.app');
    }

    public function updated($property): void
    {
        if (in_array($property, ['fromDate', 'toDate', 'movementFilter'], true)) {
            $this->resetPage('kardexPage');
        }
        if (in_array($property, ['searchTerm', 'categoryFilter', 'stockFilter'], true)) {
            $this->resetPage('stockPage');
        }
    }

    public function viewKardex(int $variantId): void
    {
        abort_unless(auth()->user()->can('ver-kardex'), 403);
        $this->selectedVariantId = ProductVariant::findOrFail($variantId)->id;
        $this->resetPage('kardexPage');
    }

    public function clearKardex(): void
    {
        $this->selectedVariantId = null;
        $this->resetPage('kardexPage');
    }

    public function movementLabel(string $type): string
    {
        return match ($type) {
            'dispatch_entry' => 'Remito de ingreso', 'dispatch_exit' => 'Remito de salida',
            'dispatch_correction' => 'Corrección de remito',
            'annulment' => 'Anulación de remito', 'delivery' => 'Entrega a trabajador',
            'delivery_correction' => 'Corrección de entrega',
            'delivery_annulment' => 'Anulación de entrega', default => $type,
        };
    }
}
