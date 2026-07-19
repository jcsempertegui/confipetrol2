<div class="page-content">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Inventario</h4>
            <p class="text-muted mb-0">Existencias calculadas desde movimientos confirmados. El stock no se modifica manualmente.</p>
        </div>
        <span class="module-counter">Actualizado: {{ now()->format('d/m/Y H:i') }}</span>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3"><div class="card mb-0"><div class="card-body py-3"><div class="text-muted small">Variantes encontradas</div><div class="fs-4 fw-bold">{{ $variants->total() }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card mb-0"><div class="card-body py-3"><div class="text-muted small">Con existencia (página)</div><div class="fs-4 fw-bold text-success">{{ $variants->getCollection()->filter(fn($v) => (float) ($v->stock ?? 0) > 0)->count() }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card mb-0"><div class="card-body py-3"><div class="text-muted small">Sin existencia (página)</div><div class="fs-4 fw-bold text-danger">{{ $variants->getCollection()->filter(fn($v) => (float) ($v->stock ?? 0) <= 0)->count() }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card mb-0"><div class="card-body py-3"><div class="text-muted small">Unidades visibles</div><div class="fs-4 fw-bold text-primary">{{ number_format($variants->getCollection()->sum(fn($v) => (float) ($v->stock ?? 0)), 3) }}</div></div></div></div>
    </div>

    <div class="card module-list-card">
        <div class="card-header filter-header">
            <div class="filter-title"><i class="bx bx-package"></i><span>Existencias</span></div>
            <div class="row g-2 flex-grow-1 justify-content-end">
                <div class="col-12 col-lg-5"><label class="filter-label">Buscar</label><div class="input-group"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.350ms="searchTerm" class="form-control" placeholder="Producto, código, SKU o variante"></div></div>
                <div class="col-sm-6 col-lg-3"><label class="filter-label">Categoría</label><select wire:model.live="categoryFilter" class="form-select"><option value="">Todas las categorías</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="filter-label">Stock</label><select wire:model.live="stockFilter" class="form-select"><option value="">Todo el stock</option><option value="positive">Con existencia</option><option value="zero">Sin existencia</option></select></div>
            </div>
        </div>

        <div class="table-responsive d-none d-lg-block">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Producto</th><th>SKU / variante</th><th>Categoría</th><th class="text-end">Stock actual</th><th>Control</th><th></th></tr></thead>
                <tbody>
                    @forelse($variants as $v)
                        <tr>
                            <td><strong>{{ $v->product->name }}</strong><div class="small text-muted">{{ $v->product->code }}</div></td>
                            <td><span class="font-monospace">{{ $v->sku }}</span><div class="small text-muted">{{ $v->name }}</div></td>
                            <td>{{ $v->product->category->name }}</td>
                            <td class="text-end"><span class="fs-6 fw-bold text-{{ (float) ($v->stock ?? 0) > 0 ? 'success' : 'danger' }}">{{ number_format((float) ($v->stock ?? 0), 3) }}</span></td>
                            <td>
                                @if($v->product->tracking_type === 'serialized')
                                    <span class="badge bg-light text-dark border">Por serie</span>
                                    <div class="small text-muted">{{ $v->serialized_available_count }} disponibles · {{ $v->serialized_assigned_count }} asignadas</div>
                                @else
                                    <span class="badge bg-light text-dark border">Por cantidad</span>
                                @endif
                            </td>
                            <td class="text-end"><button wire:click="viewKardex({{ $v->id }})" class="btn btn-sm btn-outline-primary"><i class="bx bx-history me-1"></i>Kardex</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No se encontraron productos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-lg-none p-2">
            @forelse($variants as $v)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between gap-2"><div><strong>{{ $v->product->name }}</strong><div class="small font-monospace text-muted">{{ $v->sku }} · {{ $v->name }}</div></div><span class="fw-bold text-{{ (float) ($v->stock ?? 0) > 0 ? 'success' : 'danger' }}">{{ number_format((float) ($v->stock ?? 0), 3) }}</span></div>
                    <div class="small text-muted mt-1">{{ $v->product->category->name }} · {{ $v->product->tracking_type === 'serialized' ? 'Por serie' : 'Por cantidad' }}</div>
                    <button wire:click="viewKardex({{ $v->id }})" class="btn btn-sm btn-outline-primary w-100 mt-2"><i class="bx bx-history me-1"></i>Ver Kardex</button>
                </div>
            @empty
                <div class="text-center text-muted py-5">No se encontraron productos.</div>
            @endforelse
        </div>
    </div>

    @if($variants->hasPages())<div class="mt-3">{{ $variants->links() }}</div>@endif

    @if($selectedVariant)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="kardex-title" wire:click.self="clearKardex" wire:keydown.escape.window="clearKardex" style="background: rgba(15, 23, 42, .58); z-index: 1060;">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content shadow-lg">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="kardex-title"><i class="bx bx-history me-1"></i>Kardex: {{ $selectedVariant->product->name }}</h5>
                            <div class="small text-muted mt-1">{{ $selectedVariant->sku }} · {{ $selectedVariant->product->category->name }} · Stock actual <strong>{{ number_format((float) ($selectedVariant->stock ?? 0), 3) }} {{ $selectedVariant->product->unit }}</strong></div>
                        </div>
                        <button type="button" wire:click="clearKardex" class="btn-close" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body p-0">
                        <div class="p-3 border-bottom bg-light">
                            <div class="row g-2">
                                <div class="col-6 col-md-3"><label class="filter-label">Desde</label><input type="date" wire:model.live="fromDate" class="form-control"></div>
                                <div class="col-6 col-md-3"><label class="filter-label">Hasta</label><input type="date" wire:model.live="toDate" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="filter-label">Movimiento</label><select wire:model.live="movementFilter" class="form-select"><option value="">Todos</option><option value="dispatch_entry">Ingresos</option><option value="dispatch_exit">Salidas por remito</option><option value="dispatch_correction">Correcciones de remito</option><option value="delivery">Entregas</option><option value="delivery_correction">Correcciones de entrega</option><option value="annulment">Anulaciones de remito</option><option value="delivery_annulment">Anulaciones de entrega</option></select></div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="sticky-top bg-white"><tr><th>Fecha y hora</th><th>Movimiento</th><th>Documento / destino</th><th>Serie</th><th>Usuario</th><th class="text-end">Entrada</th><th class="text-end">Salida</th><th class="text-end">Saldo</th></tr></thead>
                                <tbody>
                                    @forelse($movements as $m)
                                        @php
                                            $label = $this->movementLabel($m->movement_type);
                                            $reference = $m->dispatchNote?->number ?? $m->delivery?->number ?? '—';
                                            $destination = $m->delivery?->worker?->full_name;
                                        @endphp
                                        <tr>
                                            <td class="text-nowrap">{{ $m->occurred_at->format('d/m/Y H:i:s') }}</td>
                                            <td>{{ $label }}</td>
                                            <td><strong>{{ $reference }}</strong>@if($destination)<div class="small text-muted">{{ $destination }}</div>@endif</td>
                                            <td class="font-monospace small">{{ $m->serializedItem?->serial_number ?? '—' }}</td>
                                            <td>{{ $m->creator?->login ?? 'Sistema' }}</td>
                                            <td class="text-end text-success">{{ (float) $m->quantity > 0 ? number_format((float) $m->quantity, 3) : '' }}</td>
                                            <td class="text-end text-danger">{{ (float) $m->quantity < 0 ? number_format(abs((float) $m->quantity), 3) : '' }}</td>
                                            <td class="text-end fw-bold">{{ number_format($m->balance_after, 3) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center text-muted py-5">No hay movimientos en el periodo seleccionado.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <div>@if($movements->hasPages()){{ $movements->links() }}@endif</div>
                        <button type="button" wire:click="clearKardex" class="btn btn-outline-secondary">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
