@push('title', 'Productos')

<div class="page-content"
    style="height: calc(100vh - 60px); overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0;">
    <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Productos</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Productos</li>
            </ol>
            @can('crear-productos')
                @include('components.tools.buttonRegister')
            @endcan
        </div>
    </div>

    <div class="card" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2" style="flex-shrink: 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-box"></i>
                <span>Listar Productos</span>
            </div>
        </div>

        <div class="card-body px-3"
            style="flex: 1; min-height: 0; overflow: hidden; display: flex; flex-direction: column;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2"
                style="flex-shrink: 0;">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Mostrar</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">registros</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @component('components.tools.filterbox', ['filterCount' => ($filter_category ? 1 : 0) + ($filter_brand ? 1 : 0) + ($filter_type !== '' ? 1 : 0) + ($filter_status != 1 ? 1 : 0)])
                    <div class="mb-2">
                        <select wire:model.live="filter_category" class="form-select filter-pro-select">
                            <option value="">FILTRO POR CATEGORIA</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ mb_strtoupper($cat->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <select wire:model.live="filter_brand" class="form-select filter-pro-select">
                            <option value="">FILTRO POR LINEA O MARCA</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ mb_strtoupper($brand->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <select wire:model.live="filter_type" class="form-select filter-pro-select">
                            <option value="">FILTRO POR TIPO DE PRODUCTO</option>
                            <option value="0">PRODUCTO</option>
                            <option value="1">SERVICIO</option>
                            <option value="2">RECETA</option>
                            <option value="3">INSUMO</option>
                            <option value="4">EMPAQUE</option>
                            <option value="5">COMBO</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <select wire:model.live="filter_status" class="form-select filter-pro-select">
                            <option value="1">ACTIVO</option>
                            <option value="0">INACTIVO</option>
                            <option value="">TODOS</option>
                        </select>
                    </div>
                    @endcomponent
                    @include('components.tools.searchbox')
                </div>
            </div>

            <div class="table-responsive" style="flex: 1; min-height: 0; overflow: auto;">
                <table class="table table-hover align-middle table-striped" style="width: 100%;">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>CODIGO</th>
                            <th>PRODUCTO</th>
                            <th>TIPO PRODUCTO</th>
                            <th>PRECIO COMPRA</th>
                            <th>PRECIO VENTA</th>
                            <th>CATEGORIA</th>
                            <th>MARCA</th>
                            <th>UMD</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($products->isEmpty())
                            <tr>
                                <td colspan="11" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($products as $index => $product)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $product->code ?: 'S/N' }}</td>
                                    <td>{{ $product->name ?: 'S/N' }}</td>
                                    <td>
                                        @if ($product->type == 0)
                                            <div class="badge rounded-pill text-primary bg-light-primary text-uppercase">Producto
                                            </div>
                                        @elseif ($product->type == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">Servicio
                                            </div>
                                        @elseif ($product->type == 2)
                                            <div class="badge rounded-pill text-info bg-light-info text-uppercase">Receta</div>
                                        @elseif ($product->type == 3)
                                            <div class="badge rounded-pill text-warning bg-light-warning text-uppercase">Insumo
                                            </div>
                                        @elseif ($product->type == 4)
                                            <div class="badge rounded-pill text-orange bg-light-orange text-uppercase">Empaque</div>
                                        @elseif ($product->type == 5)
                                            <div class="badge rounded-pill text-purple bg-light-purple text-uppercase"
                                                style="background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;">Combo</div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">Desconocido
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ optional($product->inventories)->purchase_price ?: '0.00' }}</td>
                                    <td>{{ optional($product->inventories)->sale_price ?: '0.00' }}</td>
                                    <td>{{ $product->categories->name }}</td>
                                    <td>{{ $product->brands->name }}</td>
                                    <td>{{ $product->units->name ?? '-' }}</td>
                                    <td>
                                        @if ($product->status == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">ACTIVO
                                            </div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">INACTIVO
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            @can('editar-productos')
                                                <a href="javascript:;" wire:click="edit({{ $product->id }})" data-bs-toggle="modal"
                                                    data-bs-target="#theModal" class="btn-action-primary"><i
                                                        class="bx bxs-edit-alt"></i></a>
                                            @endcan
                                            @if ($product->status == 1)
                                                @can('eliminar-productos')
                                                    <a href="javascript:;" onclick="confirmDelete({{ $product->id }}, 'delete')"
                                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                                @endcan
                                            @else
                                                @can('restaurar-productos')
                                                    <a href="javascript:;" onclick="confirmDelete({{ $product->id }}, 'restore')"
                                                        class="btn-action-warning ms-1"><i class="bx bx-refresh"></i></a>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div style="flex-shrink: 0; padding-top: 0.4rem;">
                {{ $products->links() }}
            </div>

            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content"
                        x-data="{ type: @entangle('type'), tab: 'info', skus: @entangle('skus') }">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-package"></i>
                                {{ $isEditMode ? 'ACTUALIZAR PRODUCTO' : 'REGISTRAR PRODUCTO' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body"
                            style="max-height: calc(100vh - 200px); overflow-y: auto; overflow-x: hidden;">

                            <ul class="nav nav-pills nav-pills-ultra mb-1 flex-wrap" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a @click.prevent="tab = 'info'" class="nav-link"
                                        :class="tab === 'info' ? 'active' : ''" href="javascript:;" role="tab">
                                        <div class="d-flex align-items-center">
                                            <div class="tab-icon"><i class='bx bx-info-circle font-18 me-1'></i></div>
                                            <div class="tab-title">Información</div>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation" x-show="type != 4 && type != '4'">
                                    <a @click.prevent="tab = 'precios'" class="nav-link"
                                        :class="tab === 'precios' ? 'active' : ''" href="javascript:;" role="tab">
                                        <div class="d-flex align-items-center">
                                            <div class="tab-icon"><i class='bx bx-money font-18 me-1'></i></div>
                                            <div class="tab-title">L. Precios</div>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation" x-show="type == 0 || type == '0'">
                                    <a @click.prevent="tab = 'unidades'" class="nav-link"
                                        :class="tab === 'unidades' ? 'active' : ''" href="javascript:;" role="tab">
                                        <div class="d-flex align-items-center">
                                            <div class="tab-icon"><i class='bx bx-ruler font-18 me-1'></i></div>
                                            <div class="tab-title">Unid. Medida</div>
                                        </div>
                                    </a>
                                </li>
                                @if ($enable_size_color == 1)
                                    <li class="nav-item" role="presentation" x-show="type == 0 || type == '0'"
                                        style="display: none;">
                                        <a @click.prevent="tab = 'variantes_tc'" class="nav-link"
                                            :class="tab === 'variantes_tc' ? 'active' : ''" href="javascript:;" role="tab">
                                            <div class="d-flex align-items-center">
                                                <div class="tab-icon"><i class='bx bx-purchase-tag-alt font-18 me-1'></i>
                                                </div>
                                                <div class="tab-title">Tallas/Colores</div>
                                            </div>
                                        </a>
                                    </li>
                                @endif
                                <li class="nav-item" role="presentation"
                                    x-show="(type == 0 || type == '0' || type == 2 || type == '2') && ({{ $pos_type }} == 0 || {{ $pos_type }} == 4)"
                                    style="display: none;">
                                    <a @click.prevent="tab = 'adicionales'" class="nav-link"
                                        :class="tab === 'adicionales' ? 'active' : ''" href="javascript:;" role="tab">
                                        <div class="d-flex align-items-center">
                                            <div class="tab-icon"><i class='bx bx-list-plus font-18 me-1'></i></div>
                                            <div class="tab-title">Adicionales</div>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation" x-show="type == 2 || type == '2'"
                                    style="display: none;">
                                    <a @click.prevent="tab = 'variantes_receta'" class="nav-link"
                                        :class="tab === 'variantes_receta' ? 'active' : ''" href="javascript:;"
                                        role="tab">
                                        <div class="d-flex align-items-center">
                                            <div class="tab-icon"><i class='bx bx-customize font-18 me-1'></i></div>
                                            <div class="tab-title">Variantes</div>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation"
                                    x-show="(type == 2 || type == '2') && ({{ $pos_type }} == 0 || {{ $pos_type }} == 4)"
                                    style="display: none;">
                                    <a @click.prevent="tab = 'ingredientes'" class="nav-link"
                                        :class="tab === 'ingredientes' ? 'active' : ''" href="javascript:;" role="tab">
                                        <div class="d-flex align-items-center">
                                            <div class="tab-icon"><i class='bx bx-restaurant font-18 me-1'></i></div>
                                            <div class="tab-title">Insumos</div>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation"
                                    x-show="(type == 0 || type == '0' || type == 2 || type == '2') && ({{ $pos_type }} == 0 || {{ $pos_type }} == 4)"
                                    style="display: none;">
                                    <a @click.prevent="tab = 'empaques'" class="nav-link"
                                        :class="tab === 'empaques' ? 'active' : ''" href="javascript:;" role="tab">
                                        <div class="d-flex align-items-center">
                                            <div class="tab-icon"><i class='bx bx-box font-18 me-1'></i></div>
                                            <div class="tab-title">Empaques</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content" id="danger-pills-tabContent">
                                <div class="tab-pane fade" :class="tab === 'info' ? 'show active' : ''" role="tabpanel">
                                    <div class="row mb-2 p-2">
                                        <div class="col-md-8">
                                            <div class="row">
                                                <div class="col-md-8 col-sm-6 mb-2">
                                                    <label>Codigo</label>
                                                    <div class="input-group">
                                                        <input type="text" wire:model="code" class="form-control"
                                                            placeholder="Escane el código" maxlength="30"
                                                            id="codeInput">
                                                        <button type="button" class="btn btn-show btn-danger"
                                                            wire:click="generateCode()">
                                                            <i class="bx bx-barcode-reader"></i>
                                                        </button>
                                                    </div>
                                                    @if($camera_barcode_enabled == 1)
                                                        <button type="button" class="btn btn-secondary mt-2"
                                                            id="startScanBtn">
                                                            <i class="bx bx-camera"></i> Escanear
                                                        </button>
                                                        <button type="button" class="btn btn-warning mt-2" id="stopScanBtn"
                                                            style="display: none;">
                                                            <i class="bx bx-stop"></i> Detener
                                                        </button>
                                                    @endif
                                                    @error('code') <span class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                    @if($camera_barcode_enabled == 1)
                                                        <div id="qr-reader"
                                                            style="width: 100%; display: none; margin-top: 10px;">
                                                        </div>
                                                        <div id="qr-reader-results" style="margin-top: 10px;"></div>
                                                    @endif
                                                </div>

                                                <div class="col-md-12 col-sm-6 mb-2">
                                                    <label>Producto</label>
                                                    <div class="input-group">
                                                        <input type="text" wire:model.lazy="name" class="form-control"
                                                            placeholder="Producto" maxlength="80">
                                                    </div>
                                                    @error('name') <span class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-12 col-sm-6 mb-2">
                                                    <label>Caracteristicas</label>
                                                    <div class="input-group">
                                                        <textarea id="editor" class="form-control" name="message"
                                                            rows="2" placeholder="Caracteristicas del Producto"
                                                            wire:model="features"></textarea>
                                                    </div>
                                                    @error('features') <span
                                                        class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6 col-sm-6 mb-2">
                                                    <label>Categoria</label>
                                                    <div class="input-group">
                                                        <select wire:model="categorie_id" class="form-select">
                                                            <option value="" selected>Seleccionar</option>
                                                            @foreach ($categories as $categorie)
                                                                <option value="{{ $categorie->id }}"
                                                                    @selected($categorie->id == $categorie_id)>
                                                                    {{ $categorie->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button" class="btn btn-show btn-danger"
                                                            onclick="openCategoryModal()"><i
                                                                class="bx bx-plus"></i></button>
                                                    </div>
                                                    @error('categorie_id') <span
                                                        class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6 col-sm-6 mb-3">
                                                    <label>Marca</label>
                                                    <div class="input-group">
                                                        <select wire:model="brand_id" class="form-select">
                                                            <option value="" selected>Seleccionar</option>
                                                            @foreach ($brands as $brand)
                                                                <option value="{{ $brand->id }}"
                                                                    @selected($brand->id == $brand_id)>
                                                                    {{ $brand->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button" class="btn btn-show btn-danger"
                                                            onclick="openBrandModal()"><i
                                                                class="bx bx-plus"></i></button>
                                                    </div>
                                                    @error('brand_id') <span
                                                        class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6 col-sm-6 mb-3" x-show="type != 5 && type != '5'">
                                                    <label>Unidad de Medida</label>
                                                    <div class="input-group">
                                                        <select wire:model="unit_id" class="form-select">
                                                            <option value="" selected>Seleccionar</option>
                                                            @foreach ($units as $unit)
                                                                <option value="{{ $unit->id }}"
                                                                    @selected($unit->id == $unit_id)>
                                                                    {{ $unit->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button" class="btn btn-show btn-danger"
                                                            onclick="openUnitModal()"><i
                                                                class="bx bx-plus"></i></button>
                                                    </div>
                                                    @error('unit_id') <span class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                @if ($pos_type == 4 || $pos_type == 0)
                                                    <div class="col-md-6 col-sm-6 mb-3">
                                                        <label>Área de Producción</label>
                                                        <div class="input-group">
                                                            <select wire:model="production_area_id" class="form-select">
                                                                <option value="" selected>Sin área asignada</option>
                                                                @foreach ($production_areas as $area)
                                                                    <option value="{{ $area->id }}"
                                                                        @selected($area->id == $production_area_id)>
                                                                        {{ $area->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        @error('production_area_id') <span
                                                        class="text-danger er">{{ $message }}</span> @enderror
                                                    </div>
                                                @endif

                                                <div class="col-lg-6 col-sm-6" x-show="type != 5 && type != '5'">
                                                    <label>Existencia Minima</label>
                                                    <div class="position-relative input-icon">
                                                        <input type="text" class="form-control text-end"
                                                            wire:model="minimum_stock" placeholder="0" maxlength="3"
                                                            max="1" inputmode="decimal"
                                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                                        <span class="position-absolute top-50 translate-middle-y"><i
                                                                class="bx bx-box"></i></span>
                                                    </div>
                                                    @error('minimum_stock') <span
                                                        class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                @if(!$isEditMode)
                                                    <div class="col-lg-6 col-sm-6"
                                                        x-show="(type == 0 || type == '0' || type == 3 || type == '3' || type == 4 || type == '4') && skus.length === 0">
                                                        <label>Stock Inicial</label>
                                                        <div class="position-relative input-icon">
                                                            <input type="text" class="form-control text-end"
                                                                wire:model="initial_stock" placeholder="0" maxlength="10"
                                                                inputmode="decimal"
                                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                            <span class="position-absolute top-50 translate-middle-y"><i
                                                                    class="bx bx-box"></i></span>
                                                        </div>
                                                        @error('initial_stock') <span
                                                        class="text-danger er">{{ $message }}</span> @enderror
                                                    </div>
                                                @endif

                                                <div class="col-span-2 mb-1">
                                                    <hr class="my-2">
                                                </div>

                                                <div class="col-lg-4 col-sm-6 mb-2">
                                                    <label>Precio de Compra</label>
                                                    <div class="position-relative input-icon">
                                                        <input type="text" class="form-control text-end price-decimal"
                                                            id="purchase_price" wire:model.lazy="purchase_price"
                                                            placeholder="0.00" wire:change="calculateSalePrice"
                                                            inputmode="decimal" :readonly="type == 5 || type == '5'"
                                                            :class="(type == 5 || type == '5') ? 'bg-light text-muted' : ''">
                                                        <span
                                                            class="position-absolute top-50 translate-middle-y">Bs</span>
                                                    </div>
                                                    @error('purchase_price') <span
                                                        class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="col-lg-4 col-sm-6 mb-2">
                                                    <label>Ganancia (%)</label>
                                                    <div class="position-relative input-icon">
                                                        <input type="text" class="form-control text-end"
                                                            wire:model="profit" value="25" maxlength="3"
                                                            wire:change="calculateSalePrice" inputmode="decimal"
                                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                                            :readonly="type == 5 || type == '5'"
                                                            :class="(type == 5 || type == '5') ? 'bg-light text-muted' : ''">
                                                        <span
                                                            class="position-absolute top-50 translate-middle-y">%</span>
                                                    </div>
                                                    @error('profit') <span class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="col-md-4 col-sm-6 mb-2">
                                                    <label>Precio de Venta</label>
                                                    <div class="position-relative input-icon">
                                                        <input type="text" class="form-control text-end price-decimal"
                                                            id="sale_price" wire:model.lazy="sale_price"
                                                            placeholder="0.00" inputmode="decimal"
                                                            wire:change="calculatePurchasePrice">
                                                        <span
                                                            class="position-absolute top-50 translate-middle-y">Bs</span>
                                                    </div>
                                                    @error('sale_price') <span
                                                        class="text-danger er">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group" wire:ignore>
                                                <div class="d-flex flex-column align-items-center">
                                                    <label for="imageInput" class="text-center">Imagen Principal
                                                        (Portada)</label>
                                                    <div class="image-container">
                                                        <img id="previewImage"
                                                            src="{{ $image_preview ?? asset('assets/images/product.png') }}"
                                                            class="img-thumbnail mt-2"
                                                            style="border-radius: 25px; width: 200px; height: 200px; object-fit: cover; object-position: center;"
                                                            alt="Vista previa">
                                                        <div id="uploadOverlay" class="image-overlay"
                                                            style="display: none;">
                                                            <i class="bx bx-spin bx-loader upload-spinner"></i>
                                                            <span id="uploadText">Subiendo imagen...</span>
                                                            <div class="progress-bar-custom">
                                                                <div id="progressFill" class="progress-fill"
                                                                    style="width: 0%;">
                                                                </div>
                                                            </div>
                                                            <small id="progressPercent" class="mt-1">0%</small>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <input type="file" id="imageInput" name="image"
                                                            class="form-control d-none" accept=".jpg,.jpeg,.png,.webp">
                                                        <button type="button" id="selectImageBtn"
                                                            class="btn btn-outline-danger mt-2"
                                                            onclick="document.getElementById('imageInput').click()">
                                                            <i class="bx bx-cloud-upload"></i> Seleccionar imagen
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('image') <span class="text-danger er">{{ $message }}</span> @enderror

                                            <div class="col-span-2 mt-3">
                                                <hr class="my-2">
                                            </div>

                                            @if($enable_product_gallery == 1)
                                                <div class="form-group mt-3">
                                                    <label class="text-center mb-2 fw-bold w-100">Galería (Máx 3
                                                        imágenes)</label>
                                                    @php $totalPhotos = count($saved_gallery); @endphp
                                                    <div class="drop-zone border-2 border-dashed p-3 text-center rounded bg-light"
                                                        id="dropZone" style="border: 2px dashed #dc3545; cursor: pointer;"
                                                        onclick="handleClickGallery({{ $totalPhotos }})">
                                                        <input type="file" id="galleryInput" multiple accept="image/*"
                                                            class="d-none">
                                                        <div id="galleryUploadLoading" style="display:none">
                                                            <i class="bx bx-loader-alt bx-spin fs-1 text-danger"></i>
                                                            <p class="text-muted m-0">Procesando...</p>
                                                        </div>
                                                        <div id="galleryUploadPlaceholder">
                                                            <i class="bx bx-images fs-1 text-danger"></i>
                                                            <p class="text-muted mb-0 small fw-bold">Click o Arrastra aquí
                                                            </p>
                                                            <p class="text-muted mb-0" style="font-size: 11px;">(Máximo 3
                                                                fotos en
                                                                total)</p>
                                                        </div>
                                                    </div>
                                                    @error('gallery') <span
                                                        class="text-danger small d-block mt-1">{{ $message }}</span>
                                                    @enderror
                                                    <div class="gallery-carousel-wrapper mt-3">
                                                        <button type="button" class="gallery-nav-btn prev"
                                                            onclick="scrollGallery(-100)"><i
                                                                class="bx bx-chevron-left"></i></button>
                                                        <div class="gallery-scroll-area" id="galleryScrollArea">
                                                            @foreach($saved_gallery as $savedImg)
                                                                <div class="gallery-item-box">
                                                                    <img src="{{ asset('storage/' . $savedImg->image_path) }}"
                                                                        alt="img">
                                                                    <button type="button" class="btn-remove-gallery"
                                                                        wire:click="deleteGalleryImage({{ $savedImg->id }})"
                                                                        title="Eliminar al guardar"><i
                                                                            class="bx bx-x"></i></button>
                                                                </div>
                                                            @endforeach
                                                            <div id="new-gallery-display" class="d-flex" wire:ignore></div>
                                                        </div>
                                                        <button type="button" class="gallery-nav-btn next"
                                                            onclick="scrollGallery(100)"><i
                                                                class="bx bx-chevron-right"></i></button>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="col-md-12 mt-3">
                                                <label for="type" class="form-label">Tipo de producto</label>
                                                <div class="d-flex flex-wrap">
                                                    <div class="form-check me-4">
                                                        <input class="form-check-input" type="radio" id="type-producto"
                                                            wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                            value="0">
                                                        <label class="form-check-label" for="type-producto">Es
                                                            Producto?</label>
                                                    </div>
                                                    <div class="form-check me-4">
                                                        <input class="form-check-input" type="radio" id="type-servicio"
                                                            wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                            value="1">
                                                        <label class="form-check-label" for="type-servicio">Es
                                                            Servicio?</label>
                                                    </div>
                                                    @if ($pos_type == 0 || $pos_type == 4)
                                                        <div class="form-check me-4">
                                                            <input class="form-check-input" type="radio" id="type-receta"
                                                                wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                                value="2">
                                                            <label class="form-check-label" for="type-receta">Es
                                                                Receta?</label>
                                                        </div>
                                                        <div class="form-check me-4">
                                                            <input class="form-check-input" type="radio" id="type-insumo"
                                                                wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                                value="3">
                                                            <label class="form-check-label" for="type-insumo">Es
                                                                Insumo?</label>
                                                        </div>
                                                        <div class="form-check me-4">
                                                            <input class="form-check-input" type="radio" id="type-empaque"
                                                                wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                                value="4">
                                                            <label class="form-check-label" for="type-empaque">Es
                                                                Empaque?</label>
                                                        </div>
                                                    @endif
                                                    <div class="form-check me-4">
                                                        <input class="form-check-input" type="radio" id="type-combo"
                                                            wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                            value="5">
                                                        <label class="form-check-label" for="type-combo">Es
                                                            Combo?</label>
                                                    </div>
                                                </div>
                                            </div>

                                            @if($loyalty_program_enabled == 1)
                                                <div class="col-md-12 mt-3">
                                                    <hr class="my-2">
                                                    <div class="d-flex flex-wrap align-items-center">
                                                        <div class="form-check form-switch form-check-danger me-4">
                                                            <input class="form-check-input" type="checkbox" role="switch"
                                                                id="hasLoyaltySwitch" wire:model.live="has_loyalty">
                                                            <label class="form-check-label fw-bold"
                                                                for="hasLoyaltySwitch">¿Activar
                                                                Fidelización?</label>
                                                        </div>

                                                        @if($has_loyalty)
                                                            <div class="ms-3 d-flex align-items-center">
                                                                <label class="me-2 text-muted"
                                                                    style="font-size: 0.85rem;">Cantidad
                                                                    requerida</label>
                                                                <div style="width: 80px;">
                                                                    <input type="text"
                                                                        class="form-control form-control-sm text-center"
                                                                        wire:model="loyalty_req_qty" placeholder="5"
                                                                        maxlength="3"
                                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                                </div>
                                                            </div>
                                                            @error('loyalty_req_qty') <span
                                                                class="text-danger er d-block w-100 mt-1">{{ $message }}</span>
                                                            @enderror
                                                        @endif
                                                    </div>
                                                    <hr class="my-2">
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-md-6 col-sm-6 mt-2">
                                            <div x-show="type == 0 || type == '0'" style="display: none;">
                                                <div class="form-check form-switch form-check-danger">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        id="flexSwitchCheckLot" wire:model.live="lote">
                                                    <label class="form-check-label" for="flexSwitchCheckLot">Con
                                                        Lote?</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12" x-show="type == 5 || type == '5'" style="display: none;">
                                            <div
                                                style="border: 0.5px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff;">
                                                <div
                                                    style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 0.5px solid #e2e8f0; background: #fafafa;">
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <div
                                                            style="width: 32px; height: 32px; background: #fff1f1; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="bx bx-package"
                                                                style="color: #dc3545; font-size: 17px;"></i>
                                                        </div>
                                                        <div>
                                                            <div
                                                                style="font-size: 13px; font-weight: 600; color: #1a1a2e; line-height: 1.2;">
                                                                Productos del combo</div>
                                                            <div style="font-size: 11px; color: #8a8fa8;">Gestiona los
                                                                productos incluidos en este combo</div>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-secondary btnIcon" wire:click="listComboProducts()"
                                                        onclick="openComboModal()">
                                                        <i class="bx bx-plus"></i> AGREGAR PRODUCTO
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-striped table-hover nowrap"
                                                        style="width: 100%;">
                                                        <thead>
                                                            <tr>
                                                                <th>N°</th>
                                                                <th>CODIGO</th>
                                                                <th>PRODUCTO</th>
                                                                <th>COSTO</th>
                                                                <th>P.U</th>
                                                                <th>CANT. MINIMA</th>
                                                                <th>P. TOTAL</th>
                                                                <th>ACCIONES</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($combo_products as $index => $item)
                                                                <tr>
                                                                    <td>{{ $index + 1 }}</td>
                                                                    <td>{{ $item['code'] }}</td>
                                                                    <td>{{ $item['name'] }}</td>
                                                                    <td>Bs {{ number_format($item['purchase_price'], 2) }}
                                                                    </td>
                                                                    <td>Bs {{ number_format($item['sale_price'], 2) }}</td>
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control"
                                                                            value="{{ $item['quantity'] }}"
                                                                            wire:change="updateComboProductQuantity({{ $index }}, $event.target.value)"
                                                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                                            style="width: 100px; text-align: center;" />
                                                                    </td>
                                                                    <td>Bs
                                                                        {{ number_format($item['quantity'] * $item['sale_price'], 2) }}
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex order-actions">
                                                                            <a href="javascript:;"
                                                                                wire:click="removeComboProduct({{ $index }})"
                                                                                class="btn-action-danger"><i
                                                                                    class="bx bxs-trash"></i></a>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="8" class="text-center">No hay productos
                                                                        agregados al combo.</td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <td colspan="6" class="text-end">TOTAL:</td>
                                                                <td> {{ number_format(collect($combo_products)->sum(function($item) { return intval($item['quantity']) * floatval($item['sale_price']); }), 2) }}</td>
                                                                <td></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="tab-pane fade" :class="tab === 'precios' ? 'show active' : ''"
                                    role="tabpanel">
                                    <div class="p-2">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                            <h5 class="card-title mb-0">Lista de Precios Adicionales</h5>
                                            <button type="button" wire:click="addPrice"
                                                class="btn btn-secondary btnIcon">
                                                <i class="bx bx-plus-circle"></i> AGREGAR
                                            </button>
                                        </div>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-striped table-hover nowrap"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>NOMBRE</th>
                                                        <th>TIPO DE PRECIO</th>
                                                        <th>PRECIO</th>
                                                        <th>CANT. MÍN - MÁX</th>
                                                        <th>ACCIONES</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($additional_prices as $index => $price)
                                                        <tr>
                                                            <td>
                                                                <input type="text"
                                                                    wire:model.defer="additional_prices.{{ $index }}.name"
                                                                    class="form-control" placeholder="Precio 1">
                                                                @error('additional_prices.' . $index . '.name') <span
                                                                    class="text-danger small">{{ $message }}</span>
                                                                @enderror
                                                            </td>
                                                            <td>
                                                                <select
                                                                    wire:model.live="additional_prices.{{ $index }}.type"
                                                                    wire:change="updatePriceName({{ $index }})"
                                                                    class="form-select">
                                                                    <option value="normal">Normal</option>
                                                                    <option value="wholesale">Por Mayor</option>
                                                                </select>
                                                                @error('additional_prices.' . $index . '.type') <span
                                                                    class="text-danger small">{{ $message }}</span>
                                                                @enderror
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    wire:model.defer="additional_prices.{{ $index }}.price"
                                                                    class="form-control text-end price-decimal"
                                                                    placeholder="0.00"
                                                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                                                @error('additional_prices.' . $index . '.price') <span
                                                                    class="text-danger small">{{ $message }}</span>
                                                                @enderror
                                                            </td>
                                                            <td>
                                                                <div class="input-group">
                                                                    <input type="text"
                                                                        wire:model.defer="additional_prices.{{ $index }}.min_quantity"
                                                                        class="form-control text-center" placeholder="Mín"
                                                                        {{ $price['type'] !== 'wholesale' ? 'disabled' : '' }}
                                                                        oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                                                    <span class="input-group-text">-</span>
                                                                    <input type="text"
                                                                        wire:model.defer="additional_prices.{{ $index }}.max_quantity"
                                                                        class="form-control text-center" placeholder="Máx"
                                                                        {{ $price['type'] !== 'wholesale' ? 'disabled' : '' }}
                                                                        oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex order-actions">
                                                                    <a href="javascript:;"
                                                                        wire:click="removePrice({{ $index }})"
                                                                        class="btn-action-danger"><i
                                                                            class="bx bxs-trash"></i></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">No hay precios adicionales
                                                                asignados.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" :class="tab === 'unidades' ? 'show active' : ''"
                                    role="tabpanel">
                                    <div class="p-2">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                            <h5 class="card-title mb-0">Listar Unidades de Medida</h5>
                                            <button class="btn btn-secondary btnIcon" wire:click="listProductUnits()"
                                                onclick="openProductUnitModal()"><i class="bx bx-plus-circle"></i>
                                                AGREGAR</button>
                                        </div>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-striped table-hover nowrap"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>N°</th>
                                                        <th>UNIDAD</th>
                                                        <th>UNIDAD DE MEDIDA</th>
                                                        <th>FACTOR</th>
                                                        <th>PRECIO COMPRA</th>
                                                        <th>PRECIO VENTA</th>
                                                        <th>ACCIONES</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($product_units as $index => $item)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $item['unit_name'] }}</td>
                                                            <td>{{ $item['unit_base_unit'] ?? '-' }}</td>
                                                            <td>{{ $item['factor'] ?? '1.0000' }}</td>
                                                            <td>
                                                                <input type="text"
                                                                    class="form-control form-control-sm text-end"
                                                                    value="{{ $item['purchase_price'] }}"
                                                                    wire:change="updateProductUnitPurchasePrice({{ $index }}, $event.target.value)"
                                                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                                                    placeholder="0.00" style="width: 100px;">
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    class="form-control form-control-sm text-end"
                                                                    value="{{ $item['price'] }}"
                                                                    wire:change="updateProductUnitPrice({{ $index }}, $event.target.value)"
                                                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                                                    placeholder="0.00" style="width: 100px;">
                                                            </td>
                                                            <td>
                                                                <div class="d-flex order-actions">
                                                                    <a href="javascript:;"
                                                                        wire:click="removeProductUnit({{ $index }})"
                                                                        class="btn-action-danger"><i
                                                                            class="bx bxs-trash"></i></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7" class="text-center">No hay unidades de medida
                                                                asignadas.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" :class="tab === 'variantes_tc' ? 'show active' : ''"
                                    role="tabpanel">
                                    <div class="p-2">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">Variantes de Producto (Talla y Color)</h5>
                                            <div class="form-check form-switch form-check-danger mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="applySkusAllBranches" wire:model="apply_skus_all_branches">
                                                <label class="form-check-label fw-bold"
                                                    for="applySkusAllBranches">Aplicar a todas las
                                                    sucursales</label>
                                            </div>
                                        </div>
                                        <div class="row mb-3 align-items-end">
                                            <div class="col-md-5 mb-2">
                                                <label>Color</label>
                                                <select wire:model="color_id" class="form-select">
                                                    <option value="">Ninguno</option>
                                                    @foreach($colors as $c)
                                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-5 mb-2">
                                                <label>Talla</label>
                                                <select wire:model="size_id" class="form-select">
                                                    <option value="">Ninguna</option>
                                                    @foreach($sizes as $s)
                                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <button type="button" class="btn btn-secondary btnIcon w-100"
                                                    wire:click="addSkuToProduct()" title="Agregar">
                                                    <i class="bx bx-plus-circle"></i> NUEVO
                                                </button>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-striped table-hover nowrap"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>N°</th>
                                                        <th>COLOR</th>
                                                        <th>TALLA</th>
                                                        <th>SKU</th>
                                                        <th>PRECIO (Bs)</th>
                                                        <th>STOCK</th>
                                                        <th>ACCIONES</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($skus as $index => $item)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $item['color_name'] }}</td>
                                                            <td>{{ $item['size_name'] }}</td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm"
                                                                    value="{{ $item['sku'] }}"
                                                                    wire:change="updateSkuCode({{ $index }}, $event.target.value)">
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <div class="form-check form-switch m-0 p-0 d-flex align-items-center"
                                                                        title="Personalizar Precio">
                                                                        <input class="form-check-input m-0" type="checkbox"
                                                                            style="cursor: pointer;"
                                                                            wire:click="toggleCustomPrice({{ $index }})" {{ !empty($item['is_custom_price']) ? 'checked' : '' }}>
                                                                    </div>
                                                                    @if(!empty($item['is_custom_price']))
                                                                        <input type="text"
                                                                            class="form-control form-control-sm text-end"
                                                                            value="{{ $item['price'] ?? '' }}"
                                                                            placeholder="0.00"
                                                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                                                            wire:change="updateSkuPrice({{ $index }}, $event.target.value)"
                                                                            style="width: 80px;">
                                                                    @else
                                                                        <input type="text"
                                                                            class="form-control form-control-sm text-end bg-light text-muted"
                                                                            value="{{ $sale_price ?: '0.00' }}" readonly
                                                                            title="Precio Base General" style="width: 80px;">
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td>
                                                                @if($isEditMode)
                                                                    <input type="text"
                                                                        class="form-control form-control-sm text-end bg-light"
                                                                        value="{{ $item['stock'] }}" readonly
                                                                        title="El stock se gestiona desde Compras">
                                                                @else
                                                                    <input type="text"
                                                                        class="form-control form-control-sm text-end"
                                                                        value="{{ $item['stock'] }}"
                                                                        wire:change="updateSkuStock({{ $index }}, $event.target.value)"
                                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                                        placeholder="0" title="Stock Inicial">
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div class="d-flex order-actions">
                                                                    <a href="javascript:;"
                                                                        wire:click="removeSku({{ $index }})"
                                                                        class="btn-action-danger"><i
                                                                            class="bx bxs-trash"></i></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7" class="text-center">No hay combinaciones
                                                                agregadas.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" :class="tab === 'adicionales' ? 'show active' : ''"
                                    role="tabpanel">
                                    <div class="p-2">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                            <h5 class="card-title mb-0">Listar Adicionales</h5>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="form-check form-switch form-check-danger mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        id="applyAdditionalsAllBranches"
                                                        wire:model="apply_additionals_all_branches">
                                                    <label class="form-check-label fw-bold"
                                                        for="applyAdditionalsAllBranches">Aplicar a todas las
                                                        sucursales</label>
                                                </div>
                                                <button class="btn btn-secondary btnIcon" wire:click="listAdditional()"
                                                    onclick="openAdditionalModal()"><i class="bx bx-plus-circle"></i>
                                                    AGREGAR</button>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-striped table-hover nowrap"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>N°</th>
                                                        <th>DESCRIPCION</th>
                                                        <th>PRECIO</th>
                                                        <th>SELECCION</th>
                                                        <th>ACCIONES</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($additionals as $index => $item)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $item['additional']['name'] ?? '-' }}</td>
                                                            <td><input type="text" class="form-control"
                                                                    value="{{ $item['price'] }}"
                                                                    wire:change="updateAdditionalPrice({{ $index }}, $event.target.value)"
                                                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                                                    style="width: 80px;" /></td>
                                                            <td>
                                                                @if ($item['selection_type'] == 1) Obligatorio @else
                                                                Múltiple @endif
                                                                <input type="checkbox"
                                                                    wire:change="updateSelectionType({{ $index }})"
                                                                    value="{{ $item['selection_type'] }}" {{ $item['selection_type'] == 1 ? 'checked' : '' }}>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex order-actions"><a href="javascript:;"
                                                                        wire:click="removeAdditional({{ $index }})"
                                                                        class="btn-action-danger"><i
                                                                            class="bx bxs-trash"></i></a></div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">No hay adicionales
                                                                asignados.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" :class="tab === 'variantes_receta' ? 'show active' : ''"
                                    role="tabpanel">
                                    <div class="p-2">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                            <h5 class="card-title mb-0">Listar Variantes de Receta</h5>
                                            <button class="btn btn-secondary btnIcon" wire:click="listVariant()"
                                                onclick="openVariantModal()"><i class="bx bx-plus-circle"></i>
                                                AGREGAR</button>
                                        </div>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-striped table-hover nowrap"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>N°</th>
                                                        <th>DESCRIPCION</th>
                                                        <th>PRECIO</th>
                                                        <th>ACCIONES</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($variants as $index => $item)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $item['variant']['name'] ?? '-' }}</td>
                                                            <td><input type="text" class="form-control"
                                                                    value="{{ $item['price_variant'] }}"
                                                                    wire:change="updateVariantPrice({{ $index }}, $event.target.value)"
                                                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                                                    style="width: 80px;" /></td>
                                                            <td>
                                                                <div class="d-flex order-actions"><a href="javascript:;"
                                                                        wire:click="removeVariant({{ $index }})"
                                                                        class="btn-action-danger"><i
                                                                            class="bx bxs-trash"></i></a></div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="text-center">No hay variantes
                                                                asignados.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" :class="tab === 'ingredientes' ? 'show active' : ''"
                                    role="tabpanel">
                                    <div class="p-2">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                            <h5 class="card-title mb-0">Listar Ingredientes (Insumos)</h5>
                                            <button class="btn btn-secondary btnIcon" wire:click="listIngredient()"
                                                onclick="openIngredientModal()"><i class="bx bx-plus-circle"></i>
                                                AGREGAR INSUMO</button>
                                        </div>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-striped table-hover nowrap"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>N°</th>
                                                        <th>INSUMO</th>
                                                        <th>MEDIDA</th>
                                                        <th>CANTIDAD</th>
                                                        <th>ACCIONES</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($ingredients as $index => $item)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $item['name'] }}</td>
                                                            <td>{{ $item['unit_name'] ?? 'Unid' }}</td>
                                                            <td><input type="number" step="0.001" class="form-control"
                                                                    value="{{ $item['quantity'] }}"
                                                                    wire:change="updateIngredientQuantity({{ $index }}, $event.target.value)"
                                                                    style="width: 100px; text-align: center;" />
                                                            </td>
                                                            <td>
                                                                <div class="d-flex order-actions"><a href="javascript:;"
                                                                        wire:click="removeIngredient({{ $index }})"
                                                                        class="btn-action-danger"><i
                                                                            class="bx bxs-trash"></i></a></div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">No se han asignado
                                                                ingredientes a esta receta.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" :class="tab === 'empaques' ? 'show active' : ''"
                                    role="tabpanel">
                                    <div class="p-2">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                            <h5 class="card-title mb-0">Listar Empaques</h5>
                                            <button class="btn btn-secondary btnIcon" wire:click="listPackaging()"
                                                onclick="openPackagingModal()"><i class="bx bx-plus-circle"></i> AGREGAR
                                                EMPAQUE</button>
                                        </div>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-striped table-hover nowrap"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>N°</th>
                                                        <th>EMPAQUE</th>
                                                        <th>MEDIDA</th>
                                                        <th>CANTIDAD</th>
                                                        <th>ACCIONES</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($packagings as $index => $item)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $item['name'] }}</td>
                                                            <td>{{ $item['unit_name'] ?? 'Unid' }}</td>
                                                            <td><input type="number" step="0.001" class="form-control"
                                                                    value="{{ $item['quantity'] }}"
                                                                    wire:change="updatePackagingQuantity({{ $index }}, $event.target.value)"
                                                                    style="width: 100px; text-align: center;" /></td>
                                                            <td>
                                                                <div class="d-flex order-actions"><a href="javascript:;"
                                                                        wire:click="removePackaging({{ $index }})"
                                                                        class="btn-action-danger"><i
                                                                            class="bx bxs-trash"></i></a></div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-center">No se han asignado empaques.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                wire:click="resetInputFields">Cerrar</button>
                            <button type="button" id="saveBtn" wire:click.prevent="storeOrUpdate()"
                                class="btn btn-danger" wire:loading.attr="disabled" wire:target="storeOrUpdate">
                                <span wire:loading.remove
                                    wire:target="storeOrUpdate">{{ $isEditMode ? 'Actualizar' : 'Guardar' }}</span>
                                <span wire:loading wire:target="storeOrUpdate"><i class="bx bx-spin bx-loader"></i>
                                    Procesando...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @include('livewire.products.form')
            @include('livewire.products.recipe')

            <div wire:ignore.self class="modal fade" id="ingredientModal" tabindex="-1"
                aria-labelledby="ingredientModal" aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="ingredientModal">LISTAR INSUMOS</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                <div>
                                    <div class="position-relative">
                                        <input type="text" class="form-control ps-5" wire:model.live="searchIngredient"
                                            placeholder="Buscar..." maxlength="20"
                                            style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; border-radius: 10px;">
                                        <span class="position-absolute product-show translate-middle-y"
                                            style="top: 55%;"><i class="bx bx-search-alt"></i></span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>DESCRIPCIÓN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($list_ingredients as $index => $item)
                                            <tr wire:click="addIngredientToProduct({{ $item->id }})"
                                                style="cursor: pointer;">
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $item->name ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">No hay insumos registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                                wire:click="resetIngredient">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div wire:ignore.self class="modal fade" id="packagingModal" tabindex="-1" aria-labelledby="packagingModal"
                aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="packagingModal">LISTAR EMPAQUES</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                <div>
                                    <div class="position-relative">
                                        <input type="text" class="form-control ps-5" wire:model.live="searchPackaging"
                                            placeholder="Buscar..." maxlength="20"
                                            style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; border-radius: 10px;">
                                        <span class="position-absolute product-show translate-middle-y"
                                            style="top: 55%;"><i class="bx bx-search-alt"></i></span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>DESCRIPCIÓN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($list_packagings as $index => $item)
                                            <tr wire:click="addPackagingToProduct({{ $item->id }})"
                                                style="cursor: pointer;">
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $item->name ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">No hay empaques registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                                wire:click="resetPackaging">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div wire:ignore.self class="modal fade" id="productUnitModal" tabindex="-1"
                aria-labelledby="productUnitModal" aria-hidden="true" data-bs-keyboard="false"
                data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="productUnitModal">LISTAR UNIDADES</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                <div>
                                    <div class="position-relative">
                                        <input type="text" class="form-control ps-5" wire:model.live="searchProductUnit"
                                            placeholder="Buscar..." maxlength="20"
                                            style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; border-radius: 10px;">
                                        <span class="position-absolute product-show translate-middle-y"
                                            style="top: 55%;"><i class="bx bx-search-alt"></i></span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>UNIDAD</th>
                                            <th>UNIDAD MEDIDA</th>
                                            <th>FACTOR</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($list_product_units as $index => $item)
                                            <tr wire:click="addUnitToProduct({{ $item->id }})" style="cursor: pointer;">
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $item->name ?? '-' }}</td>
                                                <td>{{ $item->base_unit ?? '-' }}</td>
                                                <td>{{ $item->factor ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">No hay unidades registradas.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                                wire:click="resetProductUnitSearch">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div wire:ignore.self class="modal fade" id="comboProductModal" tabindex="-1"
                aria-labelledby="comboProductModal" aria-hidden="true" data-bs-keyboard="false"
                data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="comboProductModal">SELECCIONE PRODUCTO PARA COMBO</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                <div>
                                    <div class="position-relative">
                                        <input type="text" class="form-control ps-5"
                                            wire:model.live="searchComboProduct"
                                            placeholder="Buscar producto, servicio, receta..." maxlength="20"
                                            style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; border-radius: 10px;">
                                        <span class="position-absolute product-show translate-middle-y"
                                            style="top: 55%;"><i class="bx bx-search-alt"></i></span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>DESCRIPCIÓN</th>
                                            <th>TIPO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($list_combo_products as $index => $item)
                                            <tr wire:click="addComboProduct({{ $item->id }})" style="cursor: pointer;">
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $item->name ?? '-' }}</td>
                                                <td>
                                                    @if($item->type == 0) Producto @elseif($item->type == 1) Servicio @else
                                                    Receta @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">No hay productos disponibles.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                                wire:click="resetComboSearch">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div wire:ignore.self class="modal fade" id="variantModal" tabindex="-1" aria-labelledby="variantModal"
                aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="variantModal">
                                {{ $isVariantMode ? 'LISTAR VARIANTES' : 'REGISTRAR VARIANTES' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                @if ($isVariantMode)
                                    <div>
                                        <div class="position-relative">
                                            <input type="text" class="form-control ps-5" wire:model.live="searchVariant"
                                                placeholder="Buscar..." maxlength="20"
                                                style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; border-radius: 10px;">
                                            <span class="position-absolute product-show translate-middle-y"
                                                style="top: 55%;"><i class="bx bx-search-alt"></i></span>
                                        </div>
                                    </div>
                                @endif
                                <button wire:click="toggleVariantMode"
                                    class="btn {{ $isVariantMode ? 'btn-secondary' : 'btn-danger' }} btnIcon">
                                    <i class="bx {{ $isVariantMode ? 'bx-plus-circle' : 'bx-arrow-back' }}"></i>
                                    {{ $isVariantMode ? 'NUEVO' : 'ATRÁS' }}
                                </button>
                            </div>
                            <hr>
                            @if ($isVariantMode)
                                <div class="table-responsive">
                                    <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>DESCRIPCIÓN</th>
                                                <th>PRECIO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($list_variants as $index => $item)
                                                <tr wire:click="addVariantToProduct({{ $item->id }})" style="cursor: pointer;">
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $item->name ?? '-' }}</td>
                                                    <td>{{ number_format($item->price ?? 0, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center">No hay variantes registradas.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="row mb-2 p-2">
                                    <div class="col-lg-12 col-sm-6 mb-2">
                                        <label>Nombre</label>
                                        <input type="text" wire:model.lazy="name_variant" class="form-control"
                                            placeholder="Nombre" maxlength="30">
                                        @error('name_variant') <span class="text-danger er">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-lg-12 col-sm-6 mb-2">
                                        <label>Precio</label>
                                        <div class="position-relative input-icon">
                                            <input type="text" class="form-control text-end" wire:model="price_variant"
                                                placeholder="0.00" maxlength="10"
                                                oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                            <span class="position-absolute top-50 translate-middle-y">Bs</span>
                                        </div>
                                        @error('price_variant') <span class="text-danger er">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                                wire:click="resetInputRecipe">Cancelar</button>
                            @if (!$isVariantMode)
                                <button type="button" wire:click.prevent="storeVariant()" class="btn btn-primary"
                                    wire:loading.attr="disabled" wire:target="storeVariant">
                                    <span wire:loading.remove wire:target="storeVariant">Guardar</span>
                                    <span wire:loading wire:target="storeVariant"><i class="bx bx-spin bx-loader"></i>
                                        Procesando...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div wire:ignore.self class="modal fade" id="additionalModal" tabindex="-1"
                aria-labelledby="additionalModal" aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div
                            class="modal-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                            <h5 class="modal-title" id="additionalModal">
                                {{ $isAdditionalMode ? 'LISTAR ADICIONALES' : 'REGISTRAR ADICIONALES' }}
                            </h5>
                            <div><button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button></div>
                        </div>
                        <div class="modal-body">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                @if ($isAdditionalMode)
                                    <div>
                                        <div class="position-relative">
                                            <input type="text" class="form-control ps-5" wire:model.live="searchAdditional"
                                                placeholder="Buscar..." maxlength="20"
                                                style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; border-radius: 10px;">
                                            <span class="position-absolute product-show translate-middle-y"
                                                style="top: 55%;"><i class="bx bx-search-alt"></i></span>
                                        </div>
                                    </div>
                                @endif
                                <button wire:click="toggleAdditionalMode"
                                    class="btn {{ $isAdditionalMode ? 'btn-secondary' : 'btn-danger' }} btnIcon">
                                    <i class="bx {{ $isAdditionalMode ? 'bx-plus-circle' : 'bx-arrow-back' }}"></i>
                                    {{ $isAdditionalMode ? 'NUEVO' : 'ATRÁS' }}
                                </button>
                            </div>
                            <hr>
                            @if ($isAdditionalMode)
                                <div class="table-responsive">
                                    <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>N°</th>
                                                <th>DESCRIPCIÓN</th>
                                                <th>PRECIO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($list_additionals as $index => $item)
                                                <tr wire:click="addAdditionalToProduct({{ $item->id }})"
                                                    style="cursor: pointer;">
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $item->name ?? '-' }}</td>
                                                    <td>{{ number_format($item->price ?? 0, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center">No hay adicionales asignados.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="row mb-2 p-2">
                                    <div class="col-lg-12 col-sm-6 mb-2">
                                        <label>Nombre</label>
                                        <input type="text" wire:model.lazy="name_additional" class="form-control"
                                            placeholder="Nombre" maxlength="30">
                                        @error('name_additional') <span class="text-danger er">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-12 col-sm-6 mb-2">
                                        <label>Precio</label>
                                        <div class="position-relative input-icon">
                                            <input type="text" class="form-control text-end" wire:model="price"
                                                placeholder="0.00" maxlength="10"
                                                oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                            <span class="position-absolute top-50 translate-middle-y">Bs</span>
                                        </div>
                                        @error('price') <span class="text-danger er">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                                wire:click="resetAdditional">Cancelar</button>
                            @if (!$isAdditionalMode)
                                <button type="button" wire:click.prevent="storeAdditional()" class="btn btn-primary"
                                    wire:loading.attr="disabled" wire:target="storeAdditional">
                                    <span wire:loading.remove wire:target="storeAdditional">Guardar</span>
                                    <span wire:loading wire:target="storeAdditional"><i class="bx bx-spin bx-loader"></i>
                                        Procesando...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div wire:ignore.self class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">REGISTRAR CATEGORIA</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 p-2">
                                <div class="col-lg-12 col-sm-6 mb-2">
                                    <label>Nombre</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="name_category" class="form-control"
                                            placeholder="Nombre" maxlength="30">
                                    </div>
                                    @error('name_category') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                wire:click="resetInputCategoryBrandUnit">Cerrar</button>
                            <button type="button" wire:click.prevent="storeCategory()" class="btn btn-danger"
                                wire:loading.attr="disabled" wire:target="storeCategory">
                                <span wire:loading.remove wire:target="storeCategory">Guardar</span>
                                <span wire:loading wire:target="storeCategory"><i class="bx bx-spin bx-loader"></i>
                                    Procesando...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div wire:ignore.self class="modal fade" id="brandModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">REGISTRAR MARCA</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 p-2">
                                <div class="col-lg-12 col-sm-6 mb-2">
                                    <label>Nombre</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="name_brand" class="form-control"
                                            placeholder="Nombre" maxlength="30">
                                    </div>
                                    @error('name_brand') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                wire:click="resetInputCategoryBrandUnit">Cerrar</button>
                            <button type="button" wire:click.prevent="storeBrand()" class="btn btn-danger"
                                wire:loading.attr="disabled" wire:target="storeBrand">
                                <span wire:loading.remove wire:target="storeBrand">Guardar</span>
                                <span wire:loading wire:target="storeBrand"><i class="bx bx-spin bx-loader"></i>
                                    Procesando...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript"
    src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    function scrollGallery(scrollOffset) {
        const container = document.getElementById('galleryScrollArea');
        if (container) container.scrollLeft += scrollOffset;
    }

    function handleClickGallery(existingSavedCount) {
        const currentNewCount = document.getElementById('new-gallery-display').childElementCount;
        const total = existingSavedCount + currentNewCount;
        if (total >= 3) {
            Swal.fire({ icon: "info", title: "Galería completa", text: "El límite es de 3 imágenes en la galería (+ 1 portada aparte).", confirmButtonColor: "#3085d6", confirmButtonText: "Entendido" });
        } else {
            document.getElementById('galleryInput').click();
        }
    }

    function reindexGallery() {
        const container = document.getElementById('new-gallery-display');
        if (container) {
            Array.from(container.children).forEach((child, newIndex) => {
                const btn = child.querySelector('.btn-remove-gallery');
                if (btn) btn.setAttribute('onclick', `removeNewImage(${newIndex})`);
            });
        }
    }

    function removeNewImage(index) {
        @this.call('removeNewGalleryImage', index);
        const container = document.getElementById('new-gallery-display');
        if (container && container.children[index]) {
            container.children[index].remove();
            reindexGallery();
        }
    }

    function confirmDelete(id, action) {
        Swal.fire({
            title: action === 'delete' ? "¿Está seguro de eliminar?" : "¿Está seguro de restaurar?",
            text: action === 'delete' ? "El registro no se eliminará de forma permanente, solo cambiará el estado!" : "El registro será restaurado, cambiando su estado a activo!",
            icon: "warning", showCancelButton: true, confirmButtonColor: "#3085d6", cancelButtonColor: "#d33",
            confirmButtonText: action === 'delete' ? "Si, Eliminar!" : "Si, Restaurar!",
        }).then((result) => { if (result.isConfirmed) @this.call('delete', id); });
    }

    function openCategoryModal() {
        var myModal = new bootstrap.Modal(document.getElementById('categoryModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }
    function openBrandModal() {
        var myModal = new bootstrap.Modal(document.getElementById('brandModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }
    function openUnitModal() {
        var myModal = new bootstrap.Modal(document.getElementById('unitModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }
    function openVariantModal() {
        var myModal = new bootstrap.Modal(document.getElementById('variantModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }
    function openAdditionalModal() {
        var myModal = new bootstrap.Modal(document.getElementById('additionalModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }
    function openIngredientModal() {
        var myModal = new bootstrap.Modal(document.getElementById('ingredientModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }
    function openPackagingModal() {
        var myModal = new bootstrap.Modal(document.getElementById('packagingModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }
    function openProductUnitModal() {
        var myModal = new bootstrap.Modal(document.getElementById('productUnitModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }
    function openComboModal() {
        var myModal = new bootstrap.Modal(document.getElementById('comboProductModal'));
        myModal.show();
        setTimeout(() => { const b = document.querySelectorAll('.modal-backdrop'); if (b.length > 1) b[1].classList.add('second-backdrop'); }, 100);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const imageInput = document.getElementById('imageInput');
        const galleryInput = document.getElementById('galleryInput');
        const uploadOverlay = document.getElementById('uploadOverlay');
        const progressFill = document.getElementById('progressFill');
        const progressPercent = document.getElementById('progressPercent');
        const uploadText = document.getElementById('uploadText');
        const saveBtn = document.getElementById('saveBtn');
        const selectImageBtn = document.getElementById('selectImageBtn');
        const dropZone = document.getElementById('dropZone');
        const galleryUploadLoading = document.getElementById('galleryUploadLoading');
        const galleryUploadPlaceholder = document.getElementById('galleryUploadPlaceholder');
        const newGalleryDisplay = document.getElementById('new-gallery-display');
        let isUploading = false;

        function showUploadLoader() {
            isUploading = true;
            uploadOverlay.style.display = 'flex';
            if (saveBtn) { saveBtn.disabled = true; saveBtn.classList.add('disabled'); }
            if (selectImageBtn) { selectImageBtn.disabled = true; selectImageBtn.classList.add('disabled'); }
        }
        function hideUploadLoader() {
            isUploading = false;
            uploadOverlay.style.display = 'none';
            if (saveBtn) { saveBtn.disabled = false; saveBtn.classList.remove('disabled'); }
            if (selectImageBtn) { selectImageBtn.disabled = false; selectImageBtn.classList.remove('disabled'); }
            progressFill.style.width = '0%';
            progressPercent.textContent = '0%';
        }
        function updateProgress(percent) {
            progressFill.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
        }

        if (imageInput) {
            imageInput.addEventListener('change', async function (e) {
                const file = e.target.files[0];
                const preview = document.getElementById('previewImage');
                if (file) {
                    showUploadLoader();
                    const reader = new FileReader();
                    reader.onload = function (e) { preview.src = e.target.result; };
                    reader.readAsDataURL(file);
                    uploadText.textContent = "Optimizando a WebP...";
                    let fileToUpload = file;
                    const options = { maxSizeMB: 5, maxWidthOrHeight: 1920, useWebWorker: true, fileType: 'image/webp', initialQuality: 1.0 };
                    try {
                        const compressedBlob = await imageCompression(file, options);
                        fileToUpload = new File([compressedBlob], file.name.replace(/\.[^/.]+$/, ".webp"), { type: 'image/webp', lastModified: new Date().getTime() });
                    } catch (error) { console.warn(error); }
                    uploadText.textContent = "Subiendo al servidor...";
                    @this.upload('image', fileToUpload,
                        (uploadedFilename) => { uploadText.textContent = "¡Listo!"; updateProgress(100); setTimeout(() => { hideUploadLoader(); }, 500); },
                        (error) => { uploadText.textContent = "Error"; hideUploadLoader(); alert('Error al subir. Intente de nuevo.'); },
                        (event) => { const progress = Math.round(event.detail.progress); updateProgress(progress); uploadText.textContent = `Cargando... ${progress}%`; }
                    );
                }
            });
        }

        async function processGalleryFiles(files) {
            if (files.length > 0) {
                const savedInDb = {{ count($saved_gallery) }};
                const currentInDom = document.getElementById('new-gallery-display').childElementCount;
                const totalExisting = savedInDb + currentInDom;
                if ((totalExisting + files.length) > 3) {
                    Swal.fire({ icon: "error", title: "Límite Excedido", text: "Solo puedes tener un máximo de 3 imágenes en la galería (+ 1 portada aparte).", confirmButtonColor: "#dc3545" });
                    if (galleryInput) galleryInput.value = '';
                    return;
                }
                if (saveBtn) saveBtn.disabled = true;
                if (galleryUploadLoading) galleryUploadLoading.style.display = 'block';
                if (galleryUploadPlaceholder) galleryUploadPlaceholder.style.display = 'none';
                const options = { maxSizeMB: 5, maxWidthOrHeight: 1920, useWebWorker: true, fileType: 'image/webp', initialQuality: 1.0 };
                const compressedFiles = [];
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const div = document.createElement('div');
                        div.className = 'gallery-item-box border-warning';
                        div.innerHTML = `<img src="${e.target.result}" alt="new-img"><button type="button" class="btn-remove-gallery"><i class="bx bx-x"></i></button>`;
                        newGalleryDisplay.appendChild(div);
                        reindexGallery();
                    };
                    reader.readAsDataURL(file);
                    try {
                        const compressedBlob = await imageCompression(file, options);
                        const newFile = new File([compressedBlob], file.name.replace(/\.[^/.]+$/, ".webp"), { type: 'image/webp', lastModified: new Date().getTime() });
                        compressedFiles.push(newFile);
                    } catch (error) { compressedFiles.push(file); }
                }
                @this.uploadMultiple('temp_gallery', compressedFiles,
                    (uploadedFilename) => { if (saveBtn) saveBtn.disabled = false; if (galleryUploadLoading) galleryUploadLoading.style.display = 'none'; if (galleryUploadPlaceholder) galleryUploadPlaceholder.style.display = 'block'; },
                    (error) => { alert('Error al subir galería.'); if (saveBtn) saveBtn.disabled = false; if (galleryUploadLoading) galleryUploadLoading.style.display = 'none'; if (galleryUploadPlaceholder) galleryUploadPlaceholder.style.display = 'block'; newGalleryDisplay.innerHTML = ''; },
                    (event) => { }
                );
            }
        }

        if (galleryInput) {
            galleryInput.addEventListener('change', async function (e) {
                const files = Array.from(e.target.files);
                await processGalleryFiles(files);
            });
        }

        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => { dropZone.addEventListener(eventName, preventDefaults, false); });
            function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
            ['dragenter', 'dragover'].forEach(eventName => { dropZone.addEventListener(eventName, highlight, false); });
            ['dragleave', 'drop'].forEach(eventName => { dropZone.addEventListener(eventName, unhighlight, false); });
            function highlight(e) { dropZone.classList.add('bg-warning'); dropZone.style.borderColor = '#ffc107'; }
            function unhighlight(e) { dropZone.classList.remove('bg-warning'); dropZone.style.borderColor = '#dc3545'; }
            dropZone.addEventListener('drop', async function (e) {
                const dt = e.dataTransfer;
                const files = Array.from(dt.files);
                const imageFiles = files.filter(file => file.type.startsWith('image/'));
                if (imageFiles.length === 0) { Swal.fire('Atención', 'Solo se permiten archivos de imagen.', 'warning'); return; }
                await processGalleryFiles(imageFiles);
            }, false);
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', function (e) {
                if (isUploading) { e.preventDefault(); e.stopPropagation(); alert('Por favor espera a que termine de subir la imagen.'); return false; }
            });
        }
    });

    document.addEventListener('livewire:init', function () {
        let html5QrcodeScanner = null;
        let isScanning = false;

        Livewire.on('load-image-preview', (data) => {
            const preview = document.getElementById('previewImage');
            const galleryDisplay = document.getElementById('new-gallery-display');
            if (galleryDisplay) galleryDisplay.innerHTML = '';
            if (preview) {
                const imageUrl = data[0]?.image;
                preview.src = imageUrl ? imageUrl : '{{ asset('assets/images/product.png') }}';
            }
        });

        Livewire.on('reset-image-preview', () => {
            const preview = document.getElementById('previewImage');
            const input = document.getElementById('imageInput');
            const uploadOverlay = document.getElementById('uploadOverlay');
            const galleryDisplay = document.getElementById('new-gallery-display');
            const galleryInput = document.getElementById('galleryInput');
            if (galleryInput) galleryInput.value = '';
            if (preview) preview.src = '{{ asset('assets/images/product.png') }}';
            if (input) input.value = '';
            if (uploadOverlay) uploadOverlay.style.display = 'none';
            if (galleryDisplay) galleryDisplay.innerHTML = '';
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.classList.remove('disabled'); }
        });

        Livewire.on('alert', (data) => {
            const [msg, type, mg] = data;
            toast(msg, type);
            if (mg === 'category') $('#categoryModal').modal('hide');
            else if (mg === 'brand') $('#brandModal').modal('hide');
            else if (mg === 'unit') $('#unitModal').modal('hide');
            else if (mg === 'ingredient') $('#ingredientModal').modal('hide');
            else if (mg === 'packaging') $('#packagingModal').modal('hide');
            else if (mg === 'productUnit') $('#productUnitModal').modal('hide');
            else if (mg === 'combo') $('#comboProductModal').modal('hide');
        });

        Livewire.on('productStoreOrUpdate', (Msg, type) => {
            if (isScanning && html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => { return html5QrcodeScanner.clear(); }).then(() => {
                    if (document.getElementById('qr-reader')) document.getElementById('qr-reader').style.display = 'none';
                    if (document.getElementById('startScanBtn')) document.getElementById('startScanBtn').style.display = 'inline-block';
                    if (document.getElementById('stopScanBtn')) document.getElementById('stopScanBtn').style.display = 'none';
                    html5QrcodeScanner = null; isScanning = false;
                }).catch(() => { html5QrcodeScanner = null; isScanning = false; });
            }
            $('#theModal').modal('hide');
            toast(Msg, 'success');
        });

        Livewire.on('productDeleted', (Msg, type) => { toast(Msg, 'success') });

        const btnStart = document.getElementById('startScanBtn');
        if (btnStart) btnStart.addEventListener('click', function () { if (!isScanning) setTimeout(() => { startScanner(); }, 100); });
        const btnStop = document.getElementById('stopScanBtn');
        if (btnStop) btnStop.addEventListener('click', function () { if (isScanning) stopScanner(); });

        function startScanner() {
            if (html5QrcodeScanner) { try { html5QrcodeScanner.clear().catch(() => { }); } catch (e) { } html5QrcodeScanner = null; }
            const qrReaderElement = document.getElementById('qr-reader');
            qrReaderElement.innerHTML = '';
            qrReaderElement.style.display = 'block';
            document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-info">Iniciando cámara HD... Apunta al código de barras</div>`;
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            const config = {
                fps: 30,
                qrbox: function (viewfinderWidth, viewfinderHeight) {
                    let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                    let qrboxSize = Math.floor(minEdgeSize * 0.7);
                    return { width: Math.min(qrboxSize * 1.5, viewfinderWidth * 0.9), height: Math.min(qrboxSize * 0.6, viewfinderHeight * 0.5) };
                },
                aspectRatio: 2.5,
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                rememberLastUsedCamera: true, showTorchButtonIfSupported: true,
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE, Html5QrcodeSupportedFormats.AZTEC, Html5QrcodeSupportedFormats.CODABAR, Html5QrcodeSupportedFormats.CODE_39, Html5QrcodeSupportedFormats.CODE_93, Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.DATA_MATRIX, Html5QrcodeSupportedFormats.MAXICODE, Html5QrcodeSupportedFormats.ITF, Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8, Html5QrcodeSupportedFormats.PDF_417, Html5QrcodeSupportedFormats.RSS_14, Html5QrcodeSupportedFormats.RSS_EXPANDED, Html5QrcodeSupportedFormats.UPC_A, Html5QrcodeSupportedFormats.UPC_E, Html5QrcodeSupportedFormats.UPC_EAN_EXTENSION]
            };
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    let cameraId = devices[0].id;
                    if (devices.length > 1) {
                        const backCamera = devices.find(device => device.label.toLowerCase().includes('back') || device.label.toLowerCase().includes('rear') || device.label.toLowerCase().includes('environment'));
                        if (backCamera) cameraId = backCamera.id;
                    }
                    html5QrcodeScanner.start(cameraId, config, onScanSuccess, onScanFailure).then(() => {
                        document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-success">📱 Cámara HD activa - Listo para escanear códigos de barras</div>`;
                        isScanning = true;
                    }).catch(err => { fallbackStartUltraHD(); });
                } else { fallbackStartUltraHD(); }
            }).catch(err => { fallbackStartUltraHD(); });
            if (document.getElementById('startScanBtn')) document.getElementById('startScanBtn').style.display = 'none';
            if (document.getElementById('stopScanBtn')) document.getElementById('stopScanBtn').style.display = 'inline-block';
        }

        function fallbackStartUltraHD() {
            const videoConstraintsUltraHD = { facingMode: "environment", width: { ideal: 4096, min: 1920 }, height: { ideal: 2160, min: 1080 }, frameRate: { ideal: 60, min: 30 }, focusMode: "continuous", exposureMode: "continuous", whiteBalanceMode: "continuous", zoom: { ideal: 1.0, min: 1.0, max: 3.0 }, torch: false };
            html5QrcodeScanner.start(videoConstraintsUltraHD, { fps: 60, qrbox: function (vw, vh) { return { width: Math.min(400, vw * 0.9), height: Math.min(250, vh * 0.6) }; }, aspectRatio: 2.2, experimentalFeatures: { useBarCodeDetectorIfSupported: true }, formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE, Html5QrcodeSupportedFormats.AZTEC, Html5QrcodeSupportedFormats.CODABAR, Html5QrcodeSupportedFormats.CODE_39, Html5QrcodeSupportedFormats.CODE_93, Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.DATA_MATRIX, Html5QrcodeSupportedFormats.MAXICODE, Html5QrcodeSupportedFormats.ITF, Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8, Html5QrcodeSupportedFormats.PDF_417, Html5QrcodeSupportedFormats.RSS_14, Html5QrcodeSupportedFormats.RSS_EXPANDED, Html5QrcodeSupportedFormats.UPC_A, Html5QrcodeSupportedFormats.UPC_E, Html5QrcodeSupportedFormats.UPC_EAN_EXTENSION] }, onScanSuccess, onScanFailure
            ).then(() => { isScanning = true; document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-success">🚀 Ultra HD activo - Escaneando códigos de barras</div>`; }
            ).catch((err) => {
                const videoConstraintsHD = { facingMode: "environment", width: { ideal: 1920, min: 1280 }, height: { ideal: 1080, min: 720 }, frameRate: { ideal: 30, min: 20 }, focusMode: "continuous", exposureMode: "continuous" };
                html5QrcodeScanner.start(videoConstraintsHD, { fps: 30, qrbox: function (vw, vh) { return { width: Math.min(350, vw * 0.85), height: Math.min(200, vh * 0.55) }; }, aspectRatio: 2.0, formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE, Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.CODE_39, Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8, Html5QrcodeSupportedFormats.UPC_A, Html5QrcodeSupportedFormats.UPC_E] }, onScanSuccess, onScanFailure
                ).then(() => { isScanning = true; document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-success">📹 Full HD activo - Escaneando códigos</div>`; }
                ).catch((finalErr) => {
                    document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-danger">❌ Error al acceder a la cámara: ${finalErr}</div>`;
                    if (document.getElementById('startScanBtn')) document.getElementById('startScanBtn').style.display = 'inline-block';
                    if (document.getElementById('stopScanBtn')) document.getElementById('stopScanBtn').style.display = 'none';
                    isScanning = false;
                });
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById('codeInput').value = decodedText;
            @this.set('code', decodedText);
            const formatName = decodedResult.result?.format?.formatName || 'Desconocido';
            let formatIcon = formatName.toUpperCase() === 'QR_CODE' ? '📱' : formatName.toUpperCase() === 'CODE_128' ? '📊' : '🔍';
            document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-success"><strong>${formatIcon} ¡Código detectado exitosamente!</strong><br><strong>Código:</strong> ${decodedText}<br><strong>Formato:</strong> ${formatName}<br><small class="text-muted">✅ Guardado automáticamente</small></div>`;
            const qrReaderElement = document.getElementById('qr-reader');
            if (qrReaderElement) qrReaderElement.style.border = '3px solid #28a745';
            setTimeout(() => { if (qrReaderElement) qrReaderElement.style.border = ''; }, 1000);
            setTimeout(() => { stopScanner(); }, 2000);
        }

        function onScanFailure(error) { }

        function stopScanner() {
            if (html5QrcodeScanner && isScanning) {
                html5QrcodeScanner.stop().then(() => { return html5QrcodeScanner.clear(); }).then(() => {
                    html5QrcodeScanner = null; isScanning = false;
                    const qrReaderElement = document.getElementById('qr-reader');
                    if (qrReaderElement) { qrReaderElement.innerHTML = ''; qrReaderElement.style.display = 'none'; qrReaderElement.style.border = ''; }
                    if (document.getElementById('startScanBtn')) document.getElementById('startScanBtn').style.display = 'inline-block';
                    if (document.getElementById('stopScanBtn')) document.getElementById('stopScanBtn').style.display = 'none';
                    if (document.getElementById('qr-reader-results')) document.getElementById('qr-reader-results').innerHTML = `<div class="alert alert-info">📴 Cámara desactivada</div>`;
                    setTimeout(() => { if (document.getElementById('qr-reader-results')) document.getElementById('qr-reader-results').innerHTML = ''; }, 3000);
                }).catch((err) => {
                    html5QrcodeScanner = null; isScanning = false;
                    const qrReaderElement = document.getElementById('qr-reader');
                    if (qrReaderElement) { qrReaderElement.innerHTML = ''; qrReaderElement.style.display = 'none'; qrReaderElement.style.border = ''; }
                    if (document.getElementById('startScanBtn')) document.getElementById('startScanBtn').style.display = 'inline-block';
                    if (document.getElementById('stopScanBtn')) document.getElementById('stopScanBtn').style.display = 'none';
                });
            } else {
                const qrReaderElement = document.getElementById('qr-reader');
                if (qrReaderElement) { qrReaderElement.innerHTML = ''; qrReaderElement.style.display = 'none'; qrReaderElement.style.border = ''; }
                if (document.getElementById('startScanBtn')) document.getElementById('startScanBtn').style.display = 'inline-block';
                if (document.getElementById('stopScanBtn')) document.getElementById('stopScanBtn').style.display = 'none';
                html5QrcodeScanner = null; isScanning = false;
                if (document.getElementById('qr-reader-results')) document.getElementById('qr-reader-results').innerHTML = '';
            }
        }

        $('#theModal').on('hidden.bs.modal', function () { if (isScanning) stopScanner(); });
        $('#theModal').on('hide.bs.modal', function (e) { if (isScanning) { } });
    });
</script>