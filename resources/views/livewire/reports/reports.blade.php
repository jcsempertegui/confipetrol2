<div class="page-content reports-page">
<style>
@media print{@page{size:A4 landscape;margin:12mm}.sidebar-wrapper,.topbar,.report-controls,.page-footer,.no-print{display:none!important}.page-wrapper{margin:0!important}.page-content{padding:0!important}.reports-page{font-family:Arial,sans-serif;color:#17202a}.print-brand{display:flex!important;border-bottom:3px solid #0057a7;margin-bottom:14px;padding-bottom:10px}.card{box-shadow:none!important;border:0!important}.card-header{background:#fff!important;padding:8px 0!important}.table{font-size:9px;border-collapse:collapse}.table thead{display:table-header-group}.table th{background:#0057a7!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.table td,.table th{border:1px solid #b8c4cf!important;padding:5px!important}.badge{border:1px solid #555;color:#111!important;background:#fff!important}.table-responsive{overflow:visible!important}}
.report-tabs .btn{white-space:normal}.active-filter{border-color:#0d6efd!important;background:#f1f6ff}
@media(max-width:575.98px){.report-tabs{display:flex;flex-direction:column}.report-tabs>.btn{width:100%;border-radius:.375rem!important;margin-bottom:.35rem}}
</style>

<div class="module-header">
    <div><h4 class="mb-1">Reportes</h4><p class="text-muted mb-0">Consulta, filtra y exporta la información operativa del almacén.</p></div>
    <div class="d-flex gap-2 no-print">
        @can('exportar-reporte')<button wire:click="exportCsv" wire:loading.attr="disabled" class="btn btn-success"><i class="bx bx-spreadsheet me-1"></i>Exportar CSV</button>@endcan
        <button onclick="window.print()" class="btn btn-outline-primary"><i class="bx bx-printer me-1"></i>Imprimir / PDF</button>
    </div>
</div>
<div class="print-brand d-none justify-content-between align-items-end"><div><div style="font-size:24px;font-weight:700;color:#0057a7">CONFIPETROL</div><div>Reporte oficial del sistema de almacén</div></div><div class="text-end"><strong>{{ ['stock'=>'Inventario y alertas','movements'=>'Movimientos y Kardex','deliveries'=>'Entregas por trabajador'][$reportType] }}</strong><br><small>Generado {{ now()->format('d/m/Y H:i:s') }} por {{ auth()->user()->login }}</small></div></div>

<div class="card report-controls">
    <div class="card-body">
        <div class="btn-group w-100 mb-3 report-tabs" role="group">
            <button wire:click="$set('reportType','stock')" class="btn btn-{{ $reportType==='stock'?'primary':'outline-primary' }}">Inventario y mínimos</button>
            <button wire:click="$set('reportType','movements')" class="btn btn-{{ $reportType==='movements'?'primary':'outline-primary' }}">Movimientos</button>
            <button wire:click="$set('reportType','deliveries')" class="btn btn-{{ $reportType==='deliveries'?'primary':'outline-primary' }}">Entregas por trabajador</button>
        </div>

        <div class="row g-2">
            <div class="col-lg-5"><label class="filter-label">Búsqueda general</label><input wire:model.live.debounce.350ms="searchTerm" class="form-control" placeholder="Producto, código, SKU, serie, documento o trabajador"></div>
            <div class="col-lg-3"><label class="filter-label">Categoría</label><select wire:model.live="categoryFilter" class="form-select"><option value="">Todas las categorías</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>

            @if($reportType === 'stock')
                <div class="col-sm-4 col-lg-2"><label class="filter-label">Estado del stock</label><select wire:model.live="stockStatus" class="form-select"><option value="">Todos</option><option value="low">Stock bajo</option><option value="out">Agotados</option><option value="available">Con existencia</option><option value="normal">Sobre el mínimo</option></select></div>
                <div class="col-sm-4 col-lg-2"><label class="filter-label">Tipo de control</label><select wire:model.live="trackingFilter" class="form-select"><option value="">Todos</option><option value="bulk">Por cantidad</option><option value="serialized">Por serie</option></select></div>
                <div class="col-sm-4 col-lg-3"><label class="filter-label">Unidad</label><select wire:model.live="unitFilter" class="form-select"><option value="">Todas las unidades</option>@foreach($units as $unit)<option value="{{ $unit }}">{{ ucfirst($unit) }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="filter-label">Estado de vencimiento</label><select wire:model.live="expiryStatus" class="form-select"><option value="">Todos</option><option value="expired">Vencidos</option><option value="today">Vencen hoy</option><option value="next30">Vencen en los próximos 30 días</option><option value="next90">Vencen en los próximos 90 días</option><option value="valid">Vigentes</option><option value="without">Sin fecha de vencimiento</option></select></div>
                <div class="col-6 col-lg-2"><label class="filter-label">Vence desde</label><input type="date" wire:model.live="expiryFrom" class="form-control @error('expiryFrom') is-invalid @enderror">@error('expiryFrom')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-6 col-lg-2"><label class="filter-label">Vence hasta</label><input type="date" wire:model.live="expiryTo" class="form-control @error('expiryTo') is-invalid @enderror">@error('expiryTo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @else
                <div class="col-6 col-lg-2"><label class="filter-label">Desde</label><input type="date" wire:model.live="fromDate" class="form-control @error('fromDate') is-invalid @enderror">@error('fromDate')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-6 col-lg-2"><label class="filter-label">Hasta</label><input type="date" wire:model.live="toDate" class="form-control @error('toDate') is-invalid @enderror">@error('toDate')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6 col-lg-4 position-relative">
                    <label class="filter-label">Trabajador específico</label>
                    @if($selectedWorker)
                        <div class="form-control d-flex justify-content-between align-items-center active-filter"><span><strong>{{ $selectedWorker->full_name }}</strong> · {{ $selectedWorker->document }}</span><button type="button" wire:click="clearWorker" class="btn btn-sm btn-link text-danger p-0" title="Quitar trabajador"><i class="bx bx-x fs-5"></i></button></div>
                    @else
                        <input wire:model.live.debounce.300ms="workerSearch" class="form-control" placeholder="Escriba nombre, apellido o documento">
                        @if(mb_strlen(trim($workerSearch)) >= 2)
                            <div class="position-absolute start-0 end-0 bg-white border rounded shadow-sm mt-1" style="z-index:20;max-height:250px;overflow:auto">
                                @forelse($workerResults as $worker)<button type="button" wire:click="selectWorker({{ $worker->id }})" class="btn btn-light text-start w-100 rounded-0 border-bottom"><strong>{{ $worker->full_name }}</strong><div class="small text-muted">{{ $worker->document }}{{ $worker->area ? ' · '.$worker->area : '' }}</div></button>@empty<div class="p-3 text-muted small">No se encontraron trabajadores.</div>@endforelse
                            </div>
                        @endif
                    @endif
                </div>
                @if($reportType === 'movements')
                    <div class="col-md-4 col-lg-3"><label class="filter-label">Tipo de movimiento</label><select wire:model.live="movementType" class="form-select"><option value="">Todos los movimientos</option><option value="dispatch_entry">Ingresos</option><option value="dispatch_exit">Salidas por remito</option><option value="dispatch_correction">Correcciones de remito</option><option value="delivery">Entregas</option><option value="delivery_correction">Correcciones de entrega</option><option value="annulment">Anulación de remito</option><option value="delivery_annulment">Anulación de entrega</option></select></div>
                    <div class="col-md-4 col-lg-3"><label class="filter-label">Documento origen</label><select wire:model.live="documentSource" class="form-select"><option value="">Todos los documentos</option><option value="dispatch">Remitos</option><option value="delivery">Entregas</option></select></div>
                @else
                    <div class="col-md-4 col-lg-3"><label class="filter-label">Estado de entrega</label><select wire:model.live="deliveryStatus" class="form-select"><option value="">Todos los estados</option><option value="confirmed">Confirmadas</option><option value="annulled">Anuladas</option><option value="draft">Borradores</option></select></div>
                    <div class="col-md-4 col-lg-3"><label class="filter-label">Área del trabajador</label><select wire:model.live="areaFilter" class="form-select"><option value="">Todas las áreas</option>@foreach($areas as $area)<option value="{{ $area }}">{{ $area }}</option>@endforeach</select></div>
                @endif
            @endif
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 no-print">
            @if($reportType !== 'stock')
                <span class="small text-muted">Periodo rápido:</span>
                <button wire:click="setPeriod('today')" class="btn btn-sm btn-outline-secondary">Hoy</button><button wire:click="setPeriod('week')" class="btn btn-sm btn-outline-secondary">Semana</button><button wire:click="setPeriod('month')" class="btn btn-sm btn-outline-secondary">Mes</button><button wire:click="setPeriod('year')" class="btn btn-sm btn-outline-secondary">Año</button><button wire:click="setPeriod('all')" class="btn btn-sm btn-outline-secondary">Todo</button>
            @endif
            <button wire:click="clearFilters" class="btn btn-sm btn-outline-danger ms-auto"><i class="bx bx-reset me-1"></i>Limpiar filtros</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between gap-2">
        <div><strong>{{ ['stock'=>'Reporte de inventario y alertas','movements'=>'Reporte de movimientos y Kardex','deliveries'=>'Reporte de entregas y asignaciones'][$reportType] }}</strong><div class="small text-muted">{{ number_format($rows->total()) }} resultado(s) encontrados</div></div>
        <div class="small text-muted">Generado: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
    <div class="table-responsive"><table class="table table-striped align-middle mb-0">
    @if($reportType === 'stock')
        <thead><tr><th>Categoría</th><th>Producto</th><th>SKU / variante</th><th>Unidad</th><th>Control</th><th>Vencimiento</th><th class="text-end">Stock</th><th class="text-end">Mínimo</th><th>Estado</th></tr></thead>
        <tbody>@forelse($rows as $r)@php $stock=(float)($r->stock??0);$label=$this->stockLabel($stock,(float)$r->minimum_stock);$expiryLabel=$this->expiryLabel($r->expiration_date);@endphp<tr><td>{{ $r->product->category->name }}</td><td><strong>{{ $r->product->name }}</strong><div class="small text-muted">{{ $r->product->code }}</div></td><td>{{ $r->sku }}<div class="small text-muted">{{ $r->name }}</div></td><td>{{ $r->product->unit }}</td><td>{{ $r->product->tracking_type==='serialized'?'Por serie':'Por cantidad' }}</td><td class="text-nowrap">@if($r->expiration_date)<strong>{{ \Illuminate\Support\Carbon::parse($r->expiration_date)->format('d/m/Y') }}</strong><div><span class="badge bg-{{ $this->expiryBadge($r->expiration_date) }}">{{ $expiryLabel }}</span></div>@else<span class="text-muted">—</span>@endif</td><td class="text-end fw-bold">{{ number_format($stock,3) }}</td><td class="text-end">{{ number_format((float)$r->minimum_stock,3) }}</td><td><span class="badge bg-{{ $label==='Agotado'?'dark':($label==='Stock bajo'?'danger':'success') }}">{{ $label }}</span></td></tr>@empty<tr><td colspan="9" class="text-center py-5 text-muted">No existen productos que coincidan con los filtros.</td></tr>@endforelse</tbody>
    @elseif($reportType === 'movements')
        <thead><tr><th>Fecha</th><th>Movimiento</th><th>Producto / SKU</th><th>Serie</th><th>Documento / trabajador</th><th class="text-end">Entrada</th><th class="text-end">Salida</th><th>Usuario</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td class="text-nowrap">{{ $r->occurred_at->format('d/m/Y H:i:s') }}</td><td>{{ $this->movementLabel($r->movement_type) }}</td><td><strong>{{ $r->variant->product->name }}</strong><div class="small text-muted">{{ $r->variant->sku }} · {{ $r->variant->product->unit }}</div></td><td>{{ $r->serializedItem?->serial_number ?? '—' }}</td><td>{{ $r->dispatchNote?->number ?? $r->delivery?->number ?? '—' }}<div class="small text-muted">{{ $r->delivery?->worker?->full_name }}</div></td><td class="text-end text-success">{{ $r->quantity>0?number_format((float)$r->quantity,3):'' }}</td><td class="text-end text-danger">{{ $r->quantity<0?number_format(abs((float)$r->quantity),3):'' }}</td><td>{{ $r->creator?->login }}</td></tr>@empty<tr><td colspan="8" class="text-center py-5 text-muted">No existen movimientos que coincidan con los filtros.</td></tr>@endforelse</tbody>
    @else
        <thead><tr><th>Fecha</th><th>Entrega / estado</th><th>Trabajador</th><th>Área</th><th>Producto / SKU</th><th class="text-end">Cantidad</th><th>Series asignadas</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->delivery->delivery_date->format('d/m/Y') }}</td><td>{{ $r->delivery->number ?: 'BORRADOR #'.$r->delivery->id }}<div class="small text-muted">{{ ['draft'=>'Borrador','confirmed'=>'Confirmada','annulled'=>'Anulada'][$r->delivery->status] }}</div></td><td><strong>{{ $r->delivery->worker->full_name }}</strong><div class="small text-muted">{{ $r->delivery->worker->document }}</div></td><td>{{ $r->delivery->worker->area }}</td><td><strong>{{ $r->variant->product->name }}</strong><div class="small text-muted">{{ $r->variant->sku }} · {{ $r->variant->product->unit }}</div></td><td class="text-end">{{ number_format((float)$r->quantity,3) }}</td><td>{{ $r->serializedItems->pluck('serial_number')->join(', ') ?: '—' }}</td></tr>@empty<tr><td colspan="7" class="text-center py-5 text-muted">No existen entregas que coincidan con los filtros.</td></tr>@endforelse</tbody>
    @endif
    </table></div>
    @if($rows->hasPages())<div class="card-footer no-print">{{ $rows->links() }}</div>@endif
</div>
</div>
