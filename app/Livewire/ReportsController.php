<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\DeliveryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\Worker;
use App\Support\Quantity;
use App\Traits\AuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsController extends Component
{
    use AuditLog, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $reportType = 'stock';

    public $searchTerm = '';

    public $categoryFilter = '';

    public $fromDate = '';

    public $toDate = '';

    public $stockStatus = '';

    public $catalogStatus = 'active';

    public $trackingFilter = '';

    public $unitFilter = '';

    public $expiryFrom = '';

    public $expiryTo = '';

    public $expiryStatus = '';

    public $serialFilter = '';

    public array $attributeFilters = [];

    public bool $showAdvancedFilters = false;

    public $movementType = '';

    public $documentSource = '';

    public $deliveryStatus = 'confirmed';

    public $areaFilter = '';

    public $workerFilter = '';

    public $workerSearch = '';

    public function mount(): void
    {
        $this->setPeriod('month');
    }

    public function render()
    {
        $selectedCategory = $this->selectedCategory();
        $reportAttributes = $selectedCategory?->attributes
            ->where('status', true)
            ->reject(fn (ProductAttribute $attribute) => $attribute->scope === 'unit' || $this->isExpirationAttribute($attribute))
            ->values() ?? collect();
        $hasSerialContext = (bool) ($selectedCategory
            && ($selectedCategory->attributes->contains(fn (ProductAttribute $attribute) => $attribute->status && $attribute->scope === 'unit') || $selectedCategory->products()->where('tracking_type', 'serialized')->exists()));
        $showSerialColumn = $this->reportType === 'stock' && $hasSerialContext;
        $showExpiryColumn = $this->reportType === 'stock' && $selectedCategory
            && $selectedCategory->attributes->contains(fn (ProductAttribute $attribute) => $attribute->status && $this->isExpirationAttribute($attribute));

        $rows = match ($this->reportType) {
            'movements' => $this->movementQuery()->latest('occurred_at')->paginate(30),
            'deliveries' => $this->deliveryQuery()->latest('id')->paginate(30),
            default => $this->stockQuery()->orderBy('products.name')->orderBy('product_variants.sku')->paginate(30),
        };

        return view('livewire.reports.reports', [
            'rows' => $rows,
            'categories' => Category::where('status', true)->orderBy('name')->get(),
            'units' => Product::when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))->whereNotNull('unit')->distinct()->orderBy('unit')->pluck('unit'),
            'trackingTypes' => Product::when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))->whereNotNull('tracking_type')->distinct()->pluck('tracking_type'),
            'areas' => Worker::whereNotNull('area')->where('area', '!=', '')->distinct()->orderBy('area')->pluck('area'),
            'workerResults' => $this->workerResults(),
            'selectedWorker' => $this->workerFilter ? Worker::find($this->workerFilter) : null,
            'selectedCategory' => $selectedCategory,
            'reportAttributes' => $reportAttributes,
            'showSerialColumn' => $showSerialColumn,
            'hasSerialContext' => $hasSerialContext,
            'showExpiryColumn' => $showExpiryColumn,
            'stockColumnCount' => 8 + $reportAttributes->count() + ($showSerialColumn ? 1 : 0) + ($showExpiryColumn ? 2 : 0),
        ])->extends('layouts.theme.app');
    }

    public function updated($property): void
    {
        if (in_array($property, ['fromDate', 'toDate'], true)) {
            $this->resetErrorBag(['fromDate', 'toDate']);
            if ($this->fromDate && $this->toDate && $this->fromDate > $this->toDate) {
                $this->addError('toDate', 'La fecha final debe ser igual o posterior a la fecha inicial.');
            }
        }
        if (in_array($property, ['expiryFrom', 'expiryTo'], true)) {
            $this->resetErrorBag(['expiryFrom', 'expiryTo']);
            if ($this->expiryFrom && $this->expiryTo && $this->expiryFrom > $this->expiryTo) {
                $this->addError('expiryTo', 'La fecha final de vencimiento debe ser igual o posterior a la inicial.');
            }
        }
        if ($property === 'reportType') {
            $this->searchTerm = '';
            $this->categoryFilter = '';
            $this->attributeFilters = [];
            $this->serialFilter = '';
            $this->showAdvancedFilters = false;
        }
        if ($property === 'categoryFilter') {
            $this->attributeFilters = [];
            $this->serialFilter = '';
            $this->expiryFrom = '';
            $this->expiryTo = '';
            $this->expiryStatus = '';
            $this->trackingFilter = '';
            $this->unitFilter = '';
            $this->showAdvancedFilters = false;
        }
        $this->resetPage();
    }

    public function setPeriod(string $period): void
    {
        [$from, $to] = match ($period) {
            'today' => [now(), now()],
            'week' => [now()->startOfWeek(), now()],
            'year' => [now()->startOfYear(), now()],
            'all' => [null, null],
            default => [now()->startOfMonth(), now()],
        };
        $this->fromDate = $from?->format('Y-m-d') ?? '';
        $this->toDate = $to?->format('Y-m-d') ?? '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $type = $this->reportType;
        $this->reset(['searchTerm', 'categoryFilter', 'stockStatus', 'trackingFilter', 'unitFilter', 'expiryFrom', 'expiryTo', 'expiryStatus', 'serialFilter', 'attributeFilters', 'showAdvancedFilters', 'movementType', 'documentSource', 'areaFilter', 'workerFilter', 'workerSearch']);
        $this->catalogStatus = 'active';
        $this->deliveryStatus = 'confirmed';
        $this->reportType = $type;
        $this->setPeriod('month');
    }

    public function selectWorker(int $id): void
    {
        $worker = Worker::findOrFail($id);
        $this->workerFilter = $worker->id;
        $this->workerSearch = '';
        $this->resetPage();
    }

    public function clearWorker(): void
    {
        $this->workerFilter = '';
        $this->workerSearch = '';
        $this->resetPage();
    }

    public function exportCsv()
    {
        abort_unless(auth()->user()->can('exportar-reporte'), 403);
        $this->validateReportDates();
        $type = $this->reportType;
        $filename = 'reporte_'.$type.'_'.now()->format('Ymd_His').'.csv';
        $selectedCategory = $this->selectedCategory();
        $reportAttributes = $selectedCategory?->attributes
            ->where('status', true)
            ->reject(fn (ProductAttribute $attribute) => $attribute->scope === 'unit' || $this->isExpirationAttribute($attribute))
            ->values() ?? collect();
        $hasSerialContext = (bool) ($selectedCategory
            && ($selectedCategory->attributes->contains(fn (ProductAttribute $attribute) => $attribute->status && $attribute->scope === 'unit') || $selectedCategory->products()->where('tracking_type', 'serialized')->exists()));
        $showSerialColumn = $type === 'stock' && $hasSerialContext;
        $showExpiryColumn = $type === 'stock' && $selectedCategory
            && $selectedCategory->attributes->contains(fn (ProductAttribute $attribute) => $attribute->status && $this->isExpirationAttribute($attribute));
        $this->logActivity('REPORTES', 'EXPORTAR', 'Exportación CSV del reporte '.$type, null, null, $this->activeFilters());

        return response()->streamDownload(function () use ($type, $reportAttributes, $showSerialColumn, $showExpiryColumn) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            if ($type === 'movements') {
                fputcsv($out, ['Fecha', 'Movimiento', 'Producto', 'SKU', 'Unidad', 'Lote', 'Vencimiento', 'Serie', 'Documento', 'Trabajador', 'Entrada', 'Salida', 'Usuario'], ';');
                $this->movementQuery()->orderBy('occurred_at')->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [$r->occurred_at->format('d/m/Y H:i:s'), $this->movementLabel($r->movement_type), $this->safe($r->variant->product->name), $this->safe($r->variant->sku), $this->safe($r->variant->product->unit), $this->safe($r->inventoryLot?->lot_number), $r->inventoryLot?->expiration_date?->format('d/m/Y'), $this->safe($r->serializedItem?->serial_number), $this->safe($r->dispatchNote?->number ?? $r->delivery?->number), $this->safe($r->delivery?->worker?->full_name), $r->quantity > 0 ? Quantity::format($r->quantity) : '', $r->quantity < 0 ? Quantity::format(abs((float) $r->quantity)) : '', $this->safe($r->creator?->login)], ';');
                    }
                });
            } elseif ($type === 'deliveries') {
                fputcsv($out, ['Fecha', 'Entrega', 'Documento trabajador', 'Trabajador', 'Área', 'Producto', 'SKU', 'Lotes', 'Cantidad', 'Unidad', 'Series', 'Estado'], ';');
                $this->deliveryQuery()->orderBy('id')->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        $lots = $r->lotAllocations->map(fn ($allocation) => $allocation->lot?->lot_number.($allocation->lot?->expiration_date ? ' ('.$allocation->lot->expiration_date->format('d/m/Y').')' : ''))->filter()->join(' | ');
                        fputcsv($out, [$r->delivery->delivery_date->format('d/m/Y'), $this->safe($r->delivery->number), $this->safe($r->delivery->worker->document), $this->safe($r->delivery->worker->full_name), $this->safe($r->delivery->worker->area), $this->safe($r->variant->product->name), $this->safe($r->variant->sku), $this->safe($lots), Quantity::format($r->quantity), $this->safe($r->variant->product->unit), $this->safe($r->serializedItems->pluck('serial_number')->join(', ')), $r->delivery->status], ';');
                    }
                });
            } else {
                $headers = ['Categoría', 'Código', 'Producto', 'SKU', 'Variante'];
                foreach ($reportAttributes as $attribute) {
                    $headers[] = $attribute->name;
                }
                if ($showSerialColumn) {
                    $headers[] = 'Series en almacén';
                }
                array_push($headers, 'Unidad', 'Control');
                if ($showExpiryColumn) {
                    array_push($headers, 'Lotes con saldo', 'Próximo vencimiento', 'Estado vencimiento');
                }
                array_push($headers, 'Stock actual', 'Stock mínimo', 'Estado stock');
                fputcsv($out, $headers, ';');
                $this->stockQuery()->orderBy('products.name')->chunk(500, function ($rows) use ($out, $reportAttributes, $showSerialColumn, $showExpiryColumn) {
                    foreach ($rows as $r) {
                        $stock = (float) ($r->stock ?? 0);
                        $data = [$this->safe($r->product->category->name), $this->safe($r->product->code), $this->safe($r->product->name), $this->safe($r->sku), $this->safe($r->name)];
                        foreach ($reportAttributes as $attribute) {
                            $data[] = $this->safe($this->attributeValue($r, $attribute));
                        }
                        if ($showSerialColumn) {
                            $data[] = $this->safe($this->availableSerials($r)->pluck('serial_number')->join(', '));
                        }
                        array_push($data, $this->safe($r->product->unit), $r->product->tracking_type === 'serialized' ? 'Por serie' : 'Por cantidad');
                        if ($showExpiryColumn) {
                            $lots = $this->availableLots($r);
                            array_push($data, $this->safe($lots->map(fn ($lot) => $lot->lot_number.' ('.Quantity::format($lot->stock).($lot->expiration_date ? ', '.$lot->expiration_date->format('d/m/Y') : '').')')->join(' | ')), $r->expiration_date ? Carbon::parse($r->expiration_date)->format('d/m/Y') : '', $this->expiryLabel($r->expiration_date));
                        }
                        array_push($data, Quantity::format($stock), Quantity::format($r->minimum_stock), $this->stockLabel($stock, (float) $r->minimum_stock));
                        fputcsv($out, $data, ';');
                    }
                });
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateReportDates(): void
    {
        if ($this->reportType === 'stock') {
            $this->validate([
                'expiryFrom' => ['nullable', 'date'],
                'expiryTo' => ['nullable', 'date', 'after_or_equal:expiryFrom'],
            ], ['expiryTo.after_or_equal' => 'La fecha final de vencimiento debe ser igual o posterior a la inicial.']);

            return;
        }
        $this->validate([
            'fromDate' => ['nullable', 'date'],
            'toDate' => ['nullable', 'date', 'after_or_equal:fromDate'],
        ], ['toDate.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.']);
    }

    private function stockQuery()
    {
        $term = '%'.trim($this->searchTerm).'%';
        $stock = '(SELECT COALESCE(SUM(im.quantity), 0) FROM inventory_movements im WHERE im.product_variant_id = product_variants.id)';
        $serialStock = '(SELECT COALESCE(SUM(sim.quantity), 0) FROM inventory_movements sim WHERE sim.serialized_item_id = serialized_items.id)';
        $expiryDate = $this->expiryDateExpression();
        $today = now()->toDateString();

        $query = ProductVariant::query()->with([
            'product.category',
            'product.attributeValues',
            'attributeValues',
            'serializedItems' => fn ($query) => $query->where('status', '!=', 'inactive')->withSum('inventoryMovements as inventory_balance', 'quantity'),
            'inventoryLots' => fn ($query) => $query->withSum('movements as stock', 'quantity')->orderByRaw('expiration_date IS NULL')->orderBy('expiration_date')->orderBy('lot_number'),
        ])->select('product_variants.*')->addSelect(DB::raw("{$expiryDate} AS expiration_date"))->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
            ->withSum('inventoryMovements as stock', 'quantity')
            ->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->where('products.name', 'like', $term)->orWhere('products.code', 'like', $term)->orWhere('product_variants.sku', 'like', $term)->orWhereHas('inventoryLots', fn ($lot) => $lot->where('lot_number', 'like', $term))->orWhereHas('serializedItems', fn ($serial) => $serial->where('serial_number', 'like', $term)->whereRaw("{$serialStock} > 0"))))
            ->when($this->categoryFilter, fn ($q) => $q->where('products.category_id', $this->categoryFilter))
            ->when($this->catalogStatus === 'active', fn ($q) => $q->where('products.status', true)->where('product_variants.status', true))
            ->when($this->catalogStatus === 'inactive', fn ($q) => $q->where(fn ($status) => $status->where('products.status', false)->orWhere('product_variants.status', false)))
            ->when($this->serialFilter, fn ($q) => $q->whereHas('serializedItems', fn ($serial) => $serial->where('serial_number', 'like', '%'.trim($this->serialFilter).'%')->whereRaw("{$serialStock} > 0")))
            ->when($this->trackingFilter, fn ($q) => $q->where('products.tracking_type', $this->trackingFilter))
            ->when($this->unitFilter, fn ($q) => $q->where('products.unit', $this->unitFilter))
            ->when($this->expiryFrom, fn ($q) => $q->whereRaw("{$expiryDate} >= ?", [$this->expiryFrom]))
            ->when($this->expiryTo, fn ($q) => $q->whereRaw("{$expiryDate} <= ?", [$this->expiryTo]))
            ->when($this->expiryStatus === 'expired', fn ($q) => $q->whereRaw("{$expiryDate} < ?", [$today]))
            ->when($this->expiryStatus === 'today', fn ($q) => $q->whereRaw("{$expiryDate} = ?", [$today]))
            ->when($this->expiryStatus === 'next30', fn ($q) => $q->whereRaw("{$expiryDate} BETWEEN ? AND ?", [$today, now()->addDays(30)->toDateString()]))
            ->when($this->expiryStatus === 'next90', fn ($q) => $q->whereRaw("{$expiryDate} BETWEEN ? AND ?", [$today, now()->addDays(90)->toDateString()]))
            ->when($this->expiryStatus === 'valid', fn ($q) => $q->whereRaw("{$expiryDate} > ?", [$today]))
            ->when($this->expiryStatus === 'without', fn ($q) => $q->whereRaw("{$expiryDate} IS NULL"))
            ->when($this->stockStatus === 'low', fn ($q) => $q->where('product_variants.minimum_stock', '>', 0)->whereRaw("$stock > 0 AND $stock <= product_variants.minimum_stock"))
            ->when($this->stockStatus === 'out', fn ($q) => $q->whereRaw("$stock <= 0"))
            ->when($this->stockStatus === 'available', fn ($q) => $q->whereRaw("$stock > 0"))
            ->when($this->stockStatus === 'normal', fn ($q) => $q->whereRaw("$stock > product_variants.minimum_stock"));

        return $this->applyVariantAttributeFilters($query);
    }

    private function movementQuery()
    {
        $term = '%'.trim($this->searchTerm).'%';

        $query = InventoryMovement::with(['variant.product.category', 'inventoryLot', 'serializedItem', 'dispatchNote', 'delivery.worker', 'creator'])
            ->when($this->fromDate, fn ($q) => $q->whereDate('occurred_at', '>=', $this->fromDate))->when($this->toDate, fn ($q) => $q->whereDate('occurred_at', '<=', $this->toDate))
            ->when($this->categoryFilter, fn ($q) => $q->whereHas('variant.product', fn ($p) => $p->where('category_id', $this->categoryFilter)))
            ->when($this->movementType, fn ($q) => $q->where('movement_type', $this->movementType))
            ->when($this->documentSource === 'dispatch', fn ($q) => $q->whereNotNull('dispatch_note_id'))
            ->when($this->documentSource === 'delivery', fn ($q) => $q->whereNotNull('delivery_id'))
            ->when($this->workerFilter, fn ($q) => $q->whereHas('delivery', fn ($d) => $d->where('worker_id', $this->workerFilter)))
            ->when($this->serialFilter, fn ($q) => $q->whereHas('serializedItem', fn ($serial) => $serial->where('serial_number', 'like', '%'.trim($this->serialFilter).'%')))
            ->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->whereHas('variant', fn ($v) => $v->where('sku', 'like', $term)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term)))->orWhereHas('inventoryLot', fn ($lot) => $lot->where('lot_number', 'like', $term))->orWhereHas('serializedItem', fn ($s) => $s->where('serial_number', 'like', $term))->orWhereHas('dispatchNote', fn ($d) => $d->where('number', 'like', $term))->orWhereHas('delivery', fn ($d) => $d->where('number', 'like', $term))->orWhereHas('delivery.worker', fn ($w) => $w->where('name', 'like', $term)->orWhere('lastname', 'like', $term)->orWhere('document', 'like', $term))));

        return $this->activeAttributeFilters()->isEmpty()
            ? $query
            : $query->whereHas('variant', fn ($variant) => $this->applyVariantAttributeFilters($variant));
    }

    private function deliveryQuery()
    {
        $term = '%'.trim($this->searchTerm).'%';

        $query = DeliveryItem::with(['delivery.worker', 'variant.product.category', 'serializedItems', 'lotAllocations.lot'])
            ->whereHas('delivery', fn ($d) => $d->when($this->deliveryStatus, fn ($q) => $q->where('status', $this->deliveryStatus))->when($this->fromDate, fn ($q) => $q->whereDate('delivery_date', '>=', $this->fromDate))->when($this->toDate, fn ($q) => $q->whereDate('delivery_date', '<=', $this->toDate))->when($this->areaFilter, fn ($q) => $q->whereHas('worker', fn ($w) => $w->where('area', $this->areaFilter))))
            ->when($this->workerFilter, fn ($q) => $q->whereHas('delivery', fn ($d) => $d->where('worker_id', $this->workerFilter)))
            ->when($this->categoryFilter, fn ($q) => $q->whereHas('variant.product', fn ($p) => $p->where('category_id', $this->categoryFilter)))
            ->when($this->serialFilter, fn ($q) => $q->whereHas('serializedItems', fn ($serial) => $serial->where('serial_number', 'like', '%'.trim($this->serialFilter).'%')))
            ->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->whereHas('delivery', fn ($d) => $d->where('number', 'like', $term))->orWhereHas('delivery.worker', fn ($w) => $w->where('name', 'like', $term)->orWhere('lastname', 'like', $term)->orWhere('document', 'like', $term))->orWhereHas('variant', fn ($v) => $v->where('sku', 'like', $term)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term)))));

        return $this->activeAttributeFilters()->isEmpty()
            ? $query
            : $query->whereHas('variant', fn ($variant) => $this->applyVariantAttributeFilters($variant));
    }

    private function activeFilters(): array
    {
        $filters = collect(['tipo' => $this->reportType, 'búsqueda' => $this->searchTerm, 'categoría' => $this->categoryFilter, 'serie' => $this->serialFilter, 'desde' => $this->fromDate, 'hasta' => $this->toDate, 'estado_stock' => $this->stockStatus, 'estado_catálogo' => $this->catalogStatus, 'control' => $this->trackingFilter, 'unidad' => $this->unitFilter, 'vencimiento_desde' => $this->expiryFrom, 'vencimiento_hasta' => $this->expiryTo, 'estado_vencimiento' => $this->expiryStatus, 'movimiento' => $this->movementType, 'origen' => $this->documentSource, 'estado_entrega' => $this->deliveryStatus, 'área' => $this->areaFilter, 'trabajador_id' => $this->workerFilter])->filter(fn ($value) => filled($value));

        foreach ($this->activeAttributeFilters() as $filter) {
            $filters->put('atributo_'.$filter['attribute']->code, $filter['value']);
        }

        return $filters->all();
    }

    private function selectedCategory(): ?Category
    {
        if (! $this->categoryFilter) {
            return null;
        }

        return Category::with(['attributes' => fn ($query) => $query->orderBy('category_product_attribute.position')])->find($this->categoryFilter);
    }

    private function activeAttributeFilters()
    {
        $category = $this->selectedCategory();
        if (! $category) {
            return collect();
        }

        return collect($this->attributeFilters)
            ->filter(fn ($value) => filled($value))
            ->map(function ($value, $attributeId) use ($category) {
                $attribute = $category->attributes->first(fn (ProductAttribute $item) => (int) $item->id === (int) $attributeId && $item->status && $item->scope !== 'unit' && ! $this->isExpirationAttribute($item));

                return $attribute ? ['attribute' => $attribute, 'value' => trim((string) $value)] : null;
            })
            ->filter()
            ->values();
    }

    private function applyVariantAttributeFilters($query)
    {
        foreach ($this->activeAttributeFilters() as $filter) {
            $attribute = $filter['attribute'];
            $value = $filter['value'];
            $relation = $attribute->scope === 'product' ? 'product.attributeValues' : 'attributeValues';
            $query->whereHas($relation, function ($attributeValue) use ($attribute, $value) {
                $attributeValue->where('product_attribute_id', $attribute->id);
                if ($attribute->type === 'text') {
                    $attributeValue->where('value', 'like', '%'.$value.'%');
                } else {
                    $attributeValue->where('value', $value);
                }
            });
        }

        return $query;
    }

    private function attributeValue(ProductVariant $variant, ProductAttribute $attribute): string
    {
        $values = $attribute->scope === 'product' ? $variant->product->attributeValues : $variant->attributeValues;
        $value = $values->firstWhere('product_attribute_id', $attribute->id)?->value;

        if ($attribute->type === 'boolean' && $value !== null && $value !== '') {
            return (string) $value === '1' ? 'Sí' : 'No';
        }
        if ($attribute->type === 'date' && filled($value)) {
            return Carbon::parse($value)->format('d/m/Y');
        }

        return (string) ($value ?? '');
    }

    private function availableSerials(ProductVariant $variant)
    {
        return $variant->serializedItems->filter(fn ($serial) => (float) ($serial->inventory_balance ?? 0) > 0)->values();
    }

    public function availableLots(ProductVariant $variant)
    {
        return $variant->inventoryLots->filter(fn ($lot) => (float) ($lot->stock ?? 0) > 0.0005)->values();
    }

    private function isExpirationAttribute(ProductAttribute $attribute): bool
    {
        if ($attribute->type !== 'date') {
            return false;
        }

        $identity = mb_strtolower($attribute->code.' '.$attribute->name);

        return str_contains($identity, 'venc') || str_contains($identity, 'caduc') || str_contains($identity, 'expir');
    }

    private function workerResults()
    {
        if (mb_strlen(trim($this->workerSearch)) < 2 || $this->workerFilter) {
            return collect();
        }
        $term = '%'.trim($this->workerSearch).'%';

        return Worker::where(fn ($q) => $q->where('name', 'like', $term)->orWhere('lastname', 'like', $term)->orWhere('document', 'like', $term))
            ->orderBy('lastname')->orderBy('name')->limit(12)->get();
    }

    public function movementLabel(string $type): string
    {
        return match ($type) {
            'dispatch_entry' => 'Ingreso', 'dispatch_exit' => 'Salida por remito', 'dispatch_correction' => 'Corrección de remito', 'annulment' => 'Anulación de remito', 'delivery' => 'Entrega', 'delivery_correction' => 'Corrección de entrega', 'delivery_annulment' => 'Anulación de entrega', default => $type
        };
    }

    public function stockLabel(float $stock, float $minimum): string
    {
        return $stock <= 0 ? 'Agotado' : ($minimum > 0 && $stock <= $minimum ? 'Stock bajo' : 'Normal');
    }

    public function expiryLabel(?string $date): string
    {
        if (! $date) {
            return 'Sin fecha';
        }

        $expiration = Carbon::parse($date)->startOfDay();
        $today = now()->startOfDay();

        if ($expiration->lt($today)) {
            return 'Vencido';
        }
        if ($expiration->equalTo($today)) {
            return 'Vence hoy';
        }

        return $expiration->lte($today->copy()->addDays(30)) ? 'Por vencer' : 'Vigente';
    }

    public function expiryBadge(?string $date): string
    {
        return match ($this->expiryLabel($date)) {
            'Vencido' => 'danger',
            'Vence hoy' => 'warning',
            'Por vencer' => 'warning',
            'Vigente' => 'success',
            default => 'secondary',
        };
    }

    private function expiryDateExpression(): string
    {
        $expirationAttribute = "pa.type = 'date' AND (LOWER(pa.code) LIKE '%venc%' OR LOWER(pa.name) LIKE '%venc%' OR LOWER(pa.code) LIKE '%caduc%' OR LOWER(pa.name) LIKE '%caduc%' OR LOWER(pa.code) LIKE '%expir%' OR LOWER(pa.name) LIKE '%expir%')";

        return "COALESCE(
            (SELECT MIN(il.expiration_date) FROM inventory_lots il WHERE il.product_variant_id = product_variants.id AND il.expiration_date IS NOT NULL AND (SELECT COALESCE(SUM(lm.quantity), 0) FROM inventory_movements lm WHERE lm.inventory_lot_id = il.id) > 0),
            (SELECT MIN(vav.value) FROM variant_attribute_values vav INNER JOIN product_attributes pa ON pa.id = vav.product_attribute_id WHERE vav.product_variant_id = product_variants.id AND {$expirationAttribute} AND vav.value <> ''),
            (SELECT MIN(pav.value) FROM product_attribute_values pav INNER JOIN product_attributes pa ON pa.id = pav.product_attribute_id WHERE pav.product_id = products.id AND {$expirationAttribute} AND pav.value <> '')
        )";
    }

    private function safe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
