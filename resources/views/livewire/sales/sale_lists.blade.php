@push('title', 'Listar Ventas')

<div class="page-content template-page-wrapper">
    <div class="row align-items-center mb-2 px-2 template-shrink-none">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Ventas</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Listar Ventas</li>
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
                        <button wire:click="SalesByDate" wire:loading.attr="disabled"
                            class="btn btn-outline-secondary btnIcon" :disabled="!@this.fromDate || !@this.toDate">
                            <span wire:loading.remove wire:target="SalesByDate">
                                <i class="bx bx-search-alt"></i>
                                CONSULTAR
                            </span>
                            <span wire:loading wire:target="SalesByDate">
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
                <span>Listar Ventas</span>
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
                    @component('components.tools.filterbox', ['filterCount' => ($filter_status != 1 ? 1 : 0) + ($filter_payment !== '' ? 1 : 0)])
                    <div class="mb-2">
                        <select wire:model.live="filter_status" class="form-select filter-pro-select">
                            <option value="1">ACTIVO</option>
                            <option value="0">ANULADO</option>
                            <option value="">TODOS LOS ESTADOS</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <select wire:model.live="filter_payment" class="form-select filter-pro-select">
                            <option value="">TODOS LOS MÉTODOS</option>
                            <option value="EFECTIVO">EFECTIVO</option>
                            <option value="TARJETA">TARJETA</option>
                            <option value="QR">QR</option>
                            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                            <option value="CREDITO">CRÉDITO</option>
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
                            <th>N° VENTA</th>
                            <th>METODO</th>
                            <th>SUBTOTAL</th>
                            <th>DESCUENTO</th>
                            <th>TOTAL</th>
                            <th>TRABAJADOR</th>
                            <th>SUCURSAL</th>
                            <th>USUARIO</th>
                            <th>FECHA</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($sales->isEmpty())
                            <tr>
                                <td colspan="12" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($sales as $index => $sale)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $sale->sale_number ?: 'S/N' }}</td>
                                    <td>
                                        @foreach ($sale->payments as $payment)
                                            <p class="text-sm mb-0">• {{ $payment->description }} - Bs.
                                                {{ number_format($payment->amount, 2) }}
                                            </p>
                                        @endforeach
                                    </td>
                                    <td>{{ number_format($sale->total + $sale->discount, 2) }}</td>
                                    <td>{{ number_format($sale->discount, 2) }}</td>
                                    <td>{{ number_format($sale->total, 2) }}</td>
                                    <td>{{ $sale->worker ? $sale->worker->name . ' ' . $sale->worker->last_name : 'N/A' }}</td>
                                    <td>{{ $sale->branch->name ?? 'N/A' }}</td>
                                    <td>{{ $sale->user->login ?? $sale->user->name ?? 'N/A' }}</td>
                                    <td>{{ $sale->created_at ?: 'S/N' }}</td>
                                    <td>
                                        @if ($sale->status == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">
                                                ACTIVO
                                            </div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">
                                                ANULADO
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            <a href="javascript:;" wire:click="detailSales({{ $sale->id }})"
                                                data-bs-toggle="modal" data-bs-target="#theModal" class="btn-action-primary"><i
                                                    class="bx bx-list-ul"></i></a>

                                            @if($sale->status != 0)
                                                @can('eliminar-ventas')
                                                    <a href="javascript:;" onclick="confirmDelete({{ $sale->id }}, 'delete')"
                                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                                @endcan
                                            @endif

                                            @if($sale->status != 0)
                                                @can('editar-ventas')
                                                    @if(isset($sale->branch) && $sale->branch->pos_type == 2)
                                                        <a href="{{ route('sales_interface.edit', ['sale_id' => $sale->id]) }}"
                                                            class="btn-action-warning ms-1" title="Editar Venta Interfaz">
                                                            <i class="bx bxs-edit-alt"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('sales.edit', ['sale_id' => $sale->id]) }}"
                                                            class="btn-action-warning ms-1" title="Editar Venta">
                                                            <i class="bx bxs-edit-alt"></i>
                                                        </a>
                                                    @endif
                                                @endcan
                                            @endif

                                            <a href="javascript:;" class="btn-action-secondary ms-1" title="Imprimir no disponible"><i
                                                    class="bx bxs-file"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        <tr class="template-tr-spacer">
                            <td colspan="12" class="template-td-spacer"></td>
                        </tr>
                    </tbody>
                    <tfoot class="template-sticky-tfoot">
                        <tr>
                            <td colspan="3" class="text-end">TOTALES:</td>
                            <td>Bs. {{ number_format($total_subtotal ?? 0, 2) }}</td>
                            <td>Bs. {{ number_format($total_discount ?? 0, 2) }}</td>
                            <td>Bs. {{ number_format($total_amount ?? 0, 2) }}</td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-wrapper">
                {{ $sales->links() }}
            </div>

            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                DETALLE DE LA VENTA</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div wire:loading wire:target="detailSales" class="w-100 text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando detalles de productos...</p>
                            </div>

                            <div wire:loading.remove wire:target="detailSales">
                                <div class="row mb-2 p-1">

                                    <div class="col-md-6 col-sm-6 mb-2">
                                        <label>Trabajador</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Trabajador"
                                                value="{{ $worker }}" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-6 mb-2">
                                        <label>N° Venta</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="N° Factura"
                                                value="{{ $sale_number }}" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-6 mb-2">
                                        <label>Usuario</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Usuario"
                                                value="{{ $user }}" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-6 mb-2">
                                        <label>Total a Pagar</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control fw-bold text-success"
                                                placeholder="Total" value="Bs. {{ number_format($total, 2) }}" readonly>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="fw-bold border-bottom pb-2">MÉTODOS DE PAGO</h6>
                                        <div class="row mt-3">
                                            @forelse($sale_payments as $payment)
                                                <div class="col-md-4 col-sm-6 mb-3">
                                                    <div class="lvpayment-card">
                                                        <div class="lvpayment-img-container">
                                                            @php
                                                                $desc = strtoupper($payment->description);
                                                                $img = 'payment_efectivo.jpg';
                                                                if (str_contains($desc, 'TARJETA'))
                                                                    $img = 'payment_tarjeta.png';
                                                                elseif (str_contains($desc, 'QR'))
                                                                    $img = 'payment_qr.png';
                                                                elseif (str_contains($desc, 'CREDITO') || str_contains($desc, 'CRÉDITO'))
                                                                    $img = 'payment_credito.png';
                                                                elseif (str_contains($desc, 'TRANSFERENCIA'))
                                                                    $img = 'payment_transferencia.png';
                                                                elseif (str_contains($desc, 'MULTIPLE') || str_contains($desc, 'MÚLTIPLE'))
                                                                    $img = 'payment_convinado.png';
                                                            @endphp
                                                            <img src="{{ asset('assets/images/payments/' . $img) }}"
                                                                alt="{{ $payment->description }}" class="lvpayment-icon">
                                                        </div>
                                                        <div class="lvpayment-info">
                                                            <span class="lvpayment-title">{{ $payment->description }}</span>
                                                            <span class="lvpayment-amount">Bs.
                                                                {{ number_format($payment->amount, 2) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-12">
                                                    <span class="text-muted small">No se registraron pagos.</span>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table align-middle">
                                        <thead class="sticky-top ">
                                            <tr>
                                                <th>PRODUCTO</th>
                                                <th>CANTIDAD</th>
                                                <th>PRECIO VENTA</th>
                                                <th>SUBTOTAL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sale_details as $detail)
                                                <tr>
                                                    <td>
                                                        {{ $detail->product->name ?? 'Producto Eliminado' }}
                                                        @if($detail->unit)
                                                            <br><small class="text-muted fw-bold">{{ $detail->unit->name }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $detail->quantity }}</td>
                                                    <td>
                                                        <div>
                                                            @if ($detail->price_type == 'wholesale')
                                                                <span
                                                                    class="badge rounded-pill text-success bg-light-success text-uppercase mb-1">Precio
                                                                    por Mayor</span>
                                                                <div class="fw-bold text-success">
                                                                    Bs. {{ number_format($detail->sale_price, 2) }}
                                                                </div>
                                                                @if ($detail->wholesale_min_quantity)
                                                                    <small class="text-muted">Min:
                                                                        {{ $detail->wholesale_min_quantity }}
                                                                        unidades</small>
                                                                @endif
                                                            @elseif ($detail->price_type == 'custom')
                                                                <span
                                                                    class="badge rounded-pill text-warning bg-light-warning text-uppercase mb-1">Precio
                                                                    Personalizado</span>
                                                                <div class="fw-bold text-warning">
                                                                    Bs. {{ number_format($detail->sale_price, 2) }}
                                                                </div>
                                                                <small class="text-muted">Modificado por usuario</small>
                                                            @elseif ($detail->price_type == 'price_3')
                                                                <span
                                                                    class="badge rounded-pill text-info bg-light-info text-uppercase mb-1">Precio
                                                                    2</span>
                                                                <div class="fw-bold text-info">
                                                                    Bs. {{ number_format($detail->sale_price, 2) }}
                                                                </div>
                                                                <small class="text-muted">Precio alternativo 2</small>
                                                            @elseif ($detail->price_type == 'price_4')
                                                                <span
                                                                    class="badge rounded-pill text-info bg-light-info text-uppercase mb-1">Precio
                                                                    3</span>
                                                                <div class="fw-bold text-info">
                                                                    Bs. {{ number_format($detail->sale_price, 2) }}
                                                                </div>
                                                                <small class="text-muted">Precio alternativo 3</small>
                                                            @else
                                                                <span
                                                                    class="badge rounded-pill text-primary bg-light-primary text-uppercase mb-1">Precio
                                                                    Normal</span>
                                                                <div class="fw-bold text-primary">
                                                                    Bs. {{ number_format($detail->sale_price, 2) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>{{ $detail->subtotal }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end">SUBTOTAL:</td>
                                                <td>Bs. {{ number_format($total + $discount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end">DESCUENTO:</td>
                                                <td>Bs. {{ number_format($discount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end">TOTAL A PAGAR:</td>
                                                <td>Bs. {{ number_format($total, 2) }}</td>
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

    function confirmDelete(id, action) {
        Swal.fire({
            title: "¿Está seguro de anular la venta?",
            text: "El registro se anulara de forma permanente. El stock de los productos cambiarán!!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, Anular!"
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('delete', id);
            }
        });
    }
</script>