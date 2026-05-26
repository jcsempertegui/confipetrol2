@push('title', 'Kardex')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Inventario</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Kardex</li>
            </ol>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                <div class="col-md-5 col-12">
                    <label class="form-label fw-semibold">Buscar Producto</label>
                    <div class="sp-search-container" x-data="{ openSearch: true }" @click.outside="openSearch = false">
                        <div class="sp-search-wrapper">
                            <input type="text"
                                class="sp-search-input search-input"
                                placeholder="Buscar producto (nombre o código)..."
                                wire:model.live.debounce.300ms="search"
                                @focus="openSearch = true"
                                @input="openSearch = true"
                                autocomplete="off"
                                maxlength="55"
                                wire:keydown.enter.prevent="codeSearch">

                            <div class="sp-search-actions">
                                @if($search)
                                    <button class="sp-action-btn sp-clear" type="button" wire:click="$set('search', '')" @click="openSearch = true" title="Limpiar">
                                        <i class="bx bx-x"></i>
                                    </button>
                                @endif
                                <div class="sp-divider"></div>
                                <div class="sp-search-icon">
                                    <i class="bx bx-search"></i>
                                </div>
                            </div>
                        </div>

                        @if (!empty($products) && $search)
                            <ul class="listsearch" x-show="openSearch" style="display:block; top: 110%; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #eee;">
                                @forelse ($products as $item)
                                    @php
                                        $currentStock = $item->lote == 1 ? $item->stock_lot : $item->stock_nolot;
                                    @endphp
                                    <li class="listsearch-item"
                                        wire:click="selectProduct({{ $item->id }}); $wire.set('search', ''); openSearch = false"
                                        title="{{ $item->name }}">
                                        <div class="listsearch-content">
                                            <div class="listsearch-main">
                                                <span class="listsearch-code">{{ $item->code }}</span>
                                                <span class="listsearch-name">{{ Str::limit($item->name, 40) }}</span>
                                            </div>
                                            <div class="listsearch-info">
                                                <span class="listsearch-price">Bs. {{ number_format($item->sale_price, 2) }}</span>
                                                <span class="listsearch-stock {{ $currentStock == 0 ? 'stock-zero' : '' }}">
                                                    <i class='bx bx-error-circle' style="{{ $currentStock == 0 ? '' : 'display:none;' }}"></i>
                                                    Stock: {{ $currentStock }}
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="listsearch-empty">
                                        <i class='bx bx-search-alt'></i>
                                        No hay productos disponibles
                                    </li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="desde">Fecha Inicial</label>
                        <div class="position-relative input-icon">
                            <input id="desde" class="form-control flatpickr" type="text" wire:model.lazy="fromDate"
                                placeholder="Seleccionar Fecha Inicial">
                            <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="hasta">Fecha Final</label>
                        <div class="position-relative input-icon">
                            <input id="hasta" class="form-control flatpickr" type="text" wire:model.lazy="toDate"
                                placeholder="Seleccionar Fecha Final">
                            <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @if ($selectedProduct)
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="card-title mb-2 fw-bold text-dark">{{ $selectedProduct->name }}</h5>
                    
                    @if (!empty($selectedProductSkus) && count($selectedProductSkus) > 0)
                        <div class="d-flex flex-wrap gap-2 mb-3 mt-2">
                            @foreach($selectedProductSkus as $sku)
                                <div class="badge border border-secondary text-dark px-2 py-1" style="background-color: #f8f9fa;">
                                    <i class="bx bx-purchase-tag-alt text-secondary me-1"></i> 
                                    {{ $sku->color->name ?? 'S/C' }} - {{ $sku->size->name ?? 'S/T' }}
                                    <span class="badge bg-secondary text-white ms-1 rounded-pill">Stock: {{ $sku->stock }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">Código:</small>
                            <div class="fw-medium">{{ $selectedProduct->code }}</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Categoría:</small>
                            <div class="fw-medium">{{ $selectedProduct->categories->name ?? 'Sin categoría' }}</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Marca:</small>
                            <div class="fw-medium">{{ $selectedProduct->brands->name ?? 'Sin marca' }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="h5 mb-0 text-success">{{ $totalIn }}</div>
                                <small class="text-muted">Entradas</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="h5 mb-0 text-danger">{{ $totalOut }}</div>
                                <small class="text-muted">Salidas</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <div class="h5 mb-0 text-primary">{{ $currentStock }}</div>
                                <small class="text-muted">Stock Actual</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-list-ul"></i>
                <span class="fw-semibold">Movimientos del Kardex</span>
            </div>
        </div>

        <div class="card-body px-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
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

            <div class="table-responsive">
                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>N°</th>
                            <th>FECHA</th>
                            <th>TIPO</th>
                            <th>DESCRIPCIÓN</th>
                            <th class="text-center">ENTRADA</th>
                            <th class="text-center">SALIDA</th>
                            <th class="text-center">SALDO</th>
                            <th>COSTO UNITARIO</th>
                            <th>TOTAL VALORIZADO</th>
                            <th>LOTE</th>
                            <th>USUARIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($kardexs) && $kardexs->isEmpty())
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">No se encontraron movimientos.</td>
                            </tr>
                        @elseif (isset($kardexs))
                            @foreach ($kardexs as $index => $kardex)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $kardex->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if ($kardex->type == 'ENTRADA')
                                            <span class="badge rounded-pill text-success bg-light-success text-uppercase">ENTRADA</span>
                                        @else
                                            <span class="badge rounded-pill text-danger bg-light-danger text-uppercase">SALIDA</span>
                                        @endif
                                    </td>
                                    <td>{{ $kardex->description ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        @if ($kardex->quantity_in > 0)
                                            <span class="text-success fw-bold">+{{ $kardex->quantity_in }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($kardex->quantity_out > 0)
                                            <span class="text-danger fw-bold">-{{ $kardex->quantity_out }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="fw-bold text-dark">{{ $kardex->balance }}</span></td>
                                    <td>Bs. {{ number_format($kardex->price, 2) }}</td>
                                    <td>Bs. {{ number_format($kardex->total, 2) }}</td>
                                    <td>
                                        @if ($kardex->lot)
                                            <span class="badge bg-secondary">{{ $kardex->lot->lot_number ?? 'N/A' }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $kardex->user->login ?? $kardex->user->name ?? 'S/N' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            @if (isset($kardexs))
                <div class="mt-2">
                    {{ $kardexs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('alert', (data) => {
            const [msg, type] = data;
            if(typeof toast === 'function') {
                toast(msg, type);
            } else if(typeof Swal !== 'undefined') {
                Swal.fire({ title: msg, icon: type, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });

        Livewire.on('focusSearchInput', () => {
            let searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.focus();
            }
        });
    });
</script>