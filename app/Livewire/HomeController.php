<?php

namespace App\Livewire;

use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Models\InventoryMovement;
use App\Models\Log;
use App\Models\ProductVariant;
use App\Models\SerializedItem;
use App\Models\User;
use App\Models\Worker;
use App\Services\CodeGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class HomeController extends Component
{
    public function render()
    {
        $user = auth()->user();
        $canViewInventory = $user->can('ver-inventario');
        $canViewDispatchNotes = $user->can('ver-remito');
        $canViewDeliveries = $user->can('ver-entrega');

        $inventory = $canViewInventory ? $this->inventorySummary() : $this->emptyInventorySummary();
        $operations = $this->operationSummary($canViewDispatchNotes, $canViewDeliveries);

        return view('livewire.home.home', [
            ...$inventory,
            ...$operations,
            'quickActions' => $this->quickActions(),
            'systemMetrics' => $this->systemMetrics(),
            'recentLogs' => $user->can('ver-log') ? Log::with('user')->latest()->limit(6)->get() : collect(),
            'canViewLogs' => $user->can('ver-log'),
            'hasWarehouseAccess' => $canViewInventory || $canViewDispatchNotes || $canViewDeliveries || $user->can('ver-trabajador'),
            'todayLabel' => ucfirst(now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y')),
            'siteCode' => app(CodeGenerator::class)->siteCode(),
        ])->extends('layouts.theme.app');
    }

    public function movementLabel(string $type): string
    {
        return match ($type) {
            'dispatch_entry' => 'Ingreso por remito',
            'dispatch_exit' => 'Salida por remito',
            'dispatch_correction' => 'Corrección de remito',
            'annulment' => 'Anulación de remito',
            'delivery' => 'Entrega a trabajador',
            'delivery_correction' => 'Corrección de entrega',
            'delivery_annulment' => 'Anulación de entrega',
            default => $type,
        };
    }

    public function movementTone(string $type): string
    {
        return match ($type) {
            'dispatch_entry', 'delivery_annulment' => 'success',
            'dispatch_exit', 'delivery' => 'primary',
            'annulment' => 'danger',
            default => 'warning',
        };
    }

    private function inventorySummary(): array
    {
        $stockExpression = $this->stockExpression();
        $base = ProductVariant::query()
            ->where('product_variants.status', true)
            ->whereHas('product', fn ($query) => $query->where('status', true));

        $total = (clone $base)->count();
        $withStock = (clone $base)->whereRaw("{$stockExpression} > 0")->count();
        $outOfStock = (clone $base)->whereRaw("{$stockExpression} <= 0")->count();
        $lowStock = (clone $base)
            ->where('minimum_stock', '>', 0)
            ->whereRaw("{$stockExpression} > 0 AND {$stockExpression} <= minimum_stock")
            ->count();
        $healthy = max(0, $total - $outOfStock - $lowStock);

        $availableAssets = SerializedItem::query()
            ->where('status', 'available')
            ->whereHas('variant', fn ($query) => $query->where('status', true)->whereHas('product', fn ($product) => $product->where('status', true)))
            ->count();
        $assignedAssets = SerializedItem::query()
            ->where('status', 'assigned')
            ->whereHas('variant', fn ($query) => $query->where('status', true)->whereHas('product', fn ($product) => $product->where('status', true)))
            ->count();

        $criticalStock = (clone $base)
            ->with('product.category')
            ->withSum('inventoryMovements as stock', 'quantity')
            ->where(fn ($query) => $query
                ->whereRaw("{$stockExpression} <= 0")
                ->orWhere(fn ($low) => $low->where('minimum_stock', '>', 0)->whereRaw("{$stockExpression} > 0 AND {$stockExpression} <= minimum_stock")))
            ->orderByRaw("CASE WHEN {$stockExpression} <= 0 THEN 0 ELSE 1 END")
            ->orderByRaw("{$stockExpression} - minimum_stock")
            ->limit(6)
            ->get();

        $recentMovements = InventoryMovement::with(['variant.product', 'serializedItem', 'dispatchNote', 'delivery.worker', 'creator'])
            ->latest('occurred_at')
            ->latest('id')
            ->limit(7)
            ->get();

        $expiry = $this->expirySummary($stockExpression);

        return [
            'warehouseMetrics' => [
                ['label' => 'Referencias con stock', 'value' => $withStock, 'caption' => 'de '.$total.' activas', 'icon' => 'bx-package', 'tone' => 'primary', 'route' => route('inventory')],
                ['label' => 'Productos agotados', 'value' => $outOfStock, 'caption' => $outOfStock ? 'requieren reposición' : 'sin alertas', 'icon' => 'bx-error-circle', 'tone' => 'danger', 'route' => route('inventory')],
                ['label' => 'Stock bajo', 'value' => $lowStock, 'caption' => $lowStock ? 'bajo el mínimo' : 'niveles correctos', 'icon' => 'bx-down-arrow-circle', 'tone' => 'warning', 'route' => route('inventory')],
                ['label' => 'Activos disponibles', 'value' => $availableAssets, 'caption' => $assignedAssets.' asignados', 'icon' => 'bx-laptop', 'tone' => 'success', 'route' => route('inventory')],
            ],
            'stockOverview' => [
                'total' => $total,
                'healthy' => $healthy,
                'low' => $lowStock,
                'out' => $outOfStock,
                'healthyPercent' => $total ? round(($healthy / $total) * 100) : 0,
                'lowPercent' => $total ? round(($lowStock / $total) * 100) : 0,
                'outPercent' => $total ? max(0, 100 - round(($healthy / $total) * 100) - round(($lowStock / $total) * 100)) : 0,
            ],
            'criticalStock' => $criticalStock,
            'recentMovements' => $recentMovements,
            ...$expiry,
        ];
    }

    private function operationSummary(bool $canViewDispatchNotes, bool $canViewDeliveries): array
    {
        $metrics = [];
        $documents = collect();

        if ($canViewDispatchNotes) {
            $entriesToday = DispatchNote::where('status', 'confirmed')->where('type', 'entry')->whereDate('document_date', today())->count();
            $drafts = DispatchNote::where('status', 'draft')->count();
            $metrics[] = ['label' => 'Ingresos de hoy', 'value' => $entriesToday, 'caption' => $drafts.' borrador(es)', 'icon' => 'bx-log-in-circle', 'tone' => 'info', 'route' => route('dispatch-notes')];

            $documents = $documents->concat(DispatchNote::withCount('items')->latest('updated_at')->limit(5)->get()->map(fn (DispatchNote $note) => [
                'kind' => $note->type === 'entry' ? 'Remito de ingreso' : 'Remito de salida',
                'number' => $note->number ?: 'BORRADOR #'.$note->id,
                'subject' => $note->counterparty,
                'status' => $note->status,
                'items' => $note->items_count,
                'date' => $note->updated_at,
                'route' => route('dispatch-notes'),
                'icon' => $note->type === 'entry' ? 'bx-log-in' : 'bx-log-out',
            ]));
        }

        if ($canViewDeliveries) {
            $deliveriesToday = Delivery::where('status', 'confirmed')->whereDate('delivery_date', today())->count();
            $deliveryDrafts = Delivery::where('status', 'draft')->count();
            $workersToday = Delivery::where('status', 'confirmed')->whereDate('delivery_date', today())->distinct('worker_id')->count('worker_id');
            $metrics[] = ['label' => 'Entregas de hoy', 'value' => $deliveriesToday, 'caption' => $deliveryDrafts.' borrador(es)', 'icon' => 'bx-package', 'tone' => 'success', 'route' => route('deliveries')];
            $metrics[] = ['label' => 'Trabajadores atendidos', 'value' => $workersToday, 'caption' => 'durante la jornada', 'icon' => 'bx-group', 'tone' => 'purple', 'route' => route('deliveries')];

            $documents = $documents->concat(Delivery::with(['worker'])->withCount('items')->latest('updated_at')->limit(5)->get()->map(fn (Delivery $delivery) => [
                'kind' => 'Entrega a trabajador',
                'number' => $delivery->number ?: 'BORRADOR #'.$delivery->id,
                'subject' => $delivery->worker?->full_name,
                'status' => $delivery->status,
                'items' => $delivery->items_count,
                'date' => $delivery->updated_at,
                'route' => route('deliveries'),
                'icon' => 'bx-user-check',
            ]));
        }

        if (auth()->user()->can('ver-inventario')) {
            $movementsToday = InventoryMovement::whereDate('occurred_at', today())->count();
            $metrics[] = ['label' => 'Movimientos de hoy', 'value' => $movementsToday, 'caption' => 'registrados en Kardex', 'icon' => 'bx-transfer-alt', 'tone' => 'primary', 'route' => route('inventory')];
        }

        return [
            'operationMetrics' => $metrics,
            'recentDocuments' => $documents->sortByDesc('date')->take(7)->values(),
        ];
    }

    private function expirySummary(string $stockExpression): array
    {
        $expiryExpression = $this->expiryDateExpression();
        $today = now()->toDateString();
        $nextThirtyDays = now()->addDays(30)->toDateString();
        $nextNinetyDays = now()->addDays(90)->toDateString();

        $base = ProductVariant::query()
            ->select('product_variants.*')
            ->addSelect(DB::raw("{$expiryExpression} AS expiration_date"))
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('product_variants.status', true)
            ->where('products.status', true)
            ->whereRaw("{$stockExpression} > 0")
            ->whereRaw("{$expiryExpression} IS NOT NULL");

        $expired = (clone $base)->whereRaw("{$expiryExpression} < ?", [$today])->count();
        $expiringSoon = (clone $base)->whereRaw("{$expiryExpression} BETWEEN ? AND ?", [$today, $nextThirtyDays])->count();
        $alerts = (clone $base)
            ->with('product.category')
            ->withSum('inventoryMovements as stock', 'quantity')
            ->whereRaw("{$expiryExpression} <= ?", [$nextNinetyDays])
            ->orderByRaw("{$expiryExpression} ASC")
            ->limit(5)
            ->get()
            ->each(function (ProductVariant $variant): void {
                $variant->days_to_expiry = now()->startOfDay()->diffInDays(Carbon::parse($variant->expiration_date)->startOfDay(), false);
            });

        return [
            'expirySummary' => ['expired' => $expired, 'soon' => $expiringSoon],
            'expiryAlerts' => $alerts,
        ];
    }

    private function quickActions()
    {
        $user = auth()->user();

        return collect([
            ['permission' => 'crear-remito', 'label' => 'Nuevo remito', 'caption' => 'Registrar ingreso o salida', 'icon' => 'bx-transfer', 'tone' => 'primary', 'route' => route('dispatch-notes')],
            ['permission' => 'crear-entrega', 'label' => 'Nueva entrega', 'caption' => 'Asignar productos', 'icon' => 'bx-user-check', 'tone' => 'success', 'route' => route('deliveries')],
            ['permission' => 'crear-producto', 'label' => 'Nuevo producto', 'caption' => 'Ampliar el catálogo', 'icon' => 'bx-package', 'tone' => 'info', 'route' => route('products')],
            ['permission' => 'ver-inventario', 'label' => 'Consultar Kardex', 'caption' => 'Revisar existencias', 'icon' => 'bx-bar-chart-alt-2', 'tone' => 'warning', 'route' => route('inventory')],
            ['permission' => 'ver-reporte', 'label' => 'Abrir reportes', 'caption' => 'Analizar información', 'icon' => 'bx-file', 'tone' => 'purple', 'route' => route('reports')],
        ])->filter(fn (array $action) => $user->can($action['permission']))->values();
    }

    private function systemMetrics(): array
    {
        $user = auth()->user();
        $metrics = [];

        if ($user->can('ver-trabajador')) {
            $metrics[] = ['label' => 'Trabajadores activos', 'value' => Worker::where('status', true)->count(), 'icon' => 'bx-group'];
        }
        if ($user->can('ver-usuario')) {
            $metrics[] = ['label' => 'Usuarios activos', 'value' => User::where('status', true)->count(), 'icon' => 'bx-user-check'];
        }
        if ($user->can('ver-rol')) {
            $metrics[] = ['label' => 'Roles configurados', 'value' => Role::where('status', true)->count(), 'icon' => 'bx-shield-quarter'];
        }
        if ($user->can('ver-log')) {
            $metrics[] = ['label' => 'Acciones de hoy', 'value' => Log::whereDate('created_at', today())->count(), 'icon' => 'bx-history'];
        }

        return $metrics;
    }

    private function emptyInventorySummary(): array
    {
        return [
            'warehouseMetrics' => [],
            'stockOverview' => null,
            'criticalStock' => collect(),
            'recentMovements' => collect(),
            'expirySummary' => ['expired' => 0, 'soon' => 0],
            'expiryAlerts' => collect(),
        ];
    }

    private function stockExpression(): string
    {
        return '(SELECT COALESCE(SUM(im.quantity), 0) FROM inventory_movements im WHERE im.product_variant_id = product_variants.id)';
    }

    private function expiryDateExpression(): string
    {
        $expirationAttribute = "pa.type = 'date' AND (LOWER(pa.code) LIKE '%venc%' OR LOWER(pa.name) LIKE '%venc%' OR LOWER(pa.code) LIKE '%caduc%' OR LOWER(pa.name) LIKE '%caduc%' OR LOWER(pa.code) LIKE '%expir%' OR LOWER(pa.name) LIKE '%expir%')";

        return "COALESCE(
            (SELECT MIN(vav.value) FROM variant_attribute_values vav INNER JOIN product_attributes pa ON pa.id = vav.product_attribute_id WHERE vav.product_variant_id = product_variants.id AND {$expirationAttribute} AND vav.value <> ''),
            (SELECT MIN(pav.value) FROM product_attribute_values pav INNER JOIN product_attributes pa ON pa.id = pav.product_attribute_id WHERE pav.product_id = products.id AND {$expirationAttribute} AND pav.value <> '')
        )";
    }
}
