<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\DeliveryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Worker;
use App\Traits\AuditLog;
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

    public $trackingFilter = '';

    public $unitFilter = '';

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
        $rows = match ($this->reportType) {
            'movements' => $this->movementQuery()->latest('occurred_at')->paginate(30),
            'deliveries' => $this->deliveryQuery()->latest('id')->paginate(30),
            default => $this->stockQuery()->orderBy('products.name')->orderBy('product_variants.sku')->paginate(30),
        };

        return view('livewire.reports.reports', [
            'rows' => $rows,
            'categories' => Category::where('status', true)->orderBy('name')->get(),
            'units' => Product::whereNotNull('unit')->distinct()->orderBy('unit')->pluck('unit'),
            'areas' => Worker::whereNotNull('area')->where('area', '!=', '')->distinct()->orderBy('area')->pluck('area'),
            'workerResults' => $this->workerResults(),
            'selectedWorker' => $this->workerFilter ? Worker::find($this->workerFilter) : null,
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
        if ($property === 'reportType') {
            $this->searchTerm = '';
            $this->categoryFilter = '';
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
        $this->reset(['searchTerm', 'categoryFilter', 'stockStatus', 'trackingFilter', 'unitFilter', 'movementType', 'documentSource', 'areaFilter', 'workerFilter', 'workerSearch']);
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
        $this->logActivity('REPORTES', 'EXPORTAR', 'Exportación CSV del reporte '.$type, null, null, $this->activeFilters());

        return response()->streamDownload(function () use ($type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            if ($type === 'movements') {
                fputcsv($out, ['Fecha', 'Movimiento', 'Producto', 'SKU', 'Unidad', 'Serie', 'Documento', 'Trabajador', 'Entrada', 'Salida', 'Usuario'], ';');
                $this->movementQuery()->orderBy('occurred_at')->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [$r->occurred_at->format('d/m/Y H:i:s'), $this->movementLabel($r->movement_type), $this->safe($r->variant->product->name), $this->safe($r->variant->sku), $this->safe($r->variant->product->unit), $this->safe($r->serializedItem?->serial_number), $this->safe($r->dispatchNote?->number ?? $r->delivery?->number), $this->safe($r->delivery?->worker?->full_name), $r->quantity > 0 ? (float) $r->quantity : '', $r->quantity < 0 ? abs((float) $r->quantity) : '', $this->safe($r->creator?->login)], ';');
                    }
                });
            } elseif ($type === 'deliveries') {
                fputcsv($out, ['Fecha', 'Entrega', 'Documento trabajador', 'Trabajador', 'Área', 'Producto', 'SKU', 'Cantidad', 'Unidad', 'Series', 'Estado'], ';');
                $this->deliveryQuery()->orderBy('id')->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [$r->delivery->delivery_date->format('d/m/Y'), $this->safe($r->delivery->number), $this->safe($r->delivery->worker->document), $this->safe($r->delivery->worker->full_name), $this->safe($r->delivery->worker->area), $this->safe($r->variant->product->name), $this->safe($r->variant->sku), (float) $r->quantity, $this->safe($r->variant->product->unit), $this->safe($r->serializedItems->pluck('serial_number')->join(', ')), $r->delivery->status], ';');
                    }
                });
            } else {
                fputcsv($out, ['Categoría', 'Código', 'Producto', 'SKU', 'Variante', 'Unidad', 'Control', 'Stock actual', 'Stock mínimo', 'Estado'], ';');
                $this->stockQuery()->orderBy('products.name')->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        $stock = (float) ($r->stock ?? 0);
                        fputcsv($out, [$this->safe($r->product->category->name), $this->safe($r->product->code), $this->safe($r->product->name), $this->safe($r->sku), $this->safe($r->name), $this->safe($r->product->unit), $r->product->tracking_type === 'serialized' ? 'Por serie' : 'Por cantidad', $stock, (float) $r->minimum_stock, $this->stockLabel($stock, (float) $r->minimum_stock)], ';');
                    }
                });
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateReportDates(): void
    {
        if ($this->reportType === 'stock') {
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

        return ProductVariant::query()->with('product.category')->select('product_variants.*')->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
            ->withSum('inventoryMovements as stock', 'quantity')
            ->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->where('products.name', 'like', $term)->orWhere('products.code', 'like', $term)->orWhere('product_variants.sku', 'like', $term)))
            ->when($this->categoryFilter, fn ($q) => $q->where('products.category_id', $this->categoryFilter))
            ->when($this->trackingFilter, fn ($q) => $q->where('products.tracking_type', $this->trackingFilter))
            ->when($this->unitFilter, fn ($q) => $q->where('products.unit', $this->unitFilter))
            ->when($this->stockStatus === 'low', fn ($q) => $q->where('product_variants.minimum_stock', '>', 0)->whereRaw("$stock > 0 AND $stock <= product_variants.minimum_stock"))
            ->when($this->stockStatus === 'out', fn ($q) => $q->whereRaw("$stock <= 0"))
            ->when($this->stockStatus === 'available', fn ($q) => $q->whereRaw("$stock > 0"))
            ->when($this->stockStatus === 'normal', fn ($q) => $q->whereRaw("$stock > product_variants.minimum_stock"));
    }

    private function movementQuery()
    {
        $term = '%'.trim($this->searchTerm).'%';

        return InventoryMovement::with(['variant.product.category', 'serializedItem', 'dispatchNote', 'delivery.worker', 'creator'])
            ->when($this->fromDate, fn ($q) => $q->whereDate('occurred_at', '>=', $this->fromDate))->when($this->toDate, fn ($q) => $q->whereDate('occurred_at', '<=', $this->toDate))
            ->when($this->categoryFilter, fn ($q) => $q->whereHas('variant.product', fn ($p) => $p->where('category_id', $this->categoryFilter)))
            ->when($this->movementType, fn ($q) => $q->where('movement_type', $this->movementType))
            ->when($this->documentSource === 'dispatch', fn ($q) => $q->whereNotNull('dispatch_note_id'))
            ->when($this->documentSource === 'delivery', fn ($q) => $q->whereNotNull('delivery_id'))
            ->when($this->workerFilter, fn ($q) => $q->whereHas('delivery', fn ($d) => $d->where('worker_id', $this->workerFilter)))
            ->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->whereHas('variant', fn ($v) => $v->where('sku', 'like', $term)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term)))->orWhereHas('serializedItem', fn ($s) => $s->where('serial_number', 'like', $term))->orWhereHas('dispatchNote', fn ($d) => $d->where('number', 'like', $term))->orWhereHas('delivery', fn ($d) => $d->where('number', 'like', $term))->orWhereHas('delivery.worker', fn ($w) => $w->where('name', 'like', $term)->orWhere('lastname', 'like', $term)->orWhere('document', 'like', $term))));
    }

    private function deliveryQuery()
    {
        $term = '%'.trim($this->searchTerm).'%';

        return DeliveryItem::with(['delivery.worker', 'variant.product.category', 'serializedItems'])
            ->whereHas('delivery', fn ($d) => $d->when($this->deliveryStatus, fn ($q) => $q->where('status', $this->deliveryStatus))->when($this->fromDate, fn ($q) => $q->whereDate('delivery_date', '>=', $this->fromDate))->when($this->toDate, fn ($q) => $q->whereDate('delivery_date', '<=', $this->toDate))->when($this->areaFilter, fn ($q) => $q->whereHas('worker', fn ($w) => $w->where('area', $this->areaFilter))))
            ->when($this->workerFilter, fn ($q) => $q->whereHas('delivery', fn ($d) => $d->where('worker_id', $this->workerFilter)))
            ->when($this->categoryFilter, fn ($q) => $q->whereHas('variant.product', fn ($p) => $p->where('category_id', $this->categoryFilter)))
            ->when($this->searchTerm, fn ($q) => $q->where(fn ($x) => $x->whereHas('delivery', fn ($d) => $d->where('number', 'like', $term))->orWhereHas('delivery.worker', fn ($w) => $w->where('name', 'like', $term)->orWhere('lastname', 'like', $term)->orWhere('document', 'like', $term))->orWhereHas('variant', fn ($v) => $v->where('sku', 'like', $term)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term)))));
    }

    private function activeFilters(): array
    {
        return collect(['tipo' => $this->reportType, 'búsqueda' => $this->searchTerm, 'categoría' => $this->categoryFilter, 'desde' => $this->fromDate, 'hasta' => $this->toDate, 'estado_stock' => $this->stockStatus, 'control' => $this->trackingFilter, 'unidad' => $this->unitFilter, 'movimiento' => $this->movementType, 'origen' => $this->documentSource, 'estado_entrega' => $this->deliveryStatus, 'área' => $this->areaFilter, 'trabajador_id' => $this->workerFilter])->filter(fn ($value) => filled($value))->all();
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

    private function safe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
