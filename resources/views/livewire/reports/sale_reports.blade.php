@push('title', 'Reporte de Ventas')
<div class="page-content template-page-wrapper">
    <div class="row align-items-center mb-2 px-2 template-shrink-none">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Reportes</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Reporte de Venta</li>
            </ol>
        </div>
    </div>

    <div class="card mb-2 template-shrink-none">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="hasta">Fecha Inicial</label>
                        <div class="position-relative input-icon">
                            <input id="hasta" class="form-control flatpickr" type="text" wire:model="fromDate"
                                placeholder="Seleccionar Fecha Inicial">
                            <span class="position-absolute top-50 translate-middle-y"><i
                                    class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
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
                <div class="col-md-2 col-6">
                    <div class="form-group">
                        <label for="hasta">Seleccionar Usuario</label>
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
                        <label for="hasta">Seleccionar Sucursal</label>
                        <div class="input-group">
                            <select class="form-select" wire:model="branch_id">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
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

    <div class="card d-none d-md-flex template-flex-card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 template-shrink-none">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-box"></i>
                <span>Listar Ventas</span>
            </div>
            <div class="d-flex order-actions">
                <a href="{{ route('sale_reports.saleReportPdf', [
    'fromDate' => $fromDate,
    'toDate' => $toDate,
    'branch_id' => $branch_id,
    'user_id' => $user_id ?: '0',
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
                            <th>N° VENTA</th>
                            <th>USUARIO</th>
                            <th>CLIENTE</th>
                            <th>CODIGO</th>
                            <th>PRODUCTO</th>
                            <th>CANTIDAD</th>
                            <th>PRECIO COMPRA</th>
                            <th>PRECIO VENTA</th>
                            <th>SUBTOTAL</th>
                            <th>UTILIDAD BRUTA</th>
                            <th>FECHA</th>
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
                                    <td>{{ $sale->sale->sale_number ?: 'S/N' }}</td>
                                    <td>{{ $sale->sale->user->login ?? 'S/N' }}</td>
                                    <td>{{ $sale->sale->customer->name ?? 'S/N' }}</td>
                                    <td>{{ $sale->product->code ?? 'N/A' }}</td>
                                    <td>
                                        {{ $sale->product->name ?? 'N/A' }}
                                        @if($sale->detailSkus && $sale->detailSkus->count() > 0)
                                            @foreach($sale->detailSkus as $ds)
                                                @if($ds->productSku)
                                                    <br><small class="text-muted fw-bold">{{ $ds->productSku->color->name ?? 'S/C' }} -
                                                        {{ $ds->productSku->size->name ?? 'S/T' }}</small>
                                                @endif
                                            @endforeach
                                        @endif
                                        @if($sale->unit_name)
                                            <br><small class="text-muted fw-bold">{{ $sale->unit_name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $sale->quantity ?? '0' }}</td>
                                    <td>{{ $sale->purchase_price ?: '0.00' }}</td>
                                    <td>{{ $sale->sale_price ?: '0.00' }}</td>
                                    <td>{{ number_format($sale->subtotal ?? 0, 2) }}</td>
                                    <td>{{ number_format(($sale->sale_price - $sale->purchase_price) * $sale->quantity ?? 0, 2) }}
                                    </td>
                                    <td>{{ $sale->created_at ?: 'S/N' }}</td>
                                </tr>
                            @endforeach
                        @endif
                        <tr class="template-tr-spacer">
                            <td colspan="12" class="template-td-spacer"></td>
                        </tr>
                    </tbody>
                    <tfoot class="template-sticky-tfoot">
                        <tr>
                            <td class="text-muted small">{{ $totalProducts }} items</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ $totalQuantitySold }}</td>
                            <td></td>
                            <td></td>
                            <td>{{ number_format($totalSalesAmount, 2) }}</td>
                            <td>{{ number_format($totalProfit, 2) }}</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="3" class="text-end pe-2">EFECTIVO:</td>
                            <td>{{ number_format($totalEffective, 2) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="3" class="text-end pe-2">QR:</td>
                            <td>{{ number_format($totalQR, 2) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="3" class="text-end pe-2">TARJETA:</td>
                            <td>{{ number_format($totalCard, 2) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="3" class="text-end pe-2">TOTAL PAGOS:</td>
                            <td>{{ number_format($totalPayments, 2) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-wrapper">
                {{ $sales->links() }}
            </div>
        </div>
    </div>

    <div class="card d-md-none">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-box"></i>
                <span>Listar Ventas</span>
            </div>
            <div class="d-flex order-actions">
                <a href="{{ route('sale_reports.saleReportPdf', [
    'fromDate' => $fromDate,
    'toDate' => $toDate,
    'branch_id' => $branch_id,
    'user_id' => $user_id ?: '0',
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
                            <th>N° VENTA</th>
                            <th>USUARIO</th>
                            <th>CLIENTE</th>
                            <th>CODIGO</th>
                            <th>PRODUCTO</th>
                            <th>CANTIDAD</th>
                            <th>PRECIO COMPRA</th>
                            <th>PRECIO VENTA</th>
                            <th>SUBTOTAL</th>
                            <th>UTILIDAD BRUTA</th>
                            <th>FECHA</th>
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
                                    <td>{{ $sale->sale->sale_number ?: 'S/N' }}</td>
                                    <td>{{ $sale->sale->user->login ?? 'S/N' }}</td>
                                    <td>{{ $sale->sale->customer->name ?? 'S/N' }}</td>
                                    <td>{{ $sale->product->code ?? 'N/A' }}</td>
                                    <td>
                                        {{ $sale->product->name ?? 'N/A' }}
                                        @if($sale->detailSkus && $sale->detailSkus->count() > 0)
                                            @foreach($sale->detailSkus as $ds)
                                                @if($ds->productSku)
                                                    <br><small class="text-muted fw-bold">{{ $ds->productSku->color->name ?? 'S/C' }} -
                                                        {{ $ds->productSku->size->name ?? 'S/T' }}</small>
                                                @endif
                                            @endforeach
                                        @endif
                                        @if($sale->unit_name)
                                            <br><small class="text-muted fw-bold">{{ $sale->unit_name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $sale->quantity ?? '0' }}</td>
                                    <td>{{ $sale->purchase_price ?: '0.00' }}</td>
                                    <td>{{ $sale->sale_price ?: '0.00' }}</td>
                                    <td>{{ number_format($sale->subtotal ?? 0, 2) }}</td>
                                    <td>{{ number_format(($sale->sale_price - $sale->purchase_price) * $sale->quantity ?? 0, 2) }}
                                    </td>
                                    <td>{{ $sale->created_at ?: 'S/N' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot class="template-sticky-tfoot">
                        <tr>
                            <td class="text-muted small">{{ $totalProducts }} items</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ $totalQuantitySold }}</td>
                            <td></td>
                            <td></td>
                            <td>{{ number_format($totalSalesAmount, 2) }}</td>
                            <td>{{ number_format($totalProfit, 2) }}</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="3" class="text-end pe-2">EFECTIVO:</td>
                            <td>{{ number_format($totalEffective, 2) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="3" class="text-end pe-2">QR:</td>
                            <td>{{ number_format($totalQR, 2) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="3" class="text-end pe-2">TARJETA:</td>
                            <td>{{ number_format($totalCard, 2) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td colspan="3" class="text-end pe-2">TOTAL PAGOS:</td>
                            <td>{{ number_format($totalPayments, 2) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-mobile">
                {{ $sales->links() }}
            </div>
        </div>
    </div>
</div>