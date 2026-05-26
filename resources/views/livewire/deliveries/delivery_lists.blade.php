@push('title', 'Listar Entregas EPP')

<div class="page-content template-page-wrapper">
    <div class="row align-items-center mb-2 px-2 template-shrink-none">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Entregas EPP</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Listar Entregas</li>
            </ol>
        </div>
    </div>

    <div class="card mb-2 template-shrink-none">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                <div class="col-md-3 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="desde">Fecha Inicial</label>
                        <div class="position-relative input-icon">
                            <input id="desde" class="form-control flatpickr" type="text" wire:model="fromDate"
                                placeholder="Seleccionar Fecha Inicial">
                            <span class="position-absolute top-50 translate-middle-y"><i
                                    class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="hasta">Fecha Final</label>
                        <div class="position-relative input-icon">
                            <input id="hasta" class="form-control flatpickr" type="text" wire:model="toDate"
                                placeholder="Seleccionar Fecha Final">
                            <span class="position-absolute top-50 translate-middle-y"><i
                                    class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="form-group d-flex align-items-end">
                        <button wire:click="DeliveriesByDate" wire:loading.attr="disabled"
                            class="btn btn-outline-secondary btnIcon" :disabled="!@this.fromDate || !@this.toDate">
                            <span wire:loading.remove wire:target="DeliveriesByDate">
                                <i class="bx bx-search-alt"></i>
                                CONSULTAR
                            </span>
                            <span wire:loading wire:target="DeliveriesByDate">
                                <i class="bx bx-spin bx-loader"></i>
                                PROCESANDO...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card template-flex-card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 template-shrink-none">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-box"></i>
                <span>Listado de Entregas</span>
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
                <div class="d-flex align-items-center gap-2">
                    @component('components.tools.filterbox', ['filterCount' => ($filter_status != 1 ? 1 : 0)])
                    <div class="mb-2">
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
                            <th>N° ENTREGA</th>
                            <th>TRABAJADOR</th>
                            <th>SUCURSAL</th>
                            <th>USUARIO</th>
                            <th>CANT.</th>
                            <th>FECHA</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($deliveries->isEmpty())
                            <tr>
                                <td colspan="10" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($deliveries as $index => $delivery)
                                <tr wire:key="delivery-row-{{ $delivery->id }}">
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $delivery->delivery_number }}</td>
                                    <td>{{ $delivery->worker ? $delivery->worker->name . ' ' . $delivery->worker->last_name : 'N/A' }}</td>
                                    <td>{{ $delivery->branch->name ?? 'N/A' }}</td>
                                    <td>{{ $delivery->user->login ?? $delivery->user->name ?? 'N/A' }}</td>
                                    
                                    <td>
                                        {{ $delivery->details_sum_quantity ?? 0 }}
                                    </td>
                                    <td>{{ $delivery->created_at ? $delivery->created_at->format('d/m/Y H:i') : 'S/N' }}</td>
                                    <td>
                                        @if ($delivery->status == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">ACTIVO</div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">ANULADO</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            <a href="javascript:;" wire:click="detailDelivery({{ $delivery->id }})"
                                                data-bs-toggle="modal" data-bs-target="#deliveryDetailModal"
                                                class="btn-action-primary" title="Ver detalle">
                                                <i class="bx bx-list-ul"></i>
                                            </a>

                                            @if($delivery->status != 0)
                                                <a href="javascript:;" onclick="confirmDeleteDelivery({{ $delivery->id }})"
                                                    class="btn-action-danger ms-1" title="Anular entrega">
                                                    <i class="bx bxs-trash"></i>
                                                </a>
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
                            <td colspan="5" class="text-end">TOTALES:</td>
                            <td class="text-center">{{ $deliveries->sum('details_count') }}</td>
                            <td class="text-center">{{ $deliveries->sum('details_sum_quantity') }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-wrapper">
                {{ $deliveries->links() }}
            </div>

            <div wire:ignore.self class="modal fade" id="deliveryDetailModal" tabindex="-1"
                aria-labelledby="deliveryDetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deliveryDetailModalLabel">
                                DETALLE DE ENTREGA
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div wire:loading wire:target="detailDelivery" class="w-100 text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando detalles de productos...</p>
                            </div>

                            <div wire:loading.remove wire:target="detailDelivery">
                                <div class="row mb-2 p-1">

                                    <div class="col-md-6 col-sm-6 mb-2">
                                        <label>Trabajador</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Trabajador"
                                                value="{{ $worker_name }}" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-6 mb-2">
                                        <label>N° Entrega</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control"
                                                placeholder="N° Entrega" value="{{ $delivery_number }}" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-6 mb-2">
                                        <label>Usuario</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Usuario"
                                                value="{{ $user_name }}" readonly>
                                        </div>
                                    </div>

                                    @if($observations_detail)
                                        <div class="col-md-6 col-sm-6 mb-2">
                                            <label>Observaciones</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    value="{{ $observations_detail }}" readonly>
                                            </div>
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
                                            @forelse($delivery_details as $detail)
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
                                                <td colspan="2" class="text-end">TOTAL:</td>
                                                <td class="text-center">
                                                    {{ collect($delivery_details)->sum('quantity') }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                    Cancelar
                                </button>
                            </div>
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

    function confirmDeleteDelivery(id) {
        Swal.fire({
            title: "¿Está seguro de anular la entrega?",
            text: "La entrega se anulará de forma permanente. El stock de los EPPs será restaurado.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, Anular!"
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deleteDelivery', id);
            }
        });
    }
</script>