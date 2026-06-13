@push('title', 'Productos')

<div class="page-content" style="height: calc(100vh - 60px); overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0;">

    <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-primary">Productos</li>
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

        <div class="card-body px-3" style="flex: 1; min-height: 0; overflow: hidden; display: flex; flex-direction: column;">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2" style="flex-shrink: 0;">
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
                                <option value="">FILTRO POR CATEGORÍA</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ mb_strtoupper($cat->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <select wire:model.live="filter_brand" class="form-select filter-pro-select">
                                <option value="">FILTRO POR MARCA</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ mb_strtoupper($brand->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <select wire:model.live="filter_type" class="form-select filter-pro-select">
                                <option value="">FILTRO POR TIPO</option>
                                <option value="1">ACTIVO</option>
                                <option value="2">CONSUMIBLES</option>
                                <option value="3">EPPS</option>
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
                            <th>CÓDIGO</th>
                            <th>NOMBRE</th>
                            <th>TIPO</th>
                            <th>CATEGORÍA</th>
                            <th>MARCA</th>
                            <th>UMD</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($products->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($products as $index => $product)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $product->code ?: 'S/N' }}</td>
                                    <td>{{ $product->name ?: 'S/N' }}</td>
                                    <td>
                                        @if ($product->type == 1)
                                            <div class="badge rounded-pill text-warning bg-light-warning text-uppercase">Activo</div>
                                        @elseif ($product->type == 2)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">Consumible</div>
                                        @elseif ($product->type == 3)
                                            <div class="badge rounded-pill text-uppercase" style="background-color: rgba(111,66,193,0.1); color:#6f42c1;">EPPS</div>
                                        @else
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">Consumible</div>
                                        @endif
                                    </td>
                                    <td>{{ $product->categories->name ?? '-' }}</td>
                                    <td>{{ $product->brands->name ?? '-' }}</td>
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
                                                <a href="javascript:;" wire:click="edit({{ $product->id }})"
                                                    data-bs-toggle="modal" data-bs-target="#theModal"
                                                    class="btn-action-primary"><i class="bx bxs-edit-alt"></i></a>
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
                <div class="modal-dialog modal-lg" role="document">
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
                                <li class="nav-item" role="presentation" x-show="type == 3 || type == '3'">
                                    <a @click.prevent="tab = 'tallas_colores'" class="nav-link"
                                        :class="tab === 'tallas_colores' ? 'active' : ''" href="javascript:;" role="tab">
                                        <div class="d-flex align-items-center">
                                            <div class="tab-icon"><i class='bx bx-purchase-tag-alt font-18 me-1'></i></div>
                                            <div class="tab-title">Tallas / Colores</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">

                                <div class="tab-pane fade" :class="tab === 'info' ? 'show active' : ''" role="tabpanel">
                                    <div class="row mb-2 p-2">

                                        <div class="col-md-8">
                                            <div class="row">
                                                <div class="col-md-8 col-sm-6 mb-2">
                                                    <label>Código</label>
                                                    <div class="input-group">
                                                        <input type="text" wire:model="code" class="form-control"
                                                            placeholder="Escanee o escriba el código" maxlength="30"
                                                            id="codeInput">
                                                        <button type="button" class="btn btn-show btn-primary"
                                                            wire:click="generateCode()">
                                                            <i class="bx bx-barcode-reader"></i>
                                                        </button>
                                                    </div>
                                                    @error('code') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-12 col-sm-6 mb-2">
                                                    <label>Nombre</label>
                                                    <input type="text" wire:model.lazy="name" class="form-control"
                                                        placeholder="Nombre del producto" maxlength="80">
                                                    @error('name') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-12 col-sm-6 mb-2">
                                                    <label>Descripción</label>
                                                    <textarea class="form-control" rows="2"
                                                        placeholder="Descripción / características" wire:model="features"></textarea>
                                                    @error('features') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6 col-sm-6 mb-2" x-show="type != 1 && type != '1'">
                                                    <label>Categoría</label>
                                                    <div class="input-group">
                                                        <select wire:model="categorie_id" class="form-select">
                                                            <option value="">Seleccionar</option>
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

                                                <div class="col-md-6 col-sm-6 mb-2" x-show="type == 1 || type == '1'" style="display:none;">
                                                    <label>Modelo</label>
                                                    <input type="text" wire:model.lazy="model" class="form-control"
                                                        placeholder="Modelo del activo" maxlength="100">
                                                    @error('model') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6 col-sm-6 mb-3">
                                                    <label>Marca</label>
                                                    <div class="input-group">
                                                        <select wire:model="brand_id" class="form-select">
                                                            <option value="">Seleccionar</option>
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

                                                <div class="col-md-6 col-sm-6 mb-3">
                                                    <label>Unidad de Medida</label>
                                                    <div class="input-group">
                                                        <select wire:model="unit_id" class="form-select">
                                                            <option value="">Seleccionar</option>
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

                                                <div class="col-lg-6 col-sm-6 mb-2">
                                                    <label>Existencia Mínima</label>
                                                    <div class="position-relative input-icon">
                                                        <input type="text" class="form-control text-end"
                                                            wire:model="minimum_stock" placeholder="0" maxlength="6"
                                                            inputmode="decimal"
                                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                                        <span class="position-absolute top-50 translate-middle-y">
                                                            <i class="bx bx-box"></i>
                                                        </span>
                                                    </div>
                                                    @error('minimum_stock') <span class="text-danger er">{{ $message }}</span> @enderror
                                                </div>

                                                @if(!$isEditMode)
                                                    <div class="col-lg-6 col-sm-6 mb-2"
                                                        x-show="type != 3 && type != '3'">
                                                        <label>Stock Inicial</label>
                                                        <div class="position-relative input-icon">
                                                            <input type="text" class="form-control text-end"
                                                                wire:model="initial_stock" placeholder="0" maxlength="10"
                                                                inputmode="decimal"
                                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                            <span class="position-absolute top-50 translate-middle-y">
                                                                <i class="bx bx-box"></i>
                                                            </span>
                                                        </div>
                                                        @error('initial_stock') <span class="text-danger er">{{ $message }}</span> @enderror
                                                    </div>
                                                @endif

                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="col-md-12 mt-2">
                                                <label class="form-label fw-bold">Tipo de producto</label>
                                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                                    <div class="form-check me-3">
                                                        <input class="form-check-input" type="radio" id="type-activo"
                                                            wire:model.live="type" x-model="type"
                                                            @change="tab = 'info'" value="1">
                                                        <label class="form-check-label" for="type-activo">Activo</label>
                                                    </div>
                                                    <div class="form-check me-3">
                                                        <input class="form-check-input" type="radio" id="type-consumibles"
                                                            wire:model.live="type" x-model="type"
                                                            @change="tab = 'info'" value="2">
                                                        <label class="form-check-label" for="type-consumibles">Consumibles</label>
                                                    </div>
                                                    <div class="form-check me-3">
                                                        <input class="form-check-input" type="radio" id="type-epps"
                                                            wire:model.live="type" x-model="type"
                                                            @change="tab = 'info'" value="3">
                                                        <label class="form-check-label" for="type-epps">EPPS</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" :class="tab === 'tallas_colores' ? 'show active' : ''"
                                    role="tabpanel">
                                    <div class="p-2">
                                        <div class="mb-3">
                                            <h5 class="card-title mb-0">Variantes EPPS (Talla y Color)</h5>
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
                                                    wire:click="addSkuToProduct()">
                                                    <i class="bx bx-plus-circle"></i> NUEVO
                                                </button>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-striped table-hover nowrap" style="width:100%;">
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
                                                                            wire:click="toggleCustomPrice({{ $index }})"
                                                                            {{ !empty($item['is_custom_price']) ? 'checked' : '' }}>
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
                                                                            value="0.00" readonly
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
                                                                        class="btn-action-danger">
                                                                        <i class="bx bxs-trash"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7" class="text-center">No hay combinaciones agregadas.</td>
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
                                class="btn btn-primary" wire:loading.attr="disabled" wire:target="storeOrUpdate">
                                <span wire:loading.remove wire:target="storeOrUpdate">
                                    {{ $isEditMode ? 'Actualizar' : 'Guardar' }}
                                </span>
                                <span wire:loading wire:target="storeOrUpdate">
                                    <i class="bx bx-spin bx-loader"></i> Procesando...
                                </span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            @include('livewire.products.form')

        </div>
    </div>
</div>

<script>
    function confirmDelete(id, action) {
        Swal.fire({
            title: action === 'delete' ? '¿Está seguro de eliminar?' : '¿Está seguro de restaurar?',
            text: action === 'delete'
                ? 'El registro no se eliminará de forma permanente, solo cambiará el estado.'
                : 'El registro será restaurado, cambiando su estado a activo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: action === 'delete' ? 'Sí, Eliminar!' : 'Sí, Restaurar!',
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

    document.addEventListener('livewire:init', function () {
        Livewire.on('alert', (data) => {
            const [msg, type, mg] = data;
            toast(msg, type);
            if (mg === 'category') $('#categoryModal').modal('hide');
            else if (mg === 'brand') $('#brandModal').modal('hide');
            else if (mg === 'unit')  $('#unitModal').modal('hide');
        });

        Livewire.on('productStoreOrUpdate', (Msg) => {
            $('#theModal').modal('hide');
            toast(Msg, 'success');
        });

        Livewire.on('productDeleted', (Msg) => { toast(Msg, 'success'); });
    });
</script>