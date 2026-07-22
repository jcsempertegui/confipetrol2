<div class="page-content reports-page">
<style>
@media print{@page{size:A4 landscape;margin:12mm}.sidebar-wrapper,.topbar,.report-controls,.page-footer,.no-print{display:none!important}.page-wrapper{margin:0!important}.page-content{padding:0!important}.reports-page{font-family:Arial,sans-serif;color:#17202a}.print-brand{display:flex!important;border-bottom:3px solid #0057a7;margin-bottom:14px;padding-bottom:10px}.card{box-shadow:none!important;border:0!important}.card-header{background:#fff!important;padding:8px 0!important}.table{font-size:9px;border-collapse:collapse}.table thead{display:table-header-group}.table th{background:#0057a7!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.table td,.table th{border:1px solid #b8c4cf!important;padding:5px!important}.badge{border:1px solid #555;color:#111!important;background:#fff!important}.table-responsive{overflow:visible!important}}
.report-tabs .btn{white-space:normal}.active-filter{border-color:#0d6efd!important;background:#f1f6ff}
.category-filter-panel{padding:1rem;border:1px solid #cfe0f5;border-radius:.7rem;background:linear-gradient(110deg,#f8fbff,#fff)}
.report-advanced-panel{padding:.9rem 1rem;border:1px dashed #cfd8e3;border-radius:.65rem;background:#fafbfd}
.report-serial-list{display:flex;flex-wrap:wrap;gap:.3rem;min-width:180px;max-width:320px}
body.dark-mode .category-filter-panel{border-color:#3a4f69;background:linear-gradient(110deg,#242a31,#272b30)}
body.dark-mode .report-advanced-panel{border-color:#49515a;background:#23272c}
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

<div class="card module-filter-card report-controls">
    <div class="card-header">
        <div>
            <strong><i class="bx bx-slider-alt me-1 text-primary"></i>Configurar reporte</strong>
            <div class="form-card-subtitle">Selecciona el tipo de informe y limita los resultados con los filtros disponibles.</div>
        </div>
    </div>
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
                <div class="col-sm-4 col-lg-2 d-flex align-items-end"><button type="button" wire:click="$toggle('showAdvancedFilters')" class="btn btn-outline-secondary w-100"><i class="bx bx-slider me-1"></i>{{ $showAdvancedFilters ? 'Ocultar filtros' : 'Más filtros' }}<i class="bx {{ $showAdvancedFilters ? 'bx-chevron-up' : 'bx-chevron-down' }} ms-1"></i></button></div>
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
                            <div class="search-results position-absolute start-0 end-0 bg-white border shadow-sm mt-1">
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

            @if($reportType === 'stock' && $showAdvancedFilters)
                <div class="col-12 mt-3">
                    <div class="report-advanced-panel">
                        <div class="small fw-semibold text-uppercase text-muted mb-2">Filtros generales adicionales</div>
                        <div class="row g-2">
                            <div class="col-sm-6 col-lg-3"><label class="filter-label">Estado en el catálogo</label><select wire:model.live="catalogStatus" class="form-select"><option value="active">Solo activos</option><option value="inactive">Solo inactivos</option><option value="all">Activos e inactivos</option></select></div>
                            @if($trackingTypes->count() > 1)
                                <div class="col-sm-6 col-lg-3"><label class="filter-label">Tipo de control</label><select wire:model.live="trackingFilter" class="form-select"><option value="">Todos</option><option value="bulk">Por cantidad</option><option value="serialized">Por serie</option></select></div>
                            @endif
                            @if($units->count() > 1)
                                <div class="col-sm-6 col-lg-3"><label class="filter-label">Unidad de medida</label><select wire:model.live="unitFilter" class="form-select"><option value="">Todas las unidades</option>@foreach($units as $unit)<option value="{{ $unit }}">{{ ucfirst($unit) }}</option>@endforeach</select></div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($selectedCategory && ($reportAttributes->isNotEmpty() || $hasSerialContext || $showExpiryColumn))
                <div class="col-12 mt-3">
                    <div class="category-filter-panel">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div><strong><i class="bx bx-category-alt me-1 text-primary"></i>Filtros de {{ $selectedCategory->name }}</strong><div class="small text-muted">Estos campos cambian automáticamente según los atributos configurados en la categoría.</div></div>
                            <span class="badge bg-light text-primary border">{{ $selectedCategory->code }}</span>
                        </div>
                        <div class="row g-2">
                            @if($showExpiryColumn)
                                <div class="col-sm-6 col-lg-3"><label class="filter-label">Estado de vencimiento</label><select wire:model.live="expiryStatus" class="form-select"><option value="">Todos</option><option value="expired">Vencidos</option><option value="today">Vencen hoy</option><option value="next30">Próximos 30 días</option><option value="next90">Próximos 90 días</option><option value="valid">Vigentes</option><option value="without">Sin vencimiento</option></select></div>
                                <div class="col-6 col-lg-2"><label class="filter-label">Vence desde</label><input type="date" wire:model.live="expiryFrom" class="form-control @error('expiryFrom') is-invalid @enderror">@error('expiryFrom')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-6 col-lg-2"><label class="filter-label">Vence hasta</label><input type="date" wire:model.live="expiryTo" class="form-control @error('expiryTo') is-invalid @enderror">@error('expiryTo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            @endif
                            @if($hasSerialContext)
                                <div class="col-sm-6 col-lg-3">
                                    <label class="filter-label">Número de serie</label>
                                    <div class="input-group"><span class="input-group-text"><i class="bx bx-barcode-reader"></i></span><input wire:model.live.debounce.350ms="serialFilter" class="form-control" placeholder="Buscar serie exacta o parcial"></div>
                                </div>
                            @endif
                            @foreach($reportAttributes as $attribute)
                                <div class="col-sm-6 col-lg-3" wire:key="report-attribute-filter-{{ $attribute->id }}">
                                    <label class="filter-label">{{ $attribute->name }} <span class="field-optional">{{ $attribute->scope === 'product' ? 'Producto' : 'Variante' }}</span></label>
                                    @if($attribute->type === 'select')
                                        <select wire:model.live="attributeFilters.{{ $attribute->id }}" class="form-select"><option value="">Todos</option>@foreach($attribute->options ?? [] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select>
                                    @elseif($attribute->type === 'boolean')
                                        <select wire:model.live="attributeFilters.{{ $attribute->id }}" class="form-select"><option value="">Todos</option><option value="1">Sí</option><option value="0">No</option></select>
                                    @else
                                        <input wire:model.live.debounce.350ms="attributeFilters.{{ $attribute->id }}" type="{{ $attribute->type === 'number' ? 'number' : ($attribute->type === 'date' ? 'date' : 'text') }}" class="form-control" placeholder="{{ $attribute->type === 'text' ? 'Escriba para filtrar' : '' }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @elseif($reportType === 'stock' && ! $selectedCategory)
                <div class="col-12 mt-2"><div class="alert alert-light border mb-0 py-2"><i class="bx bx-info-circle me-1 text-primary"></i>Selecciona una categoría para habilitar filtros específicos como marca, modelo, talla o número de serie.</div></div>
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

<div class="card module-list-card">
    <div class="card-header d-flex flex-wrap justify-content-between gap-2">
        <div><strong>{{ ['stock'=>'Reporte de inventario y alertas','movements'=>'Reporte de movimientos y Kardex','deliveries'=>'Reporte de entregas y asignaciones'][$reportType] }}</strong><div class="small text-muted">{{ number_format($rows->total()) }} resultado(s) encontrados</div></div>
        <div class="small text-muted">Generado: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
    <div class="table-responsive"><table class="table table-hover table-striped align-middle mb-0">
    @if($reportType === 'stock')
        <thead>
            <tr>
                <th>Categoría</th><th>Producto</th><th>SKU / variante</th>
                @foreach($reportAttributes as $attribute)<th>{{ $attribute->name }}</th>@endforeach
                @if($showSerialColumn)<th>Series en almacén</th>@endif
                <th>Unidad</th><th>Control</th>
                @if($showExpiryColumn)<th>Vencimiento</th>@endif
                <th class="text-end">Stock</th><th class="text-end">Mínimo</th><th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                @php
                    $stock = (float) ($r->stock ?? 0);
                    $label = $this->stockLabel($stock, (float) $r->minimum_stock);
                    $expiryLabel = $this->expiryLabel($r->expiration_date);
                    $availableSerials = $r->serializedItems->filter(fn ($serial) => (float) ($serial->inventory_balance ?? 0) > 0);
                @endphp
                <tr>
                    <td>{{ $r->product->category->name }}</td>
                    <td><strong>{{ $r->product->name }}</strong><div class="small text-muted">{{ $r->product->code }}</div></td>
                    <td><span class="font-monospace">{{ $r->sku }}</span><div class="small text-muted">{{ $r->name }}</div></td>
                    @foreach($reportAttributes as $attribute)
                        @php
                            $storedAttribute = $attribute->scope === 'product'
                                ? $r->product->attributeValues->firstWhere('product_attribute_id', $attribute->id)
                                : $r->attributeValues->firstWhere('product_attribute_id', $attribute->id);
                            $attributeValue = $storedAttribute?->value;
                            if ($attribute->type === 'boolean' && $attributeValue !== null && $attributeValue !== '') {
                                $attributeValue = (string) $attributeValue === '1' ? 'Sí' : 'No';
                            }
                        @endphp
                        <td>{{ filled($attributeValue) ? $attributeValue : '—' }}</td>
                    @endforeach
                    @if($showSerialColumn)
                        <td>
                            @if($r->product->tracking_type === 'serialized')
                                <div class="report-serial-list">
                                    @forelse($availableSerials as $serial)<span class="badge bg-light text-dark border font-monospace">{{ $serial->serial_number }}</span>@empty<span class="text-muted small">Sin series disponibles</span>@endforelse
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    @endif
                    <td>{{ $r->product->unit }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $r->product->tracking_type === 'serialized' ? 'Por serie' : 'Por cantidad' }}</span></td>
                    @if($showExpiryColumn)
                        <td class="text-nowrap">@if($r->expiration_date)<strong>{{ \Illuminate\Support\Carbon::parse($r->expiration_date)->format('d/m/Y') }}</strong><div><span class="badge bg-{{ $this->expiryBadge($r->expiration_date) }}">{{ $expiryLabel }}</span></div>@else<span class="text-muted">—</span>@endif</td>
                    @endif
                    <td class="text-end fw-bold">{{ \App\Support\Quantity::format($stock) }}</td>
                    <td class="text-end">{{ \App\Support\Quantity::format($r->minimum_stock) }}</td>
                    <td><span class="badge bg-{{ $label === 'Agotado' ? 'dark' : ($label === 'Stock bajo' ? 'danger' : 'success') }}">{{ $label }}</span></td>
                </tr>
            @empty
                <tr><td colspan="{{ $stockColumnCount }}" class="text-center py-5 text-muted">No existen productos que coincidan con los filtros.</td></tr>
            @endforelse
        </tbody>
    @elseif($reportType === 'movements')
        <thead><tr><th>Fecha</th><th>Movimiento</th><th>Producto / SKU</th><th>Serie</th><th>Documento / trabajador</th><th class="text-end">Entrada</th><th class="text-end">Salida</th><th>Usuario</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td class="text-nowrap">{{ $r->occurred_at->format('d/m/Y H:i:s') }}</td><td>{{ $this->movementLabel($r->movement_type) }}</td><td><strong>{{ $r->variant->product->name }}</strong><div class="small text-muted">{{ $r->variant->sku }} · {{ $r->variant->product->unit }}</div></td><td>{{ $r->serializedItem?->serial_number ?? '—' }}</td><td>{{ $r->dispatchNote?->number ?? $r->delivery?->number ?? '—' }}<div class="small text-muted">{{ $r->delivery?->worker?->full_name }}</div></td><td class="text-end text-success">{{ $r->quantity > 0 ? \App\Support\Quantity::format($r->quantity) : '' }}</td><td class="text-end text-danger">{{ $r->quantity < 0 ? \App\Support\Quantity::format(abs((float) $r->quantity)) : '' }}</td><td>{{ $r->creator?->login }}</td></tr>@empty<tr><td colspan="8" class="text-center py-5 text-muted">No existen movimientos que coincidan con los filtros.</td></tr>@endforelse</tbody>
    @else
        <thead><tr><th>Fecha</th><th>Entrega / estado</th><th>Trabajador</th><th>Área</th><th>Producto / SKU</th><th class="text-end">Cantidad</th><th>Series asignadas</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->delivery->delivery_date->format('d/m/Y') }}</td><td>{{ $r->delivery->number ?: 'BORRADOR #'.$r->delivery->id }}<div class="small text-muted">{{ ['draft'=>'Borrador','confirmed'=>'Confirmada','annulled'=>'Anulada'][$r->delivery->status] }}</div></td><td><strong>{{ $r->delivery->worker->full_name }}</strong><div class="small text-muted">{{ $r->delivery->worker->document }}</div></td><td>{{ $r->delivery->worker->area }}</td><td><strong>{{ $r->variant->product->name }}</strong><div class="small text-muted">{{ $r->variant->sku }} · {{ $r->variant->product->unit }}</div></td><td class="text-end">{{ \App\Support\Quantity::format($r->quantity) }}</td><td>{{ $r->serializedItems->pluck('serial_number')->join(', ') ?: '—' }}</td></tr>@empty<tr><td colspan="7" class="text-center py-5 text-muted">No existen entregas que coincidan con los filtros.</td></tr>@endforelse</tbody>
    @endif
    </table></div>
    @if($rows->hasPages())<div class="card-footer no-print">{{ $rows->links() }}</div>@endif
</div>
</div>
