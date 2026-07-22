<div class="page-content">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Entregas a trabajadores</h4>
            <p class="text-muted mb-0">Registra la salida controlada de productos, EPP y activos hacia cada trabajador.</p>
        </div>
        <span class="module-counter">{{ $deliveries->total() }} registradas</span>
    </div>

    @canany(['crear-entrega', 'editar-entrega'])
        <div class="card module-form-card" id="delivery-form">
            <div class="card-header">
                <div>
                    <i class="bx bx-package me-1"></i>
                    <strong>{{ $deliveryId ? 'Editar entrega en borrador' : 'Registrar entrega' }}</strong>
                    @if($deliveryId)<div class="form-card-subtitle">Los cambios se guardarán en esta nueva versión.</div>@endif
                </div>
                @if($deliveryId)<button type="button" wire:click="resetForm" class="btn btn-sm btn-outline-secondary">Cancelar edición</button>@endif
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger d-flex align-items-start gap-2" role="alert"><i class="bx bx-error-circle fs-5"></i><div><strong>No se pudo guardar.</strong><div>{{ $errors->first() }}</div></div></div>
                @endif

                <form wire:submit="save">
                    <div class="form-section-title">Datos de la entrega</div>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4 position-relative">
                            <label class="form-label">Trabajador activo <span class="text-danger">*</span></label>
                            @if($selectedWorker)
                                <div class="form-control readonly-control d-flex justify-content-between align-items-center">
                                    <span><strong>{{ $selectedWorker->full_name }}</strong> · {{ $selectedWorker->document }}</span>
                                    <button type="button" wire:click="clearWorker" class="btn btn-sm btn-link text-danger p-0" title="Cambiar trabajador"><i class="bx bx-x fs-5"></i></button>
                                </div>
                            @else
                                <input wire:model.live.debounce.300ms="workerSearch" class="form-control @error('worker_id') is-invalid @enderror" placeholder="Buscar por nombre o documento">
                                @if(mb_strlen(trim($workerSearch)) >= 2)
                                    <div class="list-group position-absolute start-0 end-0 shadow search-results">
                                        @forelse($workerResults as $worker)
                                            <button type="button" wire:click="selectWorker({{ $worker->id }})" class="list-group-item list-group-item-action"><strong>{{ $worker->full_name }}</strong><div class="small text-muted">{{ $worker->document }}{{ $worker->area ? ' · '.$worker->area : '' }}</div></button>
                                        @empty
                                            <div class="list-group-item text-muted">No se encontraron trabajadores.</div>
                                        @endforelse
                                    </div>
                                @endif
                            @endif
                            @error('worker_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" max="{{ now()->format('Y-m-d') }}" wire:model="delivery_date" class="form-control @error('delivery_date') is-invalid @enderror">
                            @error('delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Código de entrega <span class="field-optional">Opcional</span></label>
                            <input wire:model="number" maxlength="30" class="form-control text-uppercase @error('number') is-invalid @enderror" placeholder="Automático: ENT-01-18072026-RGD">
                            @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Motivo / actividad <span class="field-optional">Opcional</span></label>
                            <input wire:model="reason" maxlength="180" class="form-control @error('reason') is-invalid @enderror" placeholder="Dotación, trabajo específico…">
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-section-title mt-4">Productos <span class="text-danger">*</span></div>
                    <div class="position-relative">
                        <div class="input-group"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.250ms="productSearch" class="form-control" placeholder="Buscar por producto, código, SKU, variante o número de serie"></div>
                        @if(mb_strlen(trim($productSearch)) >= 2)
                            <div class="list-group position-absolute w-100 shadow search-results">
                                @forelse($productResults as $result)
                                    <button type="button" wire:click="addProduct({{ $result->id }})" class="list-group-item list-group-item-action d-flex justify-content-between gap-3">
                                        <span><strong>{{ $result->product->name }}</strong> · {{ $result->name ?: $result->sku }}<small class="d-block text-muted">{{ $result->sku }}</small></span>
                                        @if($result->product->tracking_type === 'serialized')
                                            <span class="badge bg-{{ $result->deliverable_serials_count > 0 ? 'primary' : 'danger' }} align-self-center">{{ $result->deliverable_serials_count }} serie(s) disponible(s)</span>
                                        @else
                                            <span class="badge bg-light text-dark align-self-center">Stock {{ \App\Support\Quantity::format($result->stock ?? 0) }}</span>
                                        @endif
                                    </button>
                                @empty
                                    <div class="list-group-item text-muted">No se encontraron productos.</div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                    @error('items')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                    <div class="document-items">
                        @foreach($items as $index => $row)
                            @php($selected = $variants->firstWhere('id', (int) ($row['variant_id'] ?? 0)))
                            <div class="document-item" wire:key="delivery-item-{{ $index }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-5"><label class="form-label">Producto / variante</label><div class="form-control readonly-control"><strong>{{ $selected?->product?->name }}</strong> · {{ $selected?->name ?: $selected?->sku }} <span class="small text-muted">({{ $selected?->sku }})</span></div></div>
                                    @if($selected?->product?->tracking_type === 'serialized')
                                        <div class="col-lg-6">
                                            <label class="form-label">Número de serie disponible <span class="text-danger">*</span></label>
                                            @if($selected->serializedItems->isNotEmpty())
                                                <div class="border rounded p-2 bg-light-subtle @error('items.'.$index.'.serial_ids') border-danger @enderror" style="max-height: 170px; overflow-y: auto;">
                                                    @foreach($selected->serializedItems as $serial)
                                                        <label class="form-check d-flex align-items-center gap-2 border rounded bg-white px-3 py-2 mb-2" wire:key="delivery-serial-{{ $index }}-{{ $serial->id }}">
                                                            <input type="checkbox" value="{{ $serial->id }}" wire:model.live="items.{{ $index }}.serial_ids" class="form-check-input mt-0">
                                                            <span class="font-monospace fw-semibold">{{ $serial->serial_number }}</span>
                                                            @if($serial->status !== 'available')<span class="badge bg-info ms-auto">Asignada en la entrega original</span>@endif
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    Seleccionadas: <strong>{{ count($row['serial_ids'] ?? []) }}</strong> · Disponibles para elegir: {{ $selected->serializedItems->where('status', 'available')->count() }}
                                                </div>
                                            @else
                                                <div class="alert alert-warning py-2 mb-0"><i class="bx bx-error-circle me-1"></i>No existen series con saldo disponible. Confirma primero un remito de ingreso.</div>
                                            @endif
                                            @error('items.'.$index.'.serial_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-sm-4 col-lg-2"><label class="form-label">Cantidad</label><input value="{{ count($row['serial_ids'] ?? []) }}" class="form-control" disabled></div>
                                        <div class="col-sm-8 col-lg-3"><label class="form-label">Observación <span class="field-optional">Opcional</span></label><input wire:model="items.{{ $index }}.notes" maxlength="500" class="form-control @error('items.'.$index.'.notes') is-invalid @enderror">@error('items.'.$index.'.notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    @else
                                        <div class="col-sm-4 col-lg-2"><label class="form-label">Cantidad <span class="text-danger">*</span></label><input type="number" min="0.001" step="0.001" wire:model="items.{{ $index }}.quantity" class="form-control @error('items.'.$index.'.quantity') is-invalid @enderror">@error('items.'.$index.'.quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                        <div class="col-sm-6 col-lg-3"><label class="form-label">Observación <span class="field-optional">Opcional</span></label><input wire:model="items.{{ $index }}.notes" maxlength="500" class="form-control @error('items.'.$index.'.notes') is-invalid @enderror">@error('items.'.$index.'.notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    @endif
                                    <div class="col-4 col-lg-1 ms-auto"><button type="button" wire:click="removeItem({{ $index }})" class="btn btn-outline-danger w-100" title="Quitar producto"><i class="bx bx-trash"></i></button></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3 mt-1"><div class="col-12"><label class="form-label">Observaciones <span class="field-optional">Opcional</span></label><textarea wire:model="notes" maxlength="2000" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Información adicional de la entrega"></textarea>@error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                    <div class="form-actions">
                        @if($deliveryId)<button type="button" wire:click="resetForm" class="btn btn-outline-secondary">Cancelar</button>@endif
                        <button class="btn btn-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save"><i class="bx bx-save me-1"></i>{{ $deliveryId ? 'Guardar cambios' : 'Guardar borrador' }}</span><span wire:loading wire:target="save">Guardando...</span></button>
                    </div>
                </form>
            </div>
        </div>
    @endcanany

    @if($selectedDetail)
        <div class="modal fade show module-modal-shell" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="delivery-detail-title" wire:click.self="$set('detailId', null)" wire:keydown.escape.window="$set('detailId', null)">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="delivery-detail-title"><i class="bx bx-package me-1 text-primary"></i>Entrega {{ $selectedDetail->number ?: '#'.$selectedDetail->id }}</h5>
                            <div class="form-card-subtitle">{{ $selectedDetail->delivery_date->format('d/m/Y') }} · {{ $selectedDetail->worker->full_name }} · {{ $selectedDetail->worker->document }}</div>
                        </div>
                        <button type="button" wire:click="$set('detailId', null)" class="btn-close" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @if($selectedDetail->correctedFrom)<div class="alert alert-info py-2"><i class="bx bx-history me-1"></i>Esta versión corrige la entrega <strong>{{ $selectedDetail->correctedFrom->number }}</strong>.</div>@endif
                        @if($selectedDetail->correction)<div class="alert alert-warning py-2"><i class="bx bx-history me-1"></i>Este original fue sustituido por {{ $selectedDetail->correction->number ?: 'el borrador #'.$selectedDetail->correction->id }}.</div>@endif
                        <div class="detail-summary-grid mb-3">
                            <div class="detail-summary-item"><span class="detail-label">Estado</span><span class="badge bg-{{ ['draft'=>'secondary','confirmed'=>'success','annulled'=>'danger'][$selectedDetail->status] }}">{{ ['draft'=>'Borrador','confirmed'=>'Confirmada','annulled'=>'Inactiva / anulada'][$selectedDetail->status] }}</span></div>
                            <div class="detail-summary-item"><span class="detail-label">Trabajador</span><strong>{{ $selectedDetail->worker->full_name }}</strong><div class="small text-muted">{{ $selectedDetail->worker->document }}</div></div>
                            <div class="detail-summary-item"><span class="detail-label">Área</span><strong>{{ $selectedDetail->worker->area ?: '—' }}</strong></div>
                            <div class="detail-summary-item"><span class="detail-label">Motivo</span><strong>{{ $selectedDetail->reason ?: '—' }}</strong></div>
                        </div>
                        <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Producto</th><th>SKU</th><th>Series</th><th class="text-end">Cantidad</th></tr></thead><tbody>@foreach($selectedDetail->items as $item)<tr><td><strong>{{ $item->variant->product->name }}</strong></td><td class="font-monospace">{{ $item->variant->sku }}</td><td>{{ $item->serializedItems->pluck('serial_number')->join(', ') ?: '—' }}</td><td class="text-end fw-semibold">{{ \App\Support\Quantity::format($item->quantity) }} {{ $item->variant->product->unit }}</td></tr>@endforeach</tbody></table></div>
                        @if($selectedDetail->annul_reason)<div class="alert alert-danger mt-3 mb-0"><strong>Motivo de inactivación/anulación:</strong> {{ $selectedDetail->annul_reason }}</div>@endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="$set('detailId', null)" class="btn btn-outline-secondary"><i class="bx bx-x me-1"></i>Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card module-list-card">
        <div class="card-header filter-header">
            <div class="filter-title"><i class="bx bx-list-ul"></i><span>Entregas registradas</span></div>
            <div class="row g-2 flex-grow-1 justify-content-end"><div class="col-12 col-lg-6"><label class="filter-label">Buscar</label><div class="input-group"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.350ms="searchTerm" class="form-control" placeholder="Número, trabajador o documento"></div></div><div class="col-12 col-sm-5 col-lg-3"><label class="filter-label">Estado</label><select wire:model.live="statusFilter" class="form-select"><option value="">Todos</option><option value="draft">Borradores</option><option value="confirmed">Confirmadas</option><option value="annulled">Inactivas / anuladas</option></select></div></div>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover table-with-actions"><thead><tr><th>Número</th><th>Fecha</th><th>Trabajador</th><th>Productos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
            @forelse($deliveries as $delivery)
                <tr wire:key="delivery-{{ $delivery->id }}"><td class="font-monospace">{{ $delivery->number ?: 'BORRADOR #'.$delivery->id }}</td><td class="text-nowrap">{{ $delivery->delivery_date->format('d/m/Y') }}</td><td><strong>{{ $delivery->worker->full_name }}</strong><div class="small text-muted">{{ $delivery->worker->document }}</div></td><td>{{ $delivery->items_count }}</td><td><span class="badge bg-{{ ['draft'=>'secondary','confirmed'=>'success','annulled'=>'danger'][$delivery->status] }}">{{ ['draft'=>'Borrador','confirmed'=>'Confirmada','annulled'=>'Inactiva / anulada'][$delivery->status] }}</span></td><td class="text-end text-nowrap">
                    <button wire:click="viewDetail({{ $delivery->id }})" class="btn btn-sm btn-outline-info" title="Ver detalle"><i class="bx bx-show"></i></button>
                    <a target="_blank" href="{{ route('deliveries.print', $delivery) }}" class="btn btn-sm btn-outline-secondary" title="Imprimir"><i class="bx bx-printer"></i></a>
                    @if($delivery->status === 'draft')
                        @can('editar-entrega')<button wire:click="edit({{ $delivery->id }})" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit me-1"></i>Editar</button>@endcan
                        @can('confirmar-entrega')<button wire:click="confirm({{ $delivery->id }})" wire:confirm="¿Confirmar esta entrega y descontar el inventario?" class="btn btn-sm btn-success">Confirmar</button>@endcan
                        @can('eliminar-entrega')<button wire:click="deleteDraft({{ $delivery->id }})" wire:confirm="¿Eliminar definitivamente esta entrega en borrador? Sus datos quedarán registrados en el historial." class="btn btn-sm btn-outline-danger"><i class="bx bx-trash me-1"></i>Eliminar</button>@endcan
                    @elseif($delivery->status === 'confirmed')
                        @can('editar-entrega')<button wire:click="correct({{ $delivery->id }})" wire:confirm="Se creará una versión editable. El original seguirá vigente hasta confirmar los cambios. ¿Continuar?" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit me-1"></i>Editar</button>@endcan
                        @can('anular-entrega')<button onclick="const m=prompt('Motivo de anulación (mínimo 10 caracteres):');if(m){$wire.set('annulReason',m).then(()=>$wire.annul({{ $delivery->id }}))}" class="btn btn-sm btn-outline-danger">Anular</button>@endcan
                    @endif
                </td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No hay entregas registradas.</td></tr>
            @endforelse
        </tbody></table></div></div>
        @if($deliveries->hasPages())<div class="card-footer">{{ $deliveries->links() }}</div>@endif
    </div>

    @script
        <script>
            $wire.on('document-form-opened', ({ target }) => {
                if (target !== 'delivery-form') return;
                setTimeout(() => document.getElementById(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 75);
            });
        </script>
    @endscript
</div>
