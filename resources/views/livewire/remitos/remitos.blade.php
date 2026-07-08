@push('title', 'Remitos')

<div class="page-content d-flex flex-column" style="height: calc(100vh - 90px);">

    <div class="row g-3 flex-grow-1" style="min-height: 0;">

        <div class="col-12 col-lg-7 d-flex flex-column h-100">
            <div class="card mb-0 w-100 flex-grow-1 d-flex flex-column overflow-hidden template-flex-card">
                <div class="card-header px-3 py-2 flex-shrink-0 d-flex align-items-center justify-content-between">
                    <span>
                        <i class="bx bx-shopping-bag me-2"></i>BUSCAR PRODUCTOS
                    </span>
                    @if($tipo === 'INGRESO')
                        <span class="badge rounded-pill text-success bg-light-success text-uppercase">
                            <i class="bx bx-log-in me-1"></i> INGRESO
                        </span>
                    @else
                        <span class="badge rounded-pill text-danger bg-light-danger text-uppercase">
                            <i class="bx bx-log-out me-1"></i> EGRESO
                        </span>
                    @endif
                </div>
                <div class="card-body d-flex flex-column px-2 py-2 flex-grow-1 overflow-hidden template-flex-body">

                    <div class="flex-shrink-0 pt-3 px-3">
                        <div class="row mb-2">
                            <div class="col-lg-8 col-sm-12 mb-1">
                                <div class="sp-search-container" x-data="{ openSearch: true }" @click.outside="openSearch = false">
                                    <div class="sp-search-wrapper">
                                        <input type="text"
                                            class="sp-search-input search-input remito-search-input"
                                            placeholder="Buscar productos (nombre o código)..."
                                            wire:model.live.debounce.300ms="search"
                                            @focus="openSearch = true"
                                            @input="openSearch = true"
                                            autocomplete="off"
                                            maxlength="55"
                                            wire:keydown.enter.prevent="AddOrUpdate({{ !empty($products) && count($products) === 1 ? $products->first()->id : 'null' }})">

                                        <div class="sp-search-actions">
                                            @if($search)
                                                <button class="sp-action-btn sp-clear" type="button"
                                                    wire:click="$set('search', '')"
                                                    @click="openSearch = true" title="Limpiar">
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
                                        <ul class="listsearch" x-show="openSearch"
                                            style="display:block; top: 110%; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #eee;">
                                            @forelse ($products as $item)
                                                @php
                                                    $currentStock = $item->stock_nolot;
                                                @endphp
                                                <li class="listsearch-item"
                                                    wire:click="AddOrUpdate({{ $item->id }}); $wire.set('search', '')"
                                                    @click="openSearch = false"
                                                    title="{{ $item->name }}">
                                                    <div class="listsearch-content">
                                                        <div class="listsearch-main">
                                                            <span class="listsearch-code">{{ $item->code }}</span>
                                                            <span class="listsearch-name">{{ Str::limit($item->name, 45) }}</span>
                                                            <div class="listsearch-tags">
                                                                @php
                                                                    $typeLabels = [1 => 'Activo', 2 => 'Consumible', 3 => 'EPPS'];
                                                                    $typeClasses = [1 => 'tag-type-activo', 2 => 'tag-type-consumible', 3 => 'tag-type-epps'];
                                                                @endphp
                                                                <span class="tag {{ $typeClasses[$item->type] ?? 'tag-brand' }}">
                                                                    <i class='bx bx-cube'></i>
                                                                    {{ $typeLabels[$item->type] ?? 'Otro' }}
                                                                </span>
                                                                @if ($item->brands)
                                                                    <span class="tag tag-brand">
                                                                        <i class='bx bxs-purchase-tag'></i>
                                                                        {{ $item->brands->name }}
                                                                    </span>
                                                                @endif
                                                                @if ($item->categories)
                                                                    <span class="tag tag-category">
                                                                        <i class='bx bx-category'></i>
                                                                        {{ $item->categories->name }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="listsearch-info">
                                                            <span class="listsearch-stock {{ ($currentStock ?? 0) == 0 ? 'stock-zero' : '' }}">
                                                                <i class='bx bx-error-circle' style="{{ ($currentStock ?? 0) == 0 ? '' : 'display:none;' }}"></i>
                                                                Stock: {{ $currentStock ?? 'c/var' }}
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
                        </div>
                    </div>

                    <div class="table-responsive template-table-wrapper">
                        <table class="table table-hover align-middle table-striped template-table-full">
                            <thead class="sticky-top">
                                <tr>
                                    <th style="width:10%;">CÓDIGO</th>
                                    <th style="width:50%;">PRODUCTO / VARIANTE</th>
                                    <th style="width:25%; text-align:center;">CANTIDAD</th>
                                    <th style="width:5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cart as $cartKey => $item)
                                    <tr wire:key="remito-cart-row-{{ $cartKey }}"
                                        x-data="{ qty: {{ $item['quantity'] }} }"
                                        @update-remito-qty-input.window="if ($event.detail[0].productId == '{{ $cartKey }}') { qty = $event.detail[0].qty }">

                                        <td>{{ $item['code'] }}</td>

                                        <td>
                                            <div class="fw-semibold">{{ $item['name'] }}</div>
                                            @if(!empty($item['sku_name']))
                                                <small class="text-primary d-block">
                                                    <i class="bx bx-customize me-1"></i>{{ $item['sku_name'] }}
                                                </small>
                                            @endif
                                        </td>

                                        <td class="align-middle text-center">
                                            <div class="qty-capsule d-inline-flex">
                                                <button type="button" class="qty-btn minus"
                                                    @click="if(qty > 1) qty--"
                                                    wire:click.prevent="updateQty('{{ $cartKey }}', {{ $item['quantity'] - 1 }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="updateQty('{{ $cartKey }}', {{ $item['quantity'] - 1 }})"
                                                    @if($item['quantity'] <= 1) disabled @endif>
                                                    <i class="bx bx-minus"></i>
                                                </button>

                                                <input type="text" class="form-control text-center"
                                                    x-model="qty"
                                                    wire:change.prevent="updateQty('{{ $cartKey }}', $event.target.value)"
                                                    maxlength="5" inputmode="decimal"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                    autocomplete="off" style="width:50px;">

                                                <button type="button" class="qty-btn plus"
                                                    @click="qty++"
                                                    wire:click.prevent="updateQty('{{ $cartKey }}', {{ $item['quantity'] + 1 }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="updateQty('{{ $cartKey }}', {{ $item['quantity'] + 1 }})">
                                                    <i class="bx bx-plus"></i>
                                                </button>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="d-flex order-actions justify-content-center">
                                                <a href="javascript:;"
                                                    wire:click.prevent="removeItem('{{ $cartKey }}')"
                                                    class="btn-action-danger ms-1">
                                                    <i class="bx bxs-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="text-center">
                                        <td colspan="4" class="text-muted py-4">Busca un producto arriba para agregarlo al remito</td>
                                    </tr>
                                @endforelse
                                <tr class="template-tr-spacer">
                                    <td colspan="4" class="template-td-spacer"></td>
                                </tr>
                            </tbody>
                            <tfoot class="template-sticky-tfoot">
                                <tr>
                                    <td colspan="2" class="text-start">ARTÍCULOS: {{ $total_items }} (ítems)</td>
                                    <td colspan="2" class="text-end">TOTAL CANTIDAD: {{ collect($cart)->sum('quantity') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5 d-flex flex-column h-100">
            <div class="card mb-0 w-100 flex-grow-1 d-flex flex-column overflow-hidden">
                <div class="card-header px-3 py-2 flex-shrink-0
                    {{ $tipo === 'INGRESO' ? 'bg-success text-white' : 'bg-danger text-white' }}">
                    <i class="bx {{ $tipo === 'INGRESO' ? 'bx-log-in' : 'bx-log-out' }} me-2"></i>
                    DATOS DEL REMITO
                </div>
                <div class="card-body d-flex flex-column px-3 py-2 flex-grow-1"
                    style="overflow-y: auto;">

                    <div class="row g-2">

                        {{-- Tipo de Remito --}}
                        <div class="col-12 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-transfer me-1"></i>Tipo de Remito
                            </label>
                            <select class="form-select 
                                {{ $tipo === 'INGRESO' ? 'border-success text-success' : 'border-danger text-danger' }}"
                                wire:model.live="tipo"
                                style="font-weight: 700; font-size: 0.9rem;">
                                <option value="EGRESO">⬆ EGRESO</option>
                                <option value="INGRESO">⬇ INGRESO</option>
                            </select>
                        </div>

                        {{-- Fecha --}}
                        <div class="col-6 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-calendar me-1"></i>Fecha
                            </label>
                            <input type="date" class="form-control"
                                wire:model.lazy="remito_date"
                                max="{{ date('Y-m-d') }}">
                            @error('remito_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- N° Orden --}}
                        <div class="col-6 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-hash me-1"></i>N° Orden <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('n_orden') is-invalid @enderror"
                                wire:model.defer="n_orden"
                                placeholder="Nro. de Orden..."
                                maxlength="100" autocomplete="off">
                            @error('n_orden')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Contrato --}}
                        <div class="col-12 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-file me-1"></i>Contrato <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('contrato') is-invalid @enderror"
                                wire:model.defer="contrato"
                                placeholder="Nombre / Nro. de Contrato..."
                                maxlength="150" autocomplete="off">
                            @error('contrato')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Señores --}}
                        <div class="col-6 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-buildings me-1"></i>Señores <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('senores') is-invalid @enderror"
                                wire:model.defer="senores"
                                placeholder="Destinatario..."
                                maxlength="150" autocomplete="off">
                            @error('senores')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Atención --}}
                        <div class="col-6 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-user-pin me-1"></i>Atención <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('atencion') is-invalid @enderror"
                                wire:model.defer="atencion"
                                placeholder="A/C de..."
                                maxlength="150" autocomplete="off">
                            @error('atencion')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Campo --}}
                        <div class="col-6 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-map-pin me-1"></i>Campo <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('campo') is-invalid @enderror"
                                wire:model.defer="campo"
                                placeholder="Campo / Ubicación..."
                                maxlength="150" autocomplete="off">
                            @error('campo')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Placa --}}
                        <div class="col-6 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-car me-1"></i>Placa <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('placa') is-invalid @enderror"
                                wire:model.defer="placa"
                                placeholder="Placa del vehículo..."
                                maxlength="30" autocomplete="off">
                            @error('placa')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Despachado por --}}
                        <div class="col-6 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-send me-1"></i>Despachado por <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('despachado_por') is-invalid @enderror"
                                wire:model.defer="despachado_por"
                                placeholder="Nombre del despachador..."
                                maxlength="150" autocomplete="off">
                            @error('despachado_por')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Transportado por --}}
                        <div class="col-6 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-truck me-1"></i>Transportado por <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('transportado_por') is-invalid @enderror"
                                wire:model.defer="transportado_por"
                                placeholder="Nombre del transportista..."
                                maxlength="150" autocomplete="off">
                            @error('transportado_por')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Observaciones --}}
                        <div class="col-12 mb-1">
                            <label class="form-label mb-1">
                                <i class="bx bx-comment-detail me-1"></i>Observaciones
                            </label>
                            <textarea class="form-control"
                                wire:model.defer="observations"
                                placeholder="Observación / Comentario..."
                                rows="2" maxlength="500"></textarea>
                        </div>

                        {{-- Resumen --}}
                        <div class="col-12">
                            <div class="card shadow-none border mb-1 bg-light">
                                <div class="card-body p-2">
                                    <div class="row text-center align-items-center g-1">
                                        <div class="col-6">
                                            <small class="text-muted d-block" >ÍTEMS</small>
                                            <span class="text-dark">{{ $total_items }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block" >UNIDADES TOTALES</small>
                                            <span class="text-dark">{{ collect($cart)->sum('quantity') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-auto pt-2 flex-shrink-0 border-top">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <button class="btn {{ $tipo === 'INGRESO' ? 'btn-success' : 'btn-danger' }} btnIcon flex-grow-1 py-2"
                                wire:loading.attr="disabled"
                                wire:click="confirmRemito"
                                wire:target="confirmRemito">
                                <span wire:loading.remove wire:target="confirmRemito">
                                    <i class="bx bx-check-circle align-middle me-1"></i>
                                    GUARDAR {{ $tipo }}
                                </span>
                                <span wire:loading wire:target="confirmRemito">
                                    <i class="bx bx-spin bx-loader align-middle me-1"></i> PROCESANDO...
                                </span>
                            </button>
                            <button class="btn btn-outline-secondary btnIcon flex-grow-1 py-2"
                                wire:click.prevent="clearRemito"
                                wire:loading.attr="disabled"
                                wire:target="clearRemito"
                                @if(count($cart) == 0) disabled @endif>
                                <span wire:loading.remove wire:target="clearRemito">
                                    <i class="bx bx-x-circle align-middle me-1"></i> CANCELAR
                                </span>
                                <span wire:loading wire:target="clearRemito">
                                    <i class="bx bx-spin bx-loader align-middle me-1"></i> PROCESANDO...
                                </span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div wire:ignore.self class="modal fade" id="skuRemitoModal" tabindex="-1"
        aria-labelledby="skuRemitoModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" style="max-height: 90vh;">
                <div class="modal-header">
                    <h1 class="modal-title" id="skuRemitoModalLabel">
                        <i class="bx bx-customize"></i> SELECCIONA UNA VARIANTE
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="overflow-y: auto;">

                    @if($selectedProductData)
                        <div class="recipe-product-header">
                            <div class="recipe-product-icon">
                                <i class="bx bx-package"></i>
                            </div>
                            <div class="recipe-product-info">
                                <h2 class="recipe-product-name">{{ $selectedProductData['name'] }}</h2>
                                <p class="recipe-product-price">Código: {{ $selectedProductData['code'] }}</p>
                            </div>
                        </div>
                    @endif

                    @if(!empty($productSkus))
                        <div class="recipe-section">
                            <h6 class="recipe-section-title">
                                <i class="bx bx-list-ul me-2"></i>Selecciona una Talla y Color
                            </h6>
                            <div class="row row-cols-2 row-cols-md-3 g-3">
                                @foreach($productSkus as $sku)
                                    <div class="col" wire:key="sku-remito-item-{{ $sku['id'] }}">
                                        <div class="recipe-variant-card {{ $selectedSkuId == $sku['id'] ? 'recipe-variant-selected' : '' }}"
                                            wire:click="selectSku({{ $sku['id'] }})">
                                            <div class="recipe-variant-radio">
                                                <input class="form-check-input" type="radio"
                                                    value="{{ $sku['id'] }}"
                                                    {{ $selectedSkuId == $sku['id'] ? 'checked' : '' }}
                                                    readonly>
                                            </div>
                                            <div class="recipe-variant-content">
                                                <h6 class="recipe-variant-name">
                                                    {{ $sku['size_name'] ?? '' }}
                                                    {{ ($sku['size_name'] && $sku['color_name']) ? ' - ' : '' }}
                                                    {{ $sku['color_name'] ?? '' }}
                                                    @if(!$sku['size_name'] && !$sku['color_name'])
                                                        {{ $sku['sku'] }}
                                                    @endif
                                                </h6>
                                                <p class="recipe-variant-price">
                                                    Stock:
                                                    @if(($sku['stock'] ?? 0) > 0)
                                                        <span class="text-success">{{ $sku['stock'] }}</span>
                                                    @else
                                                        <span class="{{ $tipo === 'INGRESO' ? 'text-warning' : 'text-danger' }}">
                                                            {{ $tipo === 'INGRESO' ? '0 (se agregará)' : 'Sin stock' }}
                                                        </span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(empty($productSkus))
                        <div class="text-center py-4 text-muted">
                            <i class="bx bx-package fs-2"></i>
                            <p>No hay variantes disponibles</p>
                        </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button"
                        wire:click.prevent="addSkuToCart()"
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="addSkuToCart"
                        @if(!$selectedSkuId) disabled @endif>
                        <span wire:loading.remove wire:target="addSkuToCart">Aceptar</span>
                        <span wire:loading wire:target="addSkuToCart">
                            <i class="bx bx-spin bx-loader me-1"></i> Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('livewire:init', function () {

        Livewire.on('alert', (data) => {
            const [msg, type] = data;
            toast(msg, type);
        });

        Livewire.on('show-sku-remito-modal', () => {
            $('#skuRemitoModal').modal('show');
        });

        Livewire.on('close-sku-remito-modal', () => {
            $('#skuRemitoModal').modal('hide');
        });

        Livewire.on('processPrintBehaviorRemito', (data) => {
            let info = Array.isArray(data) ? data[0] : data;
            let message = info.message;
            Swal.fire({
                title: '¡Remito Registrado!',
                text: message,
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#3085d6'
            });
        });

        Livewire.on('focusRemitoSearchInput', () => {
            let isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
            if (window.innerWidth > 1024 && !isTouchDevice) {
                let searchInput = document.querySelector('.remito-search-input');
                if (searchInput) searchInput.focus();
            }
        });

    });
</script>
