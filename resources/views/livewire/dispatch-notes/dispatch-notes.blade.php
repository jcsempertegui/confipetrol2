<div class="page-content">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Remitos</h4>
            <p class="text-muted mb-0">Registra ingresos al almacén y salidas por devolución, vencimiento, daño u otro motivo.</p>
        </div>
        <span class="module-counter">{{ $dispatchNotes->total() }} registrados</span>
    </div>

    @canany(['crear-remito', 'editar-remito'])
        <div class="card module-form-card" id="dispatch-note-form">
            <div class="card-header">
                <div>
                    <i class="bx bx-transfer-alt me-1"></i>
                    <strong>{{ $noteId ? 'Editar remito en borrador' : 'Registrar remito' }}</strong>
                    @if($noteId)<div class="form-card-subtitle">Los cambios se guardarán en esta nueva versión.</div>@endif
                </div>
                @if($noteId)
                    <button type="button" wire:click="resetForm" class="btn btn-sm btn-outline-secondary">Cancelar edición</button>
                @endif
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                        <i class="bx bx-error-circle fs-5"></i>
                        <div><strong>No se pudo guardar.</strong><div>{{ $errors->first() }}</div></div>
                    </div>
                @endif

                <form wire:submit="save">
                    <div class="form-section-title">Datos del documento</div>
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select wire:model.live="type" class="form-select @error('type') is-invalid @enderror" @disabled($correctedFromId)>
                                <option value="entry">Ingreso</option>
                                <option value="exit">Salida</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" max="{{ now()->format('Y-m-d') }}" wire:model="document_date" class="form-control @error('document_date') is-invalid @enderror">
                            @error('document_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label">Número de remito <span class="field-optional">Opcional</span></label>
                            <input wire:model="number" maxlength="30" class="form-control text-uppercase @error('number') is-invalid @enderror" placeholder="Automático: REM-01-18072026-RGD">
                            <div class="form-text">La secuencia diaria es compartida por remitos de ingreso y salida.</div>
                            @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label">{{ $type === 'entry' ? 'Proveedor / procedencia' : 'Destino / proveedor' }} <span class="text-danger">*</span></label>
                            <input wire:model="counterparty" maxlength="180" class="form-control @error('counterparty') is-invalid @enderror" placeholder="Nombre o razón social">
                            @error('counterparty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label">Motivo <span class="field-optional">Opcional</span></label>
                            <input wire:model="reason" maxlength="180" placeholder="{{ $type === 'exit' ? 'Vencido, dañado…' : 'Compra, devolución…' }}" class="form-control @error('reason') is-invalid @enderror">
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-section-title mt-4">Productos <span class="text-danger">*</span></div>
                    <div class="position-relative">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input wire:model.live.debounce.250ms="productSearch" class="form-control" placeholder="Buscar producto y atributo, ej.: Camisa L">
                        </div>
                        @if(mb_strlen(trim($productSearch)) >= 1)
                            <div class="list-group position-absolute w-100 shadow search-results">
                                @forelse($productResults as $result)
                                    <button type="button" wire:click="addProductGroup({{ $result->id }})" class="list-group-item list-group-item-action product-search-result">
                                        <span class="product-search-main">
                                            <span>
                                                <strong>{{ $result->name }}</strong>
                                                <small class="d-block text-muted font-monospace">{{ $result->code }}</small>
                                            </span>
                                            <span class="badge bg-primary-subtle text-primary">{{ $result->variants->count() }} {{ $result->variants->count() === 1 ? 'presentación' : 'presentaciones' }}</span>
                                        </span>
                                        <span class="product-variant-preview">
                                            @foreach($result->variants->take(8) as $variant)
                                                @php($variantSummary = $variant->attributeValues->filter(fn($value) => filled($value->value) && $value->attribute)->map(fn($value) => $value->attribute->name.': '.$value->value)->join(' · '))
                                                <span class="variant-preview-chip">{{ $variant->name ?: ($variantSummary ?: $variant->sku) }}</span>
                                            @endforeach
                                            @if($result->variants->count() > 8)
                                                <span class="variant-preview-chip">+{{ $result->variants->count() - 8 }} más</span>
                                            @endif
                                        </span>
                                    </button>
                                @empty
                                    <div class="list-group-item text-muted">No se encontraron productos.</div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                    @error('items')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                    <div class="document-items">
                        @forelse($itemGroups as $productId => $group)
                            @php($groupProduct = $group->first()['variant']->product)
                            <div class="document-product-group" wire:key="note-product-{{ $productId }}">
                                <div class="document-product-header">
                                    <div>
                                        <strong>{{ $groupProduct->name }}</strong>
                                        <span class="font-monospace text-muted">{{ $groupProduct->code }}</span>
                                        <small class="d-block text-muted">Completa únicamente las presentaciones incluidas en el remito.</small>
                                    </div>
                                    <button type="button" wire:click="removeProduct({{ $productId }})" class="btn btn-sm btn-outline-danger" title="Quitar todas las presentaciones">
                                        <i class="bx bx-trash"></i><span class="d-none d-sm-inline">Quitar producto</span>
                                    </button>
                                </div>
                                <div class="document-variant-list">
                                    @foreach($group as $entry)
                                        @php($index = $entry['index'])
                                        @php($row = $entry['row'])
                                        @php($selected = $entry['variant'])
                                        <div class="document-variant-row" wire:key="note-item-{{ $selected->id }}">
                                            <div class="variant-identification">
                                                <span class="variant-name">{{ $selected->name ?: 'Presentación estándar' }}</span>
                                                @if($selected->attribute_summary)
                                                    <span class="d-block small text-primary"><i class="bx bx-purchase-tag-alt me-1"></i>{{ $selected->attribute_summary }}</span>
                                                @endif
                                                <span class="d-block small text-muted font-monospace">{{ $selected->sku }} · Stock {{ \App\Support\Quantity::format($selected->stock ?? 0) }}</span>
                                            </div>
                                            @if($selected->product->tracking_type === 'serialized')
                                                <div class="variant-serials">
                                                    <label class="form-label">Números de serie <span class="text-danger">*</span></label>
                                                    <select multiple size="3" wire:model="items.{{ $index }}.serial_ids" class="form-select @error('items.'.$index.'.serial_ids') is-invalid @enderror">
                                                        @foreach($selected->serializedItems as $serial)<option value="{{ $serial->id }}">{{ $serial->serial_number }}</option>@endforeach
                                                    </select>
                                                    @error('items.'.$index.'.serial_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="variant-quantity">
                                                    <label class="form-label">Cantidad</label>
                                                    <input value="{{ count($row['serial_ids'] ?? []) }}" disabled class="form-control">
                                                </div>
                                            @else
                                                <div class="variant-quantity">
                                                    <label class="form-label">Cantidad</label>
                                                    <input type="number" min="0" step="0.1" wire:model="items.{{ $index }}.quantity" placeholder="0" class="form-control @error('items.'.$index.'.quantity') is-invalid @enderror">
                                                    @error('items.'.$index.'.quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                            @endif
                                            <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-sm btn-outline-danger variant-remove" title="Quitar esta presentación"><i class="bx bx-x"></i></button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="document-products-empty">
                                <i class="bx bx-package"></i>
                                <span>Busca y selecciona un producto para agregar todas sus presentaciones.</span>
                            </div>
                        @endforelse
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label class="form-label">Observaciones <span class="field-optional">Opcional</span></label>
                            <textarea wire:model="notes" maxlength="2000" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Información adicional del remito"></textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-actions">
                        @if($noteId)<button type="button" wire:click="resetForm" class="btn btn-outline-secondary">Cancelar</button>@endif
                        <button class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save"><i class="bx bx-save me-1"></i>{{ $noteId ? 'Guardar cambios' : 'Guardar borrador' }}</span>
                            <span wire:loading wire:target="save">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcanany

    @if($selectedDetail)
        <div class="modal fade show module-modal-shell" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="dispatch-detail-title" wire:click.self="$set('detailId', null)" wire:keydown.escape.window="$set('detailId', null)">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="dispatch-detail-title"><i class="bx bx-receipt me-1 text-primary"></i>Remito {{ $selectedDetail->number ?: '#'.$selectedDetail->id }}</h5>
                            <div class="form-card-subtitle">{{ $selectedDetail->type === 'entry' ? 'Ingreso al almacén' : 'Salida del almacén' }} · {{ $selectedDetail->document_date->format('d/m/Y') }} · {{ $selectedDetail->counterparty }}</div>
                        </div>
                        <button type="button" wire:click="$set('detailId', null)" class="btn-close" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @if($selectedDetail->correctedFrom)<div class="alert alert-info py-2"><i class="bx bx-history me-1"></i>Esta versión corrige al remito <strong>{{ $selectedDetail->correctedFrom->number }}</strong>.</div>@endif
                        @if($selectedDetail->correction)<div class="alert alert-warning py-2"><i class="bx bx-history me-1"></i>Este original fue sustituido por {{ $selectedDetail->correction->number ?: 'el borrador #'.$selectedDetail->correction->id }}.</div>@endif
                        <div class="detail-summary-grid mb-3">
                            <div class="detail-summary-item"><span class="detail-label">Estado</span><span class="badge bg-{{ ['draft'=>'secondary','confirmed'=>'success','annulled'=>'danger'][$selectedDetail->status] }}">{{ ['draft'=>'Borrador','confirmed'=>'Confirmado','annulled'=>'Inactivo / anulado'][$selectedDetail->status] }}</span></div>
                            <div class="detail-summary-item"><span class="detail-label">Motivo</span><strong>{{ $selectedDetail->reason ?: '—' }}</strong></div>
                            <div class="detail-summary-item"><span class="detail-label">Registrado por</span><strong>{{ $selectedDetail->creator?->login ?? 'Sistema' }}</strong></div>
                        </div>
                        <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Producto</th><th>SKU / atributos</th><th>Series</th><th class="text-end">Cantidad</th></tr></thead><tbody>@foreach($selectedDetail->items as $item)<tr><td><strong>{{ $item->variant->product->name }}</strong></td><td><span class="font-monospace">{{ $item->variant->sku }}</span>@if($item->variant->attribute_summary)<div class="small text-primary mt-1">{{ $item->variant->attribute_summary }}</div>@endif</td><td>{{ $item->serializedItems->pluck('serial_number')->join(', ') ?: '—' }}</td><td class="text-end fw-semibold">{{ \App\Support\Quantity::format($item->quantity) }} {{ $item->variant->product->unit }}</td></tr>@endforeach</tbody></table></div>
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
            <div class="filter-title"><i class="bx bx-list-ul"></i><span>Remitos registrados</span></div>
            <div class="row g-2 flex-grow-1 justify-content-end">
                <div class="col-12 col-lg-5"><label class="filter-label">Buscar</label><div class="input-group"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.350ms="searchTerm" class="form-control" placeholder="Número o contraparte"></div></div>
                <div class="col-6 col-lg-2"><label class="filter-label">Tipo</label><select wire:model.live="typeFilter" class="form-select"><option value="">Todos</option><option value="entry">Ingresos</option><option value="exit">Salidas</option></select></div>
                <div class="col-6 col-lg-2"><label class="filter-label">Estado</label><select wire:model.live="statusFilter" class="form-select"><option value="">Todos</option><option value="draft">Borrador</option><option value="confirmed">Confirmado</option><option value="annulled">Inactivo / anulado</option></select></div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-with-actions">
                    <thead><tr><th>Número</th><th>Tipo</th><th>Fecha</th><th>Contraparte</th><th>Productos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse($dispatchNotes as $note)
                            <tr wire:key="dispatch-note-{{ $note->id }}">
                                <td class="font-monospace">{{ $note->number ?: 'BORRADOR #'.$note->id }}</td>
                                <td><span class="badge bg-{{ $note->type === 'entry' ? 'success' : 'warning' }}">{{ $note->type === 'entry' ? 'Ingreso' : 'Salida' }}</span></td>
                                <td class="text-nowrap">{{ $note->document_date->format('d/m/Y') }}</td>
                                <td>{{ $note->counterparty }}<div class="small text-muted">{{ $note->reason }}</div></td>
                                <td>{{ $note->items_count }}</td>
                                <td><span class="badge bg-{{ ['draft'=>'secondary','confirmed'=>'success','annulled'=>'danger'][$note->status] }}">{{ ['draft'=>'Borrador','confirmed'=>'Confirmado','annulled'=>'Inactivo / anulado'][$note->status] }}</span></td>
                                <td class="text-end text-nowrap">
                                    <button wire:click="viewDetail({{ $note->id }})" class="btn btn-sm btn-outline-info" title="Ver detalle"><i class="bx bx-show"></i></button>
                                    <a target="_blank" href="{{ route('dispatch-notes.print', $note) }}" class="btn btn-sm btn-outline-secondary" title="Imprimir"><i class="bx bx-printer"></i></a>
                                    @if($note->status === 'draft')
                                        @can('editar-remito')<button wire:click="edit({{ $note->id }})" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit me-1"></i>Editar</button>@endcan
                                        @can('confirmar-remito')<button wire:click="confirm({{ $note->id }})" wire:confirm="¿Confirmar este remito y actualizar el inventario?" class="btn btn-sm btn-success">Confirmar</button>@endcan
                                        @can('eliminar-remito')<button wire:click="deleteDraft({{ $note->id }})" wire:confirm="¿Eliminar definitivamente este remito en borrador? Sus datos quedarán registrados en el historial." class="btn btn-sm btn-outline-danger"><i class="bx bx-trash me-1"></i>Eliminar</button>@endcan
                                    @elseif($note->status === 'confirmed')
                                        @can('editar-remito')<button wire:click="correct({{ $note->id }})" wire:confirm="Se creará una versión editable. El original seguirá vigente hasta confirmar los cambios. ¿Continuar?" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit me-1"></i>Editar</button>@endcan
                                        @can('anular-remito')<button onclick="const m=prompt('Motivo de anulación (mínimo 10 caracteres):'); if(m){$wire.set('annulReason',m).then(()=>$wire.annul({{ $note->id }}))}" class="btn btn-sm btn-outline-danger">Anular</button>@endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No hay remitos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($dispatchNotes->hasPages())<div class="card-footer">{{ $dispatchNotes->links() }}</div>@endif
    </div>

    @script
        <script>
            $wire.on('document-form-opened', ({ target }) => {
                if (target !== 'dispatch-note-form') return;
                setTimeout(() => document.getElementById(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 75);
            });
        </script>
    @endscript
</div>
