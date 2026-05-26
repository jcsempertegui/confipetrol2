@push('title', 'Ventas')

<div class="page-content d-flex flex-column" style="height: calc(100vh - 90px);">
    @if (!$boxExists)
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-3 shadow-sm border-0 flex-shrink-0"
            role="alert">
            <i class='bx bx-error-circle fs-4'></i>
            <div class="flex-grow-1">
                <strong>Caja no aperturada:</strong> Debes iniciar la caja para continuar.
                <button class="btn btn-sm btn-light text-danger ms-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#theModal">
                    <i class="bx bx-log-in-circle me-1"></i> Iniciar Caja
                </button>
            </div>
        </div>
    @endif

    <div class="row g-3 flex-grow-1" style="min-height: 0;">
        <div class="col-12 col-lg-8 d-flex flex-column h-100">
            <div class="card mb-0 w-100 flex-grow-1 d-flex flex-column overflow-hidden template-flex-card">
                <div class="card-header px-3 py-2 flex-shrink-0">
                    <i class="bx bx-shopping-bag me-2"></i>
                    BUSCAR PRODUCTOS
                </div>
                <div class="card-body d-flex flex-column px-2 py-2 flex-grow-1 overflow-hidden template-flex-body">
                    <div class="flex-shrink-0 pt-3 px-3">
                        <div class="row mb-2">
                            <div class="col-lg-7 col-sm-12 mb-1">
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
                                            wire:keydown.enter.prevent="AddOrUpdate({{ !empty($products) && count($products) === 1 ? $products->first()->id : 'null' }})">

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
                                                    if ($item->type == 1 || $item->type == 5) {
                                                        $currentStock = '';
                                                    } else {
                                                        $currentStock = $item->lote == 1 ? $item->stock_lot : $item->stock_nolot;
                                                    }
                                                @endphp
                                                <li class="listsearch-item"
                                                    wire:click="AddOrUpdate({{ $item->id }}); $wire.set('search', '')"
                                                    @click="openSearch = false"
                                                    title="{{ $item->name }}">
                                                    <div class="listsearch-content">
                                                        <div class="listsearch-main">
                                                            <span class="listsearch-code">{{ $item->code }}</span>
                                                            <span class="listsearch-name">{{ Str::limit($item->name, 40) }}</span>

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

                                                                @if ($item->units)
                                                                    <span class="tag tag-unit">
                                                                        <i class='bx bx-package'></i>
                                                                        {{ $item->units->name }}
                                                                    </span>
                                                                @endif
                                                                
                                                                @if ($item->type == 1)
                                                                    <span class="tag tag-brand" style="background:#e0f2fe; color:#0369a1;">SERVICIO</span>
                                                                @elseif ($item->type == 5)
                                                                    <span class="tag tag-brand" style="background:#fef08a; color:#b45309;">COMBO</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="listsearch-info">
                                                            <span class="listsearch-price">Bs. {{ number_format($item->sale_price, 2) }}</span>
                                                            @if($item->type == 0)
                                                                <span class="listsearch-stock {{ $currentStock == 0 ? 'stock-zero' : '' }}">
                                                                    <i class='bx bx-error-circle' style="{{ $currentStock == 0 ? '' : 'display:none;' }}"></i>
                                                                    Stock: {{ $currentStock }}
                                                                </span>
                                                            @endif
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
                                    <th style="width: 10%;">CÓDIGO</th>
                                    <th style="width: 25%;">PRODUCTO</th>
                                    <th style="width: 5%; text-align: center;">PRECIO VENTA</th>
                                    <th style="width: 10%; text-align: center;">CANTIDAD</th>
                                    <th style="width: 10%; text-align: center;">SUBTOTAL</th>
                                    <th style="width: 0%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cart as $cartKey => $item)
                                    <tr wire:key="cart-row-{{ $cartKey }}" 
                                        x-data="{ qty: {{ $item['quantity'] }} }"
                                        @update-qty-input.window="if ($event.detail[0].productId == '{{ $cartKey }}') { qty = $event.detail[0].qty }">
                                        <td>{{ $item['code'] }}</td>
                                        
                                        <td>
                                            <div class="d-flex align-items-center gap-1" style="line-height: 1.2;">

                                                <span>{{ $item['name'] }}</span>
                                                @if(isset($item['lot_info']) && $item['lot_info'])
                                                    <button type="button" class="lot-ven-btn" wire:click="openLotModal('{{ $cartKey }}')">
                                                        <span class="lot-ven-icon">
                                                            <i class="bx bx-package"></i>
                                                            <span class="lot-ven-count">{{ isset($item['allocated_lots']) ? count($item['allocated_lots']) : 1 }}</span>
                                                        </span>
                                                    </button>
                                                @endif
                                            </div>
                                            
                                            @if(!empty($item['sku_name']))
                                                <small class="text-muted fw-bold d-block"> {{ $item['sku_name'] }}</small>
                                            @endif

                                            @if(!empty($item['unit_name']))
                                                <small class="text-muted fw-bold d-block">{{ $item['unit_name'] }}</small>
                                            @endif

                                            @if ($enable_staff_per_detail == 1 && $item['type'] == 1)
                                                <div class="mt-1 d-flex align-items-center cursor-pointer"
                                                    wire:click="openEmployeeModal('{{ $cartKey }}')">
                                                    <i class="bx bx-user-plus text-success me-1"></i>
                                                    <span class="text-success" style="font-size: 0.85rem;">
                                                        {{ $item['employee_name'] ?? 'Asignar Usuario' }}
                                                    </span>
                                                </div>
                                            @endif

                                            @if(isset($item['lot_info']) && $item['lot_info'] && isset($item['allocated_lots']))
                                                <div class="d-flex flex-column gap-0 mt-1">
                                                    @foreach($item['allocated_lots'] as $alloc)
                                                        @php
                                                            $expDate = $alloc['expiration_date'] ?? null;
                                                            $expFormatted = $expDate ? \Carbon\Carbon::parse($expDate)->format('d/m/Y') : null;
                                                            $isExpiringSoon = $expDate && \Carbon\Carbon::parse($expDate)->diffInDays(now()) <= 30 && \Carbon\Carbon::parse($expDate)->isFuture();
                                                            $isExpired = $expDate && \Carbon\Carbon::parse($expDate)->isPast();
                                                        @endphp
                                                        <span class="lot-ven-badge {{ $isExpired ? 'lot-ven-expired' : ($isExpiringSoon ? 'lot-ven-soon' : 'lot-ven-ok') }}">
                                                            <span class="lot-ven-badge-name">{{ $alloc['lot_number'] }}</span><span class="lot-ven-badge-qty">{{ $alloc['quantity'] }}</span>@if($expFormatted)<span class="lot-ven-badge-date">{{ $expFormatted }}</span>@endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            @if (isset($item['is_wholesale']) && $item['is_wholesale'])
                                                <div class="text-center">
                                                    <span class="badge bg-success mb-1">Precio por Mayor</span>
                                                    <div class="fw-bold text-success">Bs. {{ number_format($item['sale_price'], 2) }}</div>
                                                    <small class="text-muted">Min: {{ $item['wholesale_min_quantity'] }} unidades</small>
                                                </div>
                                            @else
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <input type="text" class="custom-input me-1 text-center"
                                                        value="{{ $item['sale_price'] }}"
                                                        wire:change.prevent="setCustomPrice('{{ $cartKey }}', $event.target.value)"
                                                        maxlength="8" inputmode="decimal"
                                                        oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                                        style="width: 80px;">
                                                    
                                                    @php
                                                        $hasAdditionalNormalPrices = isset($item['prices']) && collect($item['prices'])->where('type', 'normal')->count() > 0;
                                                    @endphp
                                                    
                                                    @if ($hasAdditionalNormalPrices)
                                                        <button type="button"
                                                            class="btn btn-sm btn-light d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalPrice{{ str_replace('_', '', $cartKey) }}"
                                                            style="width: 20px; height: 20px;">
                                                            <i class="bx bx-dots-vertical-rounded" style="font-size: 16px; color: #555;"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif

                                            @if (isset($item['prices']) && collect($item['prices'])->where('type', 'normal')->count() > 0)
                                            <div wire:ignore.self class="modal fade"
                                                id="modalPrice{{ str_replace('_', '', $cartKey) }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header py-2">
                                                            <h6 class="modal-title">Precios Disponibles</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body py-2 text-start">
                                                            <div class="d-grid gap-2">
                                                                <button type="button"
                                                                    class="btn btn-secondary btn-sm text-start"
                                                                    wire:click="setPredefinedPrice('{{ $cartKey }}', '{{ $item['original_sale_price'] }}', 'normal')"
                                                                    data-bs-dismiss="modal">
                                                                    <div class="d-flex justify-content-between">
                                                                        <span>Precio Base</span>
                                                                        <strong>Bs. {{ number_format($item['original_sale_price'], 2) }}</strong>
                                                                    </div>
                                                                </button>
                                                                @foreach($item['prices'] as $p)
                                                                    @if($p['type'] === 'normal')
                                                                        <button type="button"
                                                                            class="btn btn-secondary btn-sm text-start"
                                                                            wire:click="setPredefinedPrice('{{ $cartKey }}', '{{ $p['price'] }}', '{{ $p['name'] }}')"
                                                                            data-bs-dismiss="modal">
                                                                            <div class="d-flex justify-content-between">
                                                                                <span>{{ $p['name'] }}</span>
                                                                                <strong>Bs. {{ number_format($p['price'], 2) }}</strong>
                                                                            </div>
                                                                        </button>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
                                                    autocomplete="off" style="width: 50px;">

                                                <button type="button" class="qty-btn plus"
                                                    @click="qty++"
                                                    wire:click.prevent="updateQty('{{ $cartKey }}', {{ $item['quantity'] + 1 }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="updateQty('{{ $cartKey }}', {{ $item['quantity'] + 1 }})">
                                                    <i class="bx bx-plus fw-bold"></i>
                                                </button>

                                            </div>
                                        </td>

                                        <td class="text-center fw-semibold">
                                            @if(isset($item['free_qty']) && $item['free_qty'] > 0)
                                                <div class="text-decoration-line-through text-muted" style="font-size: 0.75rem;">
                                                    Bs. {{ number_format($item['quantity'] * $item['sale_price'], 2) }}
                                                </div>
                                                <div class="text-success fw-bold">
                                                    Bs. {{ number_format($item['subtotal'], 2) }}
                                                </div>
                                                <div class="badge bg-success mt-1" style="font-size: 0.65rem;">
                                                    <i class="bx bx-gift"></i> {{ $item['free_qty'] }} GRATIS
                                                </div>
                                            @else
                                                Bs. {{ number_format($item['subtotal'], 2) }}
                                            @endif
                                        </td>

                                        <td>
                                            <div class="d-flex order-actions justify-content-center">
                                                <a href="javascript:;"
                                                    wire:click.prevent="removeItem('{{ $cartKey }}')"
                                                    class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="text-center">
                                        <td colspan="6" class="text-muted py-4">Tu carrito está vacío. ¡Agrega algunos productos!</td>
                                    </tr>
                                @endforelse
                                <tr class="template-tr-spacer">
                                    <td colspan="6" class="template-td-spacer"></td>
                                </tr>
                            </tbody>
                            <tfoot class="template-sticky-tfoot">
                                <tr>
                                    <td colspan="2" class="text-start fw-bold">ARTÍCULOS: {{ $items }} (items)</td>
                                    <td colspan="2" class="text-end fw-bold">TOTAL:</td>
                                    <td colspan="2" class="fw-bold">Bs. {{ number_format($subtotal, 2) }}</td>
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
                    RESUMEN DE VENTA
                </div>
                <div class="card-body d-flex flex-column px-3 py-3 flex-grow-1 overflow-hidden">
                        <div class="row g-2">
                            <div class="col-12 position-relative" x-data="{ openSearch: false, openMenu: false }" @click.outside="openSearch = false; openMenu = false; $wire.clearWorkerSearch()">
                                <div class="ct-search-container mb-0">
                                    <div class="ct-search-wrapper">
                                        <input type="text" class="ct-search-input search-input"
                                            wire:model.live.debounce.300ms="workerSearchTerm"
                                            @focus="openSearch = true; $wire.set('showWorkerDropdown', true); $el.select()"
                                            placeholder="Buscar trabajador por documento o nombre..." autocomplete="off">

                                        <div class="ct-search-actions">
                                            @if($workers_id && $workers_id != 1)
                                                <button class="ct-action-btn ct-clear" type="button" wire:click="clearWorkerSelection" title="Quitar">
                                                    <i class="bx bx-x"></i>
                                                </button>
                                                <div class="ct-divider"></div>
                                            @endif

                                            <div class="position-relative">
                                                <a class="ct-search-icon-btn" 
                                                    title="Opciones">
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
                                                </div>
                                                <i class="bx bx-check text-success {{ $workers_id == $result->id ? '' : 'd-none' }}"></i>
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif($showWorkerDropdown && strlen($workerSearchTerm) >= 1)
                                    <ul class="ct-customer-dropdown" x-show="openSearch">
                                        <li class="ct-list-item-empty"
                                            @click="openSearch = false">
                                            No se encontraron resultados
                                        </li>
                                    </ul>
                                @endif
                            </div>

                           

                            <div class="col-lg-12 col-sm-6 mb-2 mt-2">
                                <label class="form-label mb-1" style="font-size: 0.85rem;">Fecha de Venta</label>
                                <div class="position-relative input-icon">
                                    <input type="date" class="form-control" wire:model.lazy="sale_date"
                                        max="{{ date('Y-m-d') }}">
                                    <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                                </div>
                                @error('sale_date')
                                    <span class="text-danger er">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-12 col-sm-6 mb-2">
                                <label class="form-label mb-1 text-muted" style="font-size: 0.82rem;">
                                    <i class="bx bx-comment-detail me-1"></i>Observación / Comentario
                                </label>
                                <textarea class="form-control form-control-sm"
                                    wire:model.defer="observations"
                                    placeholder="Observación / Comentario..."
                                    rows="2"
                                    maxlength="255"
                                    style="resize: none; font-size: 0.85rem;"></textarea>
                            </div>

                            <div class="col-lg-12 col-sm-6">
                                <div class="card shadow-none border mb-2 bg-light">
                                    <div class="card-body p-2">
                                        <div class="row text-center align-items-center g-1">
                                            <div class="col-3">
                                                <small class="text-muted fw-bold d-block" style="font-size: 0.70rem;">SUBTOTAL</small>
                                                <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ number_format($subtotal, 2) }}</span>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted fw-bold d-block" style="font-size: 0.70rem;">DESC.</small>
                                                <input type="text" class="form-control form-control-sm text-center fw-bold mx-auto py-0" style="width: 100%; max-width: 65px; height: 26px; border-color: #ced4da;" value="{{ $discount }}" @if (count($cart) == 0) disabled @endif wire:change="updateDiscount($event.target.value)" maxlength="8" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" @update-discount-input.window="$el.value = $event.detail[0].discount">
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted fw-bold d-block" style="font-size: 0.70rem;">DESCUENTO</small>
                                                <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ number_format($discount ?? 0, 2) }}</span>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted fw-bold d-block" style="font-size: 0.70rem;">TOTAL</small>
                                                <span class="fw-bold text-danger" style="font-size: 1.05rem;">{{ number_format($total_cart, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <div class="mt-auto pt-3 flex-shrink-0 border-top">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                           <button class="btn btn-success btnIcon flex-grow-1 py-2 fw-bold"
                                    wire:loading.attr="disabled" wire:click="confirPayment" wire:target="confirPayment"
                                    @if (!$boxExists) disabled @endif>
                                    <span wire:loading.remove wire:target="confirPayment">
                                        <i class="bx bx-check-circle align-middle me-1"></i> CONFIRMAR
                                    </span>
                                    <span wire:loading wire:target="confirPayment">
                                        <i class="bx bx-spin bx-loader align-middle me-1"></i> PROCESANDO...
                                    </span>
                                </button>
                            <button class="btn btn-danger btnIcon flex-grow-1 py-2 fw-bold" wire:click.prevent="clearSales"
                                wire:loading.attr="disabled" wire:target="clearSales"
                                @if (count($cart) == 0 && !$is_editing) disabled @endif>
                                <span wire:loading.remove wire:target="clearSales">
                                    <i class="bx bx-x-circle align-middle me-1"></i> CANCELAR
                                </span>
                                <span wire:loading wire:target="clearSales">
                                    <i class="bx bx-spin bx-loader align-middle me-1"></i> PROCESANDO...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div wire:ignore.self class="modal fade" id="skuModal" tabindex="-1" aria-labelledby="skuModal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" style="max-height: 90vh;">
                    <div class="modal-header">
                        <h1 class="modal-title">
                            <i class="bx bx-customize"></i> SELECCIONA UNA VARIANTE
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="overflow-y: auto;">
                        <div class="recipe-product-header">
                            <div class="recipe-product-icon">
                                <i class="bx bx-package"></i>
                            </div>
                            <div class="recipe-product-info">
                                <h2 class="recipe-product-name">{{ $selectedProduct->name ?? 'Seleccionar producto' }}</h2>
                                <p class="recipe-product-price">Bs. {{ number_format($selectedProduct->inventories->sale_price ?? 0, 2) }}</p>
                            </div>
                        </div>

                       
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" wire:click.prevent="confirmSku()" class="btn btn-danger" wire:loading.attr="disabled" wire:target="confirmSku">
                            <span wire:loading.remove wire:target="confirmSku">Aceptar</span>
                            <span wire:loading wire:target="confirmSku"><i class="bx bx-spin bx-loader me-1"></i> Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div wire:ignore.self class="modal fade" id="productUnitSaleModal" tabindex="-1" aria-labelledby="productUnitSaleModal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" style="max-height: 90vh;">
                    <div class="modal-header">
                        <h1 class="modal-title">
                            <i class="bx bx-purchase-tag"></i> SELECCIONA UNA UNIDAD DE MEDIDA
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="overflow-y: auto;">
                        <div class="recipe-product-header mb-3">
                            <div class="recipe-product-icon">
                                <i class="bx bx-box"></i>
                            </div>
                            <div class="recipe-product-info">
                                <h2 class="recipe-product-name">{{ $selectedProduct->name ?? 'Seleccionar producto' }}</h2>
                            </div>
                        </div>

                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" wire:click.prevent="confirmProductUnit()" class="btn btn-danger" wire:loading.attr="disabled" wire:target="confirmProductUnit">
                            <span wire:loading.remove wire:target="confirmProductUnit">Aceptar</span>
                            <span wire:loading wire:target="confirmProductUnit"><i class="bx bx-spin bx-loader me-1"></i> Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div wire:ignore.self class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="employeeModalLabel">ASIGNAR USUARIO</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3 p-2">
                            <div class="mb-3">
                                <div class="position-relative">
                                    <input type="text" class="form-control ps-5"
                                        wire:model.live.debounce.300ms="searchEmployee"
                                        placeholder="Buscar por Nombre, Apellido, Documento o Correo..." maxlength="20"
                                        style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; border-radius: 10px;">
                                    <span class="position-absolute product-show translate-middle-y" style="top: 55%;"><i class="bx bx-search-alt"></i></span>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>DOCUMENTO</th>
                                            <th>NOMBRE COMPLETO</th>
                                            <th>CORREO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (empty($listEmployees) || count($listEmployees) == 0)
                                            <tr>
                                                <td colspan="3" class="text-center">No se encontraron registros.</td>
                                            </tr>
                                        @else
                                            @foreach ($listEmployees as $emp)
                                                <tr wire:click="setEmployee({{ $emp->id }})" style="cursor: pointer;" wire:key="emp-row-{{ $emp->id }}">
                                                    <td>{{ $emp->document }}</td>
                                                    <td>{{ $emp->name }} {{ $emp->lastname }}</td>
                                                    <td>{{ $emp->email }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('livewire:init', function() {

        Livewire.on('alert', (data) => {
            const [msg, type] = data;
            toast(msg, type);
        });

        Livewire.on('cash_boxeStoreOrUpdate', (data) => {
            $('#theModal').modal('hide');
            const msg = Array.isArray(data) ? data[0] : data;
            toast(msg, 'success');
        });

        Livewire.on('customerStore', (data) => {
            const [msg, type] = data;
            toast(msg, type);
            $('#customerModal').modal('hide');
        });

        Livewire.on('paymentModal', () => {
            $('#paymentsModal').modal('show');
            let iefectivo = document.getElementById('iefectivo');
            let efectivoRadio = document.querySelector('[name="payment"][value="EFECTIVO"]');
            if (iefectivo && efectivoRadio && efectivoRadio.checked) {
                setTimeout(() => {
                    iefectivo.focus();
                    iefectivo.select();
                }, 50);
            }
            window.dispatchEvent(new Event('recalculate-payment'));
        });

        Livewire.on('show-sku-modal', () => {
            $('#skuModal').modal('show');
        });

        Livewire.on('closeSkuModal', () => {
            $('#skuModal').modal('hide');
        });

        Livewire.on('openProductUnitSaleModal', () => {
            $('#productUnitSaleModal').modal('show');
        });

        Livewire.on('closeProductUnitSaleModal', () => {
            $('#productUnitSaleModal').modal('hide');
        });

        Livewire.on('show-employee-modal', () => {
            $('#employeeModal').modal('show');
        });

        Livewire.on('close-employee-modal', () => {
            $('#employeeModal').modal('hide');
        });

        Livewire.on('show-lot-modal', () => {
            $('#lotModal').modal('show');
        });

        Livewire.on('close-lot-modal', () => {
            $('#lotModal').modal('hide');
        });

        Livewire.on('openSkuModal', () => {
            $('#skuModal').modal('show');
        });

        Livewire.on('processPrintBehavior', (data) => {
            $('#paymentsModal').modal('hide');
            let info = Array.isArray(data) ? data[0] : data;
            let url = info.url;
            let behavior = info.behavior;
            let message = info.message;

            if (!url) return;

            if (behavior === 'none') {
                Swal.fire({
                    title: '¡Excelente!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#3085d6'
                });
            } else if (behavior === 'popup') {
                let iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = url;
                document.body.appendChild(iframe);
                iframe.onload = function () {
                    setTimeout(function () {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    }, 500);
                };
            } else {
                window.open(url, "_blank");
            }
        });

        Livewire.on('focusSearchInput', () => {
            let isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
            if (window.innerWidth > 1024 && !isTouchDevice) {
                let searchInput = document.querySelector('.search-input');
                if (searchInput) {
                    searchInput.focus();
                }
            }
        });
    });
</script>