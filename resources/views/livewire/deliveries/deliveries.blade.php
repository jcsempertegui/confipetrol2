@push('title', 'Entregas')

<div class="page-content d-flex flex-column" style="height: calc(100vh - 90px);">

    <div class="row g-3 flex-grow-1" style="min-height: 0;">

        <div class="col-12 col-lg-8 d-flex flex-column h-100">
            <div class="card mb-0 w-100 flex-grow-1 d-flex flex-column overflow-hidden template-flex-card">
                <div class="card-header px-3 py-2 flex-shrink-0">
                    <i class="bx bx-hard-hat me-2"></i>
                    BUSCAR PRODUCTOS
                </div>
                <div class="card-body d-flex flex-column px-2 py-2 flex-grow-1 overflow-hidden template-flex-body">

                    <div class="flex-shrink-0 pt-3 px-3">
                        <div class="row mb-2">
                            <div class="col-lg-8 col-sm-12 mb-1">
                                <div class="sp-search-container" x-data="{ openSearch: true }" @click.outside="openSearch = false">
                                    <div class="sp-search-wrapper">
                                        <input type="text"
                                            class="sp-search-input search-input delivery-search-input"
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
                                                    $currentStock = $item->lote == 1 ? $item->stock_lot : $item->stock_nolot;
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
                                                                <span class="tag tag-brand" style="background:#fde68a; color:#92400e;">
                                                                    <i class='bx bx-hard-hat'></i> EPP
                                                                </span>
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
                                                    No hay EPPs disponibles
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
                                    <th style="width:45%;">PRODUCTO / VARIANTE</th>
                                    <th style="width:25%; text-align:center;">CANTIDAD</th>
                                    <th style="width:5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cart as $cartKey => $item)
                                    <tr wire:key="delivery-cart-row-{{ $cartKey }}"
                                        x-data="{ qty: {{ $item['quantity'] }} }"
                                        @update-delivery-qty-input.window="if ($event.detail[0].productId == '{{ $cartKey }}') { qty = $event.detail[0].qty }">

                                        <td>{{ $item['code'] }}</td>

                                        <td>
                                            <div class="fw-semibold">{{ $item['name'] }}</div>
                                            @if(!empty($item['sku_name']))
                                                <small class="text-primary fw-bold d-block">
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
                                                    <i class="bx bx-minus fw-bold"></i>
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
                                                    <i class="bx bx-plus fw-bold"></i>
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
                                        <td colspan="4" class="text-muted py-4">Busca un EPP arriba para agregarlo a la entrega</td>
                                    </tr>
                                @endforelse
                                <tr class="template-tr-spacer">
                                    <td colspan="4" class="template-td-spacer"></td>
                                </tr>
                            </tbody>
                            <tfoot class="template-sticky-tfoot">
                                <tr>
                                    <td colspan="2" class="text-start fw-bold">ARTÍCULOS: {{ $total_items }} (ítems)</td>
                                    <td colspan="2" class="fw-bold text-end">TOTAL UNIDADES: {{ collect($cart)->sum('quantity') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4 d-flex flex-column h-100">
            <div class="card mb-0 w-100 flex-grow-1 d-flex flex-column overflow-hidden">
                <div class="card-header px-3 py-2 flex-shrink-0">
                    <i class="bx bx-receipt me-2"></i>
                    RESUMEN DE ENTREGA EPP
                </div>
                <div class="card-body d-flex flex-column px-3 py-3 flex-grow-1 overflow-hidden">

                    <div class="row g-2">
                        <div class="col-12 position-relative"
                            x-data="{ openSearch: false }"
                            @click.outside="openSearch = false; $wire.clearWorkerSearch()">
                            <div class="ct-search-container mb-0">
                                <div class="ct-search-wrapper">
                                    <input type="text" class="ct-search-input search-input"
                                        wire:model.live.debounce.300ms="workerSearchTerm"
                                        @focus="openSearch = true; $wire.set('showWorkerDropdown', true); $el.select()"
                                        placeholder="Buscar trabajador por documento o nombre..."
                                        autocomplete="off">

                                    <div class="ct-search-actions">
                                        @if($workers_id)
                                            <button class="ct-action-btn ct-clear" type="button"
                                                wire:click="clearWorkerSelection" title="Quitar">
                                                <i class="bx bx-x"></i>
                                            </button>
                                            <div class="ct-divider"></div>
                                        @endif
                                        <div class="position-relative">
                                            <a class="ct-search-icon-btn" title="Trabajador">
                                                <i class="bx bx-user-circle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($showWorkerDropdown && count($workerResults) > 0)
                                <ul class="ct-customer-dropdown" x-show="openSearch">
                                    @foreach($workerResults as $result)
                                        <li class="ct-list-item"
                                            wire:click="selectWorker({{ $result->id }})"
                                            @click="openSearch = false">
                                            <div>
                                                <span class="ct-item-name">{{ $result->name }} {{ $result->last_name }}</span>
                                                <span class="ct-item-doc">Doc: {{ $result->document }}</span>
                                                @if($result->cargo)
                                                    <span class="ct-item-doc">{{ $result->cargo }}</span>
                                                @endif
                                            </div>
                                            <i class="bx bx-check text-success {{ $workers_id == $result->id ? '' : 'd-none' }}"></i>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif($showWorkerDropdown && strlen($workerSearchTerm) >= 1)
                                <ul class="ct-customer-dropdown" x-show="openSearch">
                                    <li class="ct-list-item-empty" @click="openSearch = false">
                                        No se encontraron resultados
                                    </li>
                                </ul>
                            @endif
                        </div>

                        <div class="col-lg-12 col-sm-6 mb-2 mt-2">
                            <label class="form-label mb-1" style="font-size: 0.85rem;">Fecha de Entrega</label>
                            <div class="position-relative input-icon">
                                <input type="date" class="form-control" wire:model.lazy="delivery_date"
                                    max="{{ date('Y-m-d') }}">
                                <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                            </div>
                            @error('delivery_date')
                                <span class="text-danger er">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-lg-12 col-sm-6 mb-2">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.82rem;">
                                <i class="bx bx-comment-detail me-1"></i>Observaciones
                            </label>
                            <textarea class="form-control form-control-sm"
                                wire:model.defer="observations"
                                placeholder="Observación / Comentario..."
                                rows="2" maxlength="255"
                                style="resize: none; font-size: 0.85rem;"></textarea>
                        </div>

                        <div class="col-lg-12 col-sm-6">
                            <div class="card shadow-none border mb-2 bg-light">
                                <div class="card-body p-2">
                                    <div class="row text-center align-items-center g-1">
                                        <div class="col-6">
                                            <small class="text-muted fw-bold d-block" style="font-size: 0.70rem;">ÍTEMS</small>
                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $total_items }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted fw-bold d-block" style="font-size: 0.70rem;">UNIDADES TOTALES</small>
                                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ collect($cart)->sum('quantity') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto pt-3 flex-shrink-0 border-top">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <button class="btn btn-success btnIcon flex-grow-1 py-2 fw-bold"
                                wire:loading.attr="disabled"
                                wire:click="confirmDelivery"
                                wire:target="confirmDelivery">
                                <span wire:loading.remove wire:target="confirmDelivery">
                                    <i class="bx bx-check-circle align-middle me-1"></i> GUARDAR ENTREGA
                                </span>
                                <span wire:loading wire:target="confirmDelivery">
                                    <i class="bx bx-spin bx-loader align-middle me-1"></i> PROCESANDO...
                                </span>
                            </button>
                            <button class="btn btn-danger btnIcon flex-grow-1 py-2 fw-bold"
                                wire:click.prevent="clearDeliveries"
                                wire:loading.attr="disabled"
                                wire:target="clearDeliveries"
                                @if(count($cart) == 0) disabled @endif>
                                <span wire:loading.remove wire:target="clearDeliveries">
                                    <i class="bx bx-x-circle align-middle me-1"></i> CANCELAR
                                </span>
                                <span wire:loading wire:target="clearDeliveries">
                                    <i class="bx bx-spin bx-loader align-middle me-1"></i> PROCESANDO...
                                </span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div wire:ignore.self class="modal fade" id="skuDeliveryModal" tabindex="-1"
    aria-labelledby="skuDeliveryModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="max-height: 90vh;">
            <div class="modal-header">
                <h1 class="modal-title" id="skuDeliveryModalLabel">
                    <i class="bx bx-customize"></i> SELECCIONA UNA VARIANTE
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">

                {{-- Cabecera del producto --}}
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

                {{-- Variantes --}}
                @if(!empty($productSkus))
                    <div class="recipe-section">
                        <h6 class="recipe-section-title">
                            <i class="bx bx-list-ul me-2"></i>Selecciona una Talla y Color
                        </h6>
                        <div class="row row-cols-2 row-cols-md-3 g-3">
                            @foreach($productSkus as $sku)
                                <div class="col" wire:key="sku-delivery-item-{{ $sku['id'] }}">
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
                                                    <span class="text-success fw-bold">{{ $sku['stock'] }}</span>
                                                @else
                                                    <span class="text-danger fw-bold">Sin stock</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Sin variantes --}}
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
                    class="btn btn-danger"
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

        Livewire.on('show-sku-delivery-modal', () => {
            $('#skuDeliveryModal').modal('show');
        });

        Livewire.on('close-sku-delivery-modal', () => {
            $('#skuDeliveryModal').modal('hide');
        });

        Livewire.on('processPrintBehavior', (data) => {
            let info = Array.isArray(data) ? data[0] : data;
            let behavior = info.behavior;
            let message  = info.message;

            if (behavior === 'none') {
                Swal.fire({
                    title: '¡Excelente!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        Livewire.on('focusDeliverySearchInput', () => {
            let isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
            if (window.innerWidth > 1024 && !isTouchDevice) {
                let searchInput = document.querySelector('.delivery-search-input');
                if (searchInput) searchInput.focus();
            }
        });

    });
</script>
