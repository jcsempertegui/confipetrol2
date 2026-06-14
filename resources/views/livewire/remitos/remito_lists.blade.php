@push('title', 'Listar Remitos')

<div class="page-content template-page-wrapper">
    <div class="row align-items-center mb-2 px-2 template-shrink-none">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-primary">Remitos</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Listar Remitos</li>
            </ol>
        </div>
    </div>

    {{-- Filtros de fecha --}}
    <div class="card mb-2 template-shrink-none">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                <div class="col-md-3 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="desde">Fecha Inicial</label>
                        <div class="position-relative input-icon">
                            <input id="desde" class="form-control flatpickr" type="text" wire:model="fromDate"
                                placeholder="Seleccionar Fecha Inicial">
                            <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="hasta">Fecha Final</label>
                        <div class="position-relative input-icon">
                            <input id="hasta" class="form-control flatpickr" type="text" wire:model="toDate"
                                placeholder="Seleccionar Fecha Final">
                            <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="form-group d-flex align-items-end">
                        <button wire:click="RemitosByDate" wire:loading.attr="disabled"
                            class="btn btn-outline-secondary btnIcon" :disabled="!@this.fromDate || !@this.toDate">
                            <span wire:loading.remove wire:target="RemitosByDate">
                                <i class="bx bx-search-alt"></i> CONSULTAR
                            </span>
                            <span wire:loading wire:target="RemitosByDate">
                                <i class="bx bx-spin bx-loader"></i> PROCESANDO...
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Tabla de remitos --}}
    <div class="card template-flex-card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 template-shrink-none">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-receipt"></i>
                <span>Listado de Remitos</span>
            </div>
        </div>

        <div class="card-body px-3 template-flex-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2 template-shrink-none">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Mostrar</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">registros</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @component('components.tools.filterbox', ['filterCount' => (($filter_status != 1 ? 1 : 0) + ($filter_tipo != '' ? 1 : 0))])
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size:0.8rem;">Tipo</label>
                            <select wire:model.live="filter_tipo" class="form-select filter-pro-select">
                                <option value="">TODOS LOS TIPOS</option>
                                <option value="INGRESO">INGRESO</option>
                                <option value="EGRESO">EGRESO</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-1" style="font-size:0.8rem;">Estado</label>
                            <select wire:model.live="filter_status" class="form-select filter-pro-select">
                                <option value="1">ACTIVO</option>
                                <option value="0">ANULADO</option>
                                <option value="">TODOS LOS ESTADOS</option>
                            </select>
                        </div>
                    @endcomponent
                    @include('components.tools.searchbox')
                </div>
            </div>

            <div class="table-responsive template-table-wrapper">
                <table class="table table-hover align-middle table-striped template-table-full">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>N° REMITO</th>
                            <th>TIPO</th>
                            <th>CONTRATO</th>
                            <th>CAMPO</th>
                            <th>USUARIO</th>
                            <th>CANT.</th>
                            <th>FECHA</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($remitos->isEmpty())
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($remitos as $index => $remito)
                                <tr wire:key="remito-row-{{ $remito->id }}">
                                    <td>{{ $startCount - $index }}</td>
                                    <td class="fw-semibold">{{ $remito->remito_number }}</td>
                                    <td>
                                        @if($remito->tipo === 'INGRESO')
                                            <span class="badge rounded-pill text-success bg-light-success">
                                                <i class="bx bx-log-in me-1"></i>INGRESO
                                            </span>
                                        @else
                                            <span class="badge rounded-pill text-danger bg-light-danger">
                                                <i class="bx bx-log-out me-1"></i>EGRESO
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $remito->contrato ?? '—' }}</td>
                                    <td>{{ $remito->campo ?? '—' }}</td>
                                    <td>{{ $remito->user->login ?? $remito->user->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $remito->details_sum_quantity ?? 0 }}</td>
                                    <td>{{ $remito->created_at ? $remito->created_at->format('d/m/Y H:i') : 'S/N' }}</td>
                                    <td>
                                        @if ($remito->status == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">ACTIVO</div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">ANULADO</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            <a href="javascript:;" wire:click="detailRemito({{ $remito->id }})"
                                                data-bs-toggle="modal" data-bs-target="#remitoDetailModal"
                                                class="btn-action-primary" title="Ver detalle">
                                                <i class="bx bx-list-ul"></i>
                                            </a>
                                            @if($remito->status != 0)
                                                @can('eliminar-remito')
                                                <a href="javascript:;" onclick="confirmDeleteRemito({{ $remito->id }})"
                                                    class="btn-action-danger ms-1" title="Anular remito">
                                                    <i class="bx bxs-trash"></i>
                                                </a>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        <tr class="template-tr-spacer">
                            <td colspan="10" class="template-td-spacer"></td>
                        </tr>
                    </tbody>
                    <tfoot class="template-sticky-tfoot">
                        <tr>
                            <td colspan="6" class="text-end">TOTALES:</td>
                            <td class="text-center">{{ $remitos->sum('details_sum_quantity') }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-wrapper">
                {{ $remitos->links() }}
            </div>

            {{-- Modal Detalle --}}
            <div wire:ignore.self class="modal fade" id="remitoDetailModal" tabindex="-1"
                aria-labelledby="remitoDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="remitoDetailModalLabel">
                                <i class="bx bx-receipt me-2"></i>DETALLE DE REMITO
                                @if($tipo_detail)
                                    — <strong>{{ $tipo_detail }}</strong>
                                @endif
                            </h5>
                            <button type="button" class="btn-close {{ $tipo_detail ? 'btn-close-white' : '' }}"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                            <div wire:loading wire:target="detailRemito" class="w-100 text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando detalles...</p>
                            </div>

                            <div wire:loading.remove wire:target="detailRemito">
                                <div class="row mb-2 p-1 g-2">

                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">N° Remito</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $remito_number }}" readonly>
                                    </div>

                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Usuario</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $user_name }}" readonly>
                                    </div>

                                    @if($contrato_detail)
                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Contrato</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $contrato_detail }}" readonly>
                                    </div>
                                    @endif

                                    @if($n_orden_detail)
                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">N° Orden</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $n_orden_detail }}" readonly>
                                    </div>
                                    @endif

                                    @if($senores_detail)
                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Señores</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $senores_detail }}" readonly>
                                    </div>
                                    @endif

                                    @if($atencion_detail)
                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Atención</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $atencion_detail }}" readonly>
                                    </div>
                                    @endif

                                    @if($campo_detail)
                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Campo</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $campo_detail }}" readonly>
                                    </div>
                                    @endif

                                    @if($placa_detail)
                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Placa</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $placa_detail }}" readonly>
                                    </div>
                                    @endif

                                    @if($despachado_detail)
                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Despachado por</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $despachado_detail }}" readonly>
                                    </div>
                                    @endif

                                    @if($transportado_detail)
                                    <div class="col-md-6 col-sm-6">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Transportado por</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $transportado_detail }}" readonly>
                                    </div>
                                    @endif

                                    @if($observations_detail)
                                    <div class="col-12">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Observaciones</label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="{{ $observations_detail }}" readonly>
                                    </div>
                                    @endif

                                </div>

                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table align-middle">
                                        <thead class="sticky-top">
                                            <tr>
                                                <th>PRODUCTO</th>
                                                <th>VARIANTE</th>
                                                <th class="text-center">CANTIDAD</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($remito_details as $detail)
                                                <tr>
                                                    <td>
                                                        {{ $detail->product->name ?? 'Producto Eliminado' }}
                                                        @if($detail->product->code ?? false)
                                                            <br><small class="text-muted">{{ $detail->product->code }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($detail->sku)
                                                            @php
                                                                $parts = array_filter([
                                                                    $detail->sku->size  ? 'Talla: ' . $detail->sku->size->name  : null,
                                                                    $detail->sku->color ? 'Color: ' . $detail->sku->color->name : null,
                                                                ]);
                                                            @endphp
                                                            {{ implode(' / ', $parts) ?: $detail->sku->sku }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">{{ $detail->quantity }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Sin detalles.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2" class="text-end fw-semibold">TOTAL:</td>
                                                <td class="text-center fw-semibold">
                                                    {{ collect($remito_details)->sum('quantity') }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('alert', (data) => {
            const [msg, type] = data;
            if (typeof toast === 'function') {
                toast(msg, type);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({ title: msg, icon: type, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });
    });

    function confirmDeleteRemito(id) {
        Swal.fire({
            title: "¿Está seguro de anular el remito?",
            text: "El remito se anulará de forma permanente y el stock será revertido.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sí, Anular!",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deleteRemito', id);
            }
        });
    }
</script>
