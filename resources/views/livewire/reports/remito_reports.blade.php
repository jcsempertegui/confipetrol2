@push('title', 'Reporte de Remitos')
<div class="page-content template-page-wrapper">
    <div class="row align-items-center mb-2 px-2 template-shrink-none">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-primary">Reportes</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Reporte de Remitos</li>
            </ol>
        </div>
    </div>

    <div class="card mb-2 template-shrink-none">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="fromDate">Fecha Inicial</label>
                        <div class="position-relative input-icon">
                            <input id="fromDate" class="form-control flatpickr" type="text" wire:model="fromDate"
                                placeholder="Seleccionar Fecha Inicial">
                            <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="toDate">Fecha Final</label>
                        <div class="position-relative input-icon">
                            <input id="toDate" class="form-control flatpickr" type="text" wire:model="toDate"
                                placeholder="Seleccionar Fecha Final">
                            <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Tipo</label>
                        <div class="input-group">
                            <select class="form-select" wire:model.live="filter_tipo">
                                <option value="">Todos</option>
                                <option value="INGRESO">INGRESO</option>
                                <option value="EGRESO">EGRESO</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Trabajador</label>
                        <div class="input-group">
                            <select class="form-select" wire:model="worker_id">
                                <option value="">Todos</option>
                                @foreach ($workers as $worker)
                                    <option value="{{ $worker->id }}">{{ $worker->name }} {{ $worker->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Usuario</label>
                        <div class="input-group">
                            <select class="form-select" wire:model="user_id">
                                <option value="">Todos</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->login }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label>Seleccionar Sucursal</label>
                        <div class="input-group">
                            <select class="form-select" wire:model="branch_id">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="form-group d-flex align-items-end">
                        <button wire:click="RemitosByDate" wire:loading.attr="disabled"
                            class="btn btn-outline-secondary btnIcon" :disabled="!@this.fromDate || !@this.toDate">
                            <span wire:loading.remove wire:target="RemitosByDate">
                                <i class="bx bx-search-alt"></i>
                                CONSULTAR
                            </span>
                            <span wire:loading wire:target="RemitosByDate">
                                <i class="bx bx-spin bx-loader"></i>
                                PROCESANDO...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DESKTOP --}}
    <div class="card d-none d-md-flex template-flex-card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 template-shrink-none">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-receipt"></i>
                <span>Listar Remitos</span>
            </div>
            <div class="d-flex order-actions">
                <a href="{{ route('remito_reports.remitoReportPdf', [
                    'fromDate'  => $fromDate,
                    'toDate'    => $toDate,
                    'branch_id' => $branch_id,
                    'user_id'   => $user_id ?: '0',
                    'tipo'      => $filter_tipo ?: '0',
                    'worker_id' => $worker_id ?: '0',
                ]) }}" target="_blank" class="btn-action-danger"><i class="bx bxs-file-pdf"></i></a>
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
                @include('components.tools.searchbox')
            </div>

            <div class="table-responsive template-table-wrapper">
                <table class="table table-hover align-middle table-striped template-table-full">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>N° REMITO</th>
                            <th>TIPO</th>
                            <th>TRABAJADOR</th>
                            <th>CONTRATO</th>
                            <th>CAMPO</th>
                            <th>USUARIO</th>
                            <th>CODIGO</th>
                            <th>PRODUCTO</th>
                            <th>TALLA / COLOR</th>
                            <th>CANTIDAD</th>
                            <th>ALMACÉN</th>
                            <th>FECHA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($remitos->isEmpty())
                            <tr>
                                <td colspan="13" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($remitos as $index => $detail)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td class="fw-semibold">{{ $detail->remito->remito_number ?? 'S/N' }}</td>
                                    <td>
                                        @if(($detail->remito->tipo ?? '') === 'INGRESO')
                                            <span class="badge rounded-pill text-success bg-light-success">
                                                <i class="bx bx-log-in me-1"></i>INGRESO
                                            </span>
                                        @elseif(($detail->remito->tipo ?? '') === 'EGRESO')
                                            <span class="badge rounded-pill text-danger bg-light-danger">
                                                <i class="bx bx-log-out me-1"></i>EGRESO
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($detail->remito->worker)
                                            {{ $detail->remito->worker->name }} {{ $detail->remito->worker->last_name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->remito->contrato ?? '—' }}</td>
                                    <td>{{ $detail->remito->campo ?? '—' }}</td>
                                    <td>{{ $detail->remito->user->login ?? $detail->remito->user->name ?? 'S/N' }}</td>
                                    <td>{{ $detail->product->code ?? 'N/A' }}</td>
                                    <td>
                                        {{ $detail->product->name ?? 'N/A' }}
                                        @if($detail->sku)
                                            <br><small class="text-muted fw-bold">
                                                {{ $detail->sku->color->name ?? 'S/C' }} - {{ $detail->sku->size->name ?? 'S/T' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($detail->sku)
                                            <small class="text-muted">
                                                {{ $detail->sku->color->name ?? 'S/C' }} / {{ $detail->sku->size->name ?? 'S/T' }}
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->quantity ?? '0' }}</td>
                                    <td>{{ $detail->warehouse->name ?? 'S/N' }}</td>
                                    <td>{{ $detail->created_at ?? 'S/N' }}</td>
                                </tr>
                            @endforeach
                        @endif
                        <tr class="template-tr-spacer">
                            <td colspan="13" class="template-td-spacer"></td>
                        </tr>
                    </tbody>
                    <tfoot class="template-sticky-tfoot">
                        <tr>
                            <td class="text-muted small">{{ $totalItems }} items</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ $totalQuantityRemito }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-wrapper">
                {{ $remitos->links() }}
            </div>
        </div>
    </div>

    {{-- MOBILE --}}
    <div class="card d-md-none">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-receipt"></i>
                <span>Listar Remitos</span>
            </div>
            <div class="d-flex order-actions">
                <a href="{{ route('remito_reports.remitoReportPdf', [
                    'fromDate'  => $fromDate,
                    'toDate'    => $toDate,
                    'branch_id' => $branch_id,
                    'user_id'   => $user_id ?: '0',
                    'tipo'      => $filter_tipo ?: '0',
                    'worker_id' => $worker_id ?: '0',
                ]) }}" target="_blank" class="btn-action-danger"><i class="bx bxs-file-pdf"></i></a>
            </div>
        </div>

        <div class="card-body px-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Mostrar</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm w-auto">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">registros</span>
                </div>
                @include('components.tools.searchbox')
            </div>

            <div class="table-responsive template-table-wrapper-mobile">
                <table class="table table-hover align-middle table-striped template-table-mobile">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>N° REMITO</th>
                            <th>TIPO</th>
                            <th>TRABAJADOR</th>
                            <th>CONTRATO</th>
                            <th>CAMPO</th>
                            <th>USUARIO</th>
                            <th>CODIGO</th>
                            <th>PRODUCTO</th>
                            <th>TALLA / COLOR</th>
                            <th>CANTIDAD</th>
                            <th>ALMACÉN</th>
                            <th>FECHA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($remitos->isEmpty())
                            <tr>
                                <td colspan="13" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($remitos as $index => $detail)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td class="fw-semibold">{{ $detail->remito->remito_number ?? 'S/N' }}</td>
                                    <td>
                                        @if(($detail->remito->tipo ?? '') === 'INGRESO')
                                            <span class="badge rounded-pill text-success bg-light-success">INGRESO</span>
                                        @elseif(($detail->remito->tipo ?? '') === 'EGRESO')
                                            <span class="badge rounded-pill text-danger bg-light-danger">EGRESO</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($detail->remito->worker)
                                            {{ $detail->remito->worker->name }} {{ $detail->remito->worker->last_name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->remito->contrato ?? '—' }}</td>
                                    <td>{{ $detail->remito->campo ?? '—' }}</td>
                                    <td>{{ $detail->remito->user->login ?? $detail->remito->user->name ?? 'S/N' }}</td>
                                    <td>{{ $detail->product->code ?? 'N/A' }}</td>
                                    <td>
                                        {{ $detail->product->name ?? 'N/A' }}
                                        @if($detail->sku)
                                            <br><small class="text-muted fw-bold">
                                                {{ $detail->sku->color->name ?? 'S/C' }} - {{ $detail->sku->size->name ?? 'S/T' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($detail->sku)
                                            <small class="text-muted">
                                                {{ $detail->sku->color->name ?? 'S/C' }} / {{ $detail->sku->size->name ?? 'S/T' }}
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->quantity ?? '0' }}</td>
                                    <td>{{ $detail->warehouse->name ?? 'S/N' }}</td>
                                    <td>{{ $detail->created_at ?? 'S/N' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot class="template-sticky-tfoot">
                        <tr>
                            <td class="text-muted small">{{ $totalItems }} items</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ $totalQuantityRemito }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-mobile">
                {{ $remitos->links() }}
            </div>
        </div>
    </div>
</div>