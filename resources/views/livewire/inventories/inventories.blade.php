@push('title', 'Stock')

<div class="page-content"
    style="height: calc(100vh - 60px); overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0;">
    <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-primary">Inventario</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Stock</li>
            </ol>
        </div>
    </div>

    <div class="d-flex gap-2 mt-1 mb-2" style="flex-shrink: 0;">
        <div class="form-group">
            <label for="hasta">Seleccionar Sucursal</label>
            <div class="input-group">
                <select class="form-select" data-placeholder="Buscar Sucursal..." wire:model="warehouse_id">
                    <option value="">Todos</option>
                    @foreach ($warehousesList as $wh)
                        <option value="{{ $wh['id'] }}">{{ $wh['display_name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group d-flex align-items-end">
            <button class="btn btn-outline-secondary btnIcon" wire:click.prevent="searchBybranch"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="searchBybranch">
                    <i class="bx bx-search-alt"></i>
                    CONSULTAR
                </span>
                <span wire:loading wire:target="searchBybranch">
                    <i class="bx bx-spin bx-loader"></i>
                    PROCESANDO...
                </span>
            </button>
        </div>
    </div>

    <div class="card d-none d-md-flex template-flex-card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 template-shrink-none">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-box"></i>
                <span class="fw-semibold">Stock de Productos</span>
            </div>
            <div class="d-flex order-actions">
                <a href="javascript:;" class="btn-action-success" wire:click="exportExcel" title="Descargar Excel"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="exportExcel"><i class="bx bxs-file-export"></i></span>
                    <span wire:loading wire:target="exportExcel"><i class="bx bx-spin bx-loader"></i></span>
                </a>
                <a href="javascript:;" class="btn-action-danger ms-1" wire:click="exportPdf"
                    title="Descargar Inventario Valorado PDF" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="exportPdf"><i class="bx bxs-file-pdf"></i></span>
                    <span wire:loading wire:target="exportPdf"><i class="bx bx-spin bx-loader"></i></span>
                </a>
            </div>
        </div>

        <div class="card-body px-3 template-flex-body">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2 template-shrink-none">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Mostrar</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">registros</span>
                </div>
                @include('components.tools.searchbox')
            </div>

            <div class="table-responsive template-table-wrapper">
                <table class="table table-hover align-middle table-striped template-table-full" id="theTable"
                    style="width: 100%;">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>CODIGO</th>
                            <th>PRODUCTO</th>
                            <th>TIPO PRODUCTO</th>
                            <th>STOCK MINIMO</th>
                            <th>STOCK</th>
                            <th>SUCURSAL</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($inventories->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($inventories as $index => $inventorie)
                                <tr
                                    class="{{ ($inventorie->stock <= $inventorie->product->minimum_stock) ? 'table-danger' : '' }}">
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $inventorie->product->code ?: 'S/N' }}</td>
                                    <td>{{ $inventorie->product->name ?: 'S/N' }}</td>
                                    <td>
                                        @if ($inventorie->product->type == 0)
                                            <div class="badge rounded-pill text-primary bg-light-primary text-uppercase">Producto
                                            </div>
                                        @elseif ($inventorie->product->type == 3)
                                            <div class="badge rounded-pill text-warning bg-light-warning text-uppercase">Insumo
                                            </div>
                                        @else
                                            <div class="badge rounded-pill text-secondary bg-light-secondary text-uppercase">Otro
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $inventorie->product->minimum_stock ?: '0' }}</td>
                                    <td>{{ $inventorie->stock ?: '0' }}</td>
                                    <td>
                                        {{ $inventorie->branch->name ?: 'S/N' }}
                                        @php
                                            $warehouseCount = collect($warehousesList)->where('branch_id', $inventorie->branch_id)->count();
                                        @endphp
                                        @if($warehouseCount > 1)
                                            <br><small class="text-muted mt-0">{{ $inventorie->warehouse->name ?? '' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            <a href="javascript:;" wire:click="openAdjustModal({{ $inventorie->id }})"
                                                data-bs-toggle="modal" data-bs-target="#adjustStockModal"
                                                class="btn-action-success" title="Ajustar Stock">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            @if ($inventorie->product->lote == 1)
                                                <a href="javascript:;"
                                                    wire:click="openLotesModal({{ $inventorie->product->id }}, '{{ $inventorie->product->name }}', '{{ $inventorie->product->code }}', {{ $inventorie->branch_id }})"
                                                    data-bs-toggle="modal" data-bs-target="#lotesModal"
                                                    class="btn-action-primary ms-1" title="Gestionar Lotes">
                                                    <i class="bx bx-list-ul"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        <tr class="template-tr-spacer">
                            <td colspan="7" class="template-td-spacer"></td>
                        </tr>
                    </tbody>
                    <tfoot class="template-sticky-tfoot">
                        <tr>
                            <td class="text-muted small">{{ $inventories->total() }} items</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-wrapper">
                {{ $inventories->links() }}
            </div>
        </div>
    </div>

    {{-- MOBILE --}}
    <div class="card d-md-none">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-box"></i>
                <span class="fw-semibold">Stock de Productos</span>
            </div>
            <div class="d-flex order-actions">
                <a href="javascript:;" class="btn-action-success" wire:click="exportExcel" title="Descargar Excel"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="exportExcel"><i class="bx bxs-file-export"></i></span>
                    <span wire:loading wire:target="exportExcel"><i class="bx bx-spin bx-loader"></i></span>
                </a>
                <a href="javascript:;" class="btn-action-danger ms-1" wire:click="exportPdf"
                    title="Descargar Inventario Valorado PDF" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="exportPdf"><i class="bx bxs-file-pdf"></i></span>
                    <span wire:loading wire:target="exportPdf"><i class="bx bx-spin bx-loader"></i></span>
                </a>
            </div>
        </div>

        <div class="card-body px-3">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2">
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
                <table class="table table-hover align-middle table-striped template-table-mobile" style="width: 100%;">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>CODIGO</th>
                            <th>PRODUCTO</th>
                            <th>TIPO PRODUCTO</th>
                            <th>STOCK MINIMO</th>
                            <th>STOCK</th>
                            <th>SUCURSAL</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($inventories->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($inventories as $index => $inventorie)
                                <tr
                                    class="{{ ($inventorie->stock <= $inventorie->product->minimum_stock) ? 'table-danger' : '' }}">
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $inventorie->product->code ?: 'S/N' }}</td>
                                    <td>{{ $inventorie->product->name ?: 'S/N' }}</td>
                                    <td>
                                        @if ($inventorie->product->type == 0)
                                            <div class="badge rounded-pill text-primary bg-light-primary text-uppercase">Producto
                                            </div>
                                        @elseif ($inventorie->product->type == 3)
                                            <div class="badge rounded-pill text-warning bg-light-warning text-uppercase">Insumo
                                            </div>
                                        @else
                                            <div class="badge rounded-pill text-secondary bg-light-secondary text-uppercase">Otro
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $inventorie->product->minimum_stock ?: '0' }}</td>
                                    <td>{{ $inventorie->stock ?: '0' }}</td>
                                    <td>
                                        {{ $inventorie->branch->name ?: 'S/N' }}
                                        @php
                                            $warehouseCount = collect($warehousesList)->where('branch_id', $inventorie->branch_id)->count();
                                        @endphp
                                        @if($warehouseCount > 1)
                                            <br><small class="text-muted mt-0">{{ $inventorie->warehouse->name ?? '' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            <a href="javascript:;" wire:click="openAdjustModal({{ $inventorie->id }})"
                                                data-bs-toggle="modal" data-bs-target="#adjustStockModal"
                                                class="btn-action-success" title="Ajustar Stock">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            @if ($inventorie->product->lote == 1)
                                                <a href="javascript:;"
                                                    wire:click="openLotesModal({{ $inventorie->product->id }}, '{{ $inventorie->product->name }}', '{{ $inventorie->product->code }}', {{ $inventorie->branch_id }})"
                                                    data-bs-toggle="modal" data-bs-target="#lotesModal"
                                                    class="btn-action-primary ms-1" title="Gestionar Lotes">
                                                    <i class="bx bx-list-ul"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot class="template-sticky-tfoot">
                        <tr>
                            <td class="text-muted small">{{ $inventories->total() }} items</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="template-pagination-mobile">
                {{ $inventories->links() }}
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adjustStockModalLabel">AJUSTAR STOCK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($selectedInventory)
                        <div class="mb-3">
                            <p><strong>Producto:</strong> {{ $selectedInventory['product_name'] }}</p>
                            <p><strong>Código:</strong> {{ $selectedInventory['product_code'] }}</p>
                            <p><strong>Stock Actual General:</strong> {{ $selectedInventory['current_stock'] }}</p>
                            <p><strong>Sucursal:</strong> {{ $selectedInventory['branch_name'] }}</p>
                        </div>

                        @if ($selectedInventory['has_lote'])
                            <div class="mb-3">
                                <label class="form-label">Seleccionar Lote</label>
                                <select class="form-select" wire:model.live="selected_lot_id">
                                    <option value="">Seleccione un lote</option>
                                    @foreach ($availableLots as $lot)
                                        <option value="{{ $lot->id }}">
                                            {{ $lot->lot_number }} - Stock: {{ $lot->quantity }}
                                            @if($lot->expiration_date)
                                                - Vence: {{ $lot->expiration_date }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('selected_lot_id')
                                    <span class="text-danger er">{{ $message }}</span>
                                @enderror
                            </div>
                        @elseif ($has_sku)
                            <div class="mb-3">
                                <label class="form-label">Seleccionar Variante (Talla/Color)</label>
                                <select class="form-select" wire:model.live="selected_sku_id">
                                    <option value="">Seleccione una variante</option>
                                    @foreach ($availableSkus as $sku)
                                        <option value="{{ $sku->id }}">
                                            {{ $sku->color->name ?? 'S/C' }} - {{ $sku->size->name ?? 'S/T' }} - Stock actual:
                                            {{ $sku->stock }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('selected_sku_id')
                                    <span class="text-danger er">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        @if ($selected_lot_id || $selected_sku_id || (!$selectedInventory['has_lote'] && !$has_sku))
                            <div class="mb-3">
                                <label class="form-label">
                                    @if($selected_lot_id) Nueva Cantidad para el Lote
                                    @elseif($selected_sku_id) Nueva Cantidad para la Variante
                                    @else Nueva Cantidad
                                    @endif
                                </label>
                                <input type="number" class="form-control" wire:model="new_quantity"
                                    placeholder="Ingrese la nueva cantidad" min="0">
                                @error('new_quantity')
                                    <span class="text-danger er">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Motivo del Ajuste <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" wire:model="adjustment_reason" rows="2"
                                placeholder="Ej: Ajuste por inventario físico, merma, daño..."></textarea>
                            @error('adjustment_reason')
                                <span class="text-danger er">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" wire:click="saveStockAdjustment"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveStockAdjustment">Guardar Ajuste</span>
                        <span wire:loading wire:target="saveStockAdjustment">
                            <i class="bx bx-spin bx-loader"></i> Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="lotesModal" tabindex="-1" aria-labelledby="lotesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lotesModalLabel">GESTIONAR LOTES</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2 p-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>LOTE</th>
                                        <th>STOCK</th>
                                        <th>FECHA VENCIMIENTO</th>
                                        <th>ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (empty($listLots))
                                        <tr>
                                            <td colspan="4" class="text-center">No hay lotes disponibles</td>
                                        </tr>
                                    @else
                                        @foreach ($listLots as $lot)
                                            <tr>
                                                @if ($editingLot && $lot_id == $lot['id'])
                                                    <td>
                                                        <input type="text" class="form-control" wire:model="lot_number"
                                                            placeholder="Número de lote" maxlength="50">
                                                        @error('lot_number')
                                                            <span class="text-danger er">{{ $message }}</span>
                                                        @enderror
                                                    </td>
                                                    <td>{{ $lot['quantity'] }}</td>
                                                    <td>
                                                        <input type="date" class="form-control" wire:model="lot_expiration_date"
                                                            placeholder="Opcional">
                                                        @error('lot_expiration_date')
                                                            <span class="text-danger er">{{ $message }}</span>
                                                        @enderror
                                                    </td>
                                                    <td>
                                                        <div class="d-flex order-actions">
                                                            <a href="javascript:;" wire:click="updateLot" class="btn-action-success"
                                                                title="Guardar">
                                                                <i class="bx bx-check"></i>
                                                            </a>
                                                            <a href="javascript:;" wire:click="cancelEditLot"
                                                                class="btn-action-danger ms-1" title="Cancelar">
                                                                <i class="bx bx-x"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                @else
                                                    <td>{{ $lot['lot_number'] ?: 'S/N' }}</td>
                                                    <td>{{ $lot['quantity'] ?: '0' }}</td>
                                                    <td>{{ $lot['expiration_date'] ?: 'S/N' }}</td>
                                                    <td>
                                                        <div class="d-flex order-actions">
                                                            <a href="javascript:;" wire:click="editLot({{ $lot['id'] }})"
                                                                class="btn-action-primary" title="Editar Lote">
                                                                <i class="bx bxs-edit-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
    document.addEventListener('livewire:init', function () {
        $('.selectBranch').select2();
        $('.selectBranch').on('change', function () {
            @this.set('branch_id', this.value)
        });

        Livewire.on('reset-selectSupplier', () => {
            $('.selectBranch').val('').trigger('change');
        });

        Livewire.on('alert', (data) => {
            const [msg, type] = data;
            toast(msg, type);
        });

        Livewire.on('closeAdjustModal', () => {
            $('#adjustStockModal').modal('hide');
        });
    });
</script>