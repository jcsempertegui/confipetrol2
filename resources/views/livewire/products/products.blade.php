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
                                            <div class="badge rounded-pill text-primary bg-light-primary text-uppercase">Producto</div>
                                        @elseif ($product->type == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">Servicio</div>
                                        @elseif ($product->type == 2)
                                            <div class="badge rounded-pill text-info bg-light-info text-uppercase">Receta</div>
                                        @elseif ($product->type == 3)
                                            <div class="badge rounded-pill text-warning bg-light-warning text-uppercase">Insumo</div>
                                        @elseif ($product->type == 4)
                                            <div class="badge rounded-pill text-orange bg-light-orange text-uppercase">Empaque</div>
                                        @elseif ($product->type == 5)
                                            <div class="badge rounded-pill text-uppercase"
                                                style="background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;">Combo</div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">Desconocido</div>
                                        @endif
                                    </td>
                                    <td>{{ optional($product->inventories->first())->purchase_price ?: '0.00' }}</td>
                                    <td>{{ optional($product->inventories->first())->sale_price ?: '0.00' }}</td>
                                    <td>{{ $product->categories->name }}</td>
                                    <td>{{ $product->brands->name }}</td>
                                    <td>{{ $product->units->name ?? '-' }}</td>
                                    <td>
                                        @if ($product->status == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">ACTIVO</div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">INACTIVO</div>
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

            {{-- Modal Principal --}}
            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content"
                        x-data="{ type: @entangle('type'), tab: 'info' }">
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
                                                            placeholder="Escanee el código" maxlength="30"
                                                            id="codeInput">
                                                        <button type="button" class="btn btn-show btn-primary"
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
                                                    @error('code') <span class="text-danger er">{{ $message }}</span> @enderror
                                                    @if($camera_barcode_enabled == 1)
                                                        <div id="qr-reader"
                                                            style="width: 100%; display: none; margin-top: 10px;"></div>
                                                        <div id="qr-reader-results" style="margin-top: 10px;"></div>
                                                    @endif
                                                </div>

                                                <div class="col-md-12 col-sm-6 mb-2">
                                                    <label>Producto</label>
                                                    <div class="input-group">
                                                        <input type="text" wire:model.lazy="name" class="form-control"
                                                            placeholder="Producto" maxlength="80">
                                                    </div>
                                                    @error('name') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-12 col-sm-6 mb-2">
                                                    <label>Caracteristicas</label>
                                                    <div class="input-group">
                                                        <textarea id="editor" class="form-control" name="message"
                                                            rows="2" placeholder="Caracteristicas del Producto"
                                                            wire:model="features"></textarea>
                                                    </div>
                                                    @error('features') <span class="text-danger er">{{ $message }}</span> @enderror
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
                                                        <button type="button" class="btn btn-show btn-primary"
                                                            onclick="openCategoryModal()"><i class="bx bx-plus"></i></button>
                                                    </div>
                                                    @error('categorie_id') <span class="text-danger er">{{ $message }}</span> @enderror
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
                                                        <button type="button" class="btn btn-show btn-primary"
                                                            onclick="openBrandModal()"><i class="bx bx-plus"></i></button>
                                                    </div>
                                                    @error('brand_id') <span class="text-danger er">{{ $message }}</span> @enderror
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
                                                        <button type="button" class="btn btn-show btn-primary"
                                                            onclick="openUnitModal()"><i class="bx bx-plus"></i></button>
                                                    </div>
                                                    @error('unit_id') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-lg-6 col-sm-6" x-show="type != 5 && type != '5'">
                                                    <label>Existencia Minima</label>
                                                    <div class="position-relative input-icon">
                                                        <input type="text" class="form-control text-end"
                                                            wire:model="minimum_stock" placeholder="0" maxlength="3"
                                                            inputmode="decimal"
                                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                                        <span class="position-absolute top-50 translate-middle-y"><i
                                                                class="bx bx-box"></i></span>
                                                    </div>
                                                    @error('minimum_stock') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                @if(!$isEditMode)
                                                    <div class="col-lg-6 col-sm-6"
                                                        x-show="type == 0 || type == '0' || type == 3 || type == '3' || type == 4 || type == '4'">
                                                        <label>Stock Inicial</label>
                                                        <div class="position-relative input-icon">
                                                            <input type="text" class="form-control text-end"
                                                                wire:model="initial_stock" placeholder="0" maxlength="10"
                                                                inputmode="decimal"
                                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                            <span class="position-absolute top-50 translate-middle-y"><i
                                                                    class="bx bx-box"></i></span>
                                                        </div>
                                                        @error('initial_stock') <span class="text-danger er">{{ $message }}</span> @enderror
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
                                                            inputmode="decimal"
                                                            :readonly="type == 5 || type == '5'"
                                                            :class="(type == 5 || type == '5') ? 'bg-light text-muted' : ''">
                                                        <span class="position-absolute top-50 translate-middle-y">Bs</span>
                                                    </div>
                                                    @error('purchase_price') <span class="text-danger er">{{ $message }}</span> @enderror
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
                                                        <span class="position-absolute top-50 translate-middle-y">%</span>
                                                    </div>
                                                    @error('profit') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-4 col-sm-6 mb-2">
                                                    <label>Precio de Venta</label>
                                                    <div class="position-relative input-icon">
                                                        <input type="text" class="form-control text-end price-decimal"
                                                            id="sale_price" wire:model.lazy="sale_price"
                                                            placeholder="0.00" inputmode="decimal"
                                                            wire:change="calculatePurchasePrice">
                                                        <span class="position-absolute top-50 translate-middle-y">Bs</span>
                                                    </div>
                                                    @error('sale_price') <span class="text-danger er">{{ $message }}</span> @enderror
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
                                                                    style="width: 0%;"></div>
                                                            </div>
                                                            <small id="progressPercent" class="mt-1">0%</small>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <input type="file" id="imageInput" name="image"
                                                            class="form-control d-none" accept=".jpg,.jpeg,.png,.webp">
                                                        <button type="button" id="selectImageBtn"
                                                            class="btn btn-outline-primary mt-2"
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

                                            <div class="col-md-12 mt-3">
                                                <label for="type" class="form-label">Tipo de producto</label>
                                                <div class="d-flex flex-wrap">
                                                    <div class="form-check me-4">
                                                        <input class="form-check-input" type="radio" id="type-producto"
                                                            wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                            value="0">
                                                        <label class="form-check-label" for="type-producto">Es Producto?</label>
                                                    </div>
                                                    <div class="form-check me-4">
                                                        <input class="form-check-input" type="radio" id="type-servicio"
                                                            wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                            value="1">
                                                        <label class="form-check-label" for="type-servicio">Es Servicio?</label>
                                                    </div>
                                                    @if ($pos_type == 0 || $pos_type == 4)
                                                        <div class="form-check me-4">
                                                            <input class="form-check-input" type="radio" id="type-receta"
                                                                wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                                value="2">
                                                            <label class="form-check-label" for="type-receta">Es Receta?</label>
                                                        </div>
                                                        <div class="form-check me-4">
                                                            <input class="form-check-input" type="radio" id="type-insumo"
                                                                wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                                value="3">
                                                            <label class="form-check-label" for="type-insumo">Es Insumo?</label>
                                                        </div>
                                                        <div class="form-check me-4">
                                                            <input class="form-check-input" type="radio" id="type-empaque"
                                                                wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                                value="4">
                                                            <label class="form-check-label" for="type-empaque">Es Empaque?</label>
                                                        </div>
                                                    @endif
                                                    <div class="form-check me-4">
                                                        <input class="form-check-input" type="radio" id="type-combo"
                                                            wire:model.live="type" x-model="type" @change="tab = 'info'"
                                                            value="5">
                                                        <label class="form-check-label" for="type-combo">Es Combo?</label>
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
                                                                for="hasLoyaltySwitch">¿Activar Fidelización?</label>
                                                        </div>
                                                        @if($has_loyalty)
                                                            <div class="ms-3 d-flex align-items-center">
                                                                <label class="me-2 text-muted" style="font-size: 0.85rem;">Cantidad requerida</label>
                                                                <div style="width: 80px;">
                                                                    <input type="text"
                                                                        class="form-control form-control-sm text-center"
                                                                        wire:model="loyalty_req_qty" placeholder="5"
                                                                        maxlength="3"
                                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                                </div>
                                                            </div>
                                                            @error('loyalty_req_qty') <span class="text-danger er d-block w-100 mt-1">{{ $message }}</span> @enderror
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
                                                    <label class="form-check-label" for="flexSwitchCheckLot">Con Lote?</label>
                                                </div>
                                            </div>
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
                                <span wire:loading.remove wire:target="storeOrUpdate">{{ $isEditMode ? 'Actualizar' : 'Guardar' }}</span>
                                <span wire:loading wire:target="storeOrUpdate"><i class="bx bx-spin bx-loader"></i> Procesando...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @include('livewire.products.form')

        </div>
    </div>
</div>

<script type="text/javascript"
    src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
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

    document.addEventListener('DOMContentLoaded', function () {
        const imageInput = document.getElementById('imageInput');
        const uploadOverlay = document.getElementById('uploadOverlay');
        const progressFill = document.getElementById('progressFill');
        const progressPercent = document.getElementById('progressPercent');
        const uploadText = document.getElementById('uploadText');
        const saveBtn = document.getElementById('saveBtn');
        const selectImageBtn = document.getElementById('selectImageBtn');
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
            if (preview) {
                const imageUrl = data[0]?.image;
                preview.src = imageUrl ? imageUrl : '{{ asset('assets/images/product.png') }}';
            }
        });

        Livewire.on('reset-image-preview', () => {
            const preview = document.getElementById('previewImage');
            const input = document.getElementById('imageInput');
            const uploadOverlay = document.getElementById('uploadOverlay');
            if (preview) preview.src = '{{ asset('assets/images/product.png') }}';
            if (input) input.value = '';
            if (uploadOverlay) uploadOverlay.style.display = 'none';
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.classList.remove('disabled'); }
        });

        Livewire.on('alert', (data) => {
            const [msg, type, mg] = data;
            toast(msg, type);
            if (mg === 'category') $('#categoryModal').modal('hide');
            else if (mg === 'brand') $('#brandModal').modal('hide');
            else if (mg === 'unit') $('#unitModal').modal('hide');
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
    });
</script>
