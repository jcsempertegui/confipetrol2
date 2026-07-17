@push('title', 'Productos')

<div class="page-content">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Productos</h4>
            <p class="text-muted mb-0">Selecciona una categoría y completa los datos definidos para ella.</p>
        </div>
        <span class="module-counter"><i class="bx bx-package me-1"></i>{{ $products->total() }} registrados</span>
    </div>

    @canany(['crear-producto', 'editar-producto'])
        <div class="card module-form-card">
            <div class="card-header">
                <div>
                    <strong><i class="bx bx-package me-1"></i>{{ $productId ? 'Editar producto' : 'Nuevo producto' }}</strong>
                    <div class="form-card-subtitle">Los campos adicionales se mostrarán según la categoría seleccionada.</div>
                </div>
            </div>
            <div class="card-body">
                <form wire:submit="save">
                    <div class="form-section-title">Información general</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="product-category" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select id="product-category" wire:model.live="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">Selecciona una categoría...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="product-name" class="form-label">Nombre del producto <span class="text-danger">*</span></label>
                            <input id="product-name" wire:model="name" maxlength="200" placeholder="Ej.: Laptop Toshiba" class="form-control @error('name') is-invalid @enderror">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label for="product-unit" class="form-label">Unidad <span class="text-danger">*</span></label>
                            <input id="product-unit" wire:model="unit" list="product-units" maxlength="40" placeholder="Ej.: unidad" class="form-control @error('unit') is-invalid @enderror">
                            <datalist id="product-units">
                                <option value="unidad"><option value="par"><option value="caja"><option value="paquete"><option value="metro"><option value="kilogramo"><option value="litro"><option value="rollo"><option value="juego">
                            </datalist>
                            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label for="product-code" class="form-label">Código <span class="field-optional">Opcional</span></label>
                            <input id="product-code" wire:model="code" maxlength="80" placeholder="Automático" class="form-control text-uppercase @error('code') is-invalid @enderror">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @php($selectedCategory = $categories->firstWhere('id', (int) $category_id))
                        @if($selectedCategory)
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0">
                                    <i class="bx bx-barcode me-1"></i><strong>Codificación automática:</strong> si dejas el código vacío se generará <span class="font-monospace">{{ $codePreview }}</span>. Las variantes se completarán con su talla o presentación.
                                </div>
                            </div>

                            @php($productAttributes = $selectedCategory->attributes->where('scope', 'product'))
                            @if($productAttributes->isNotEmpty())
                                <div class="col-12"><div class="form-section-title mt-2 mb-0">Datos de {{ $selectedCategory->name }}</div></div>
                                @foreach($productAttributes as $attribute)
                                    <div class="col-md-4">
                                        <label class="form-label">{{ $attribute->name }} @if($attribute->pivot->required)<span class="text-danger">*</span>@else<span class="field-optional">Opcional</span>@endif</label>
                                        @include('livewire.products.attribute-input', ['model' => 'productValues.'.$attribute->id, 'attribute' => $attribute])
                                        @error('productValues.'.$attribute->id)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                @endforeach
                            @endif

                            @php($variantAttributes = $selectedCategory->attributes->where('scope', 'variant'))
                            @php($unitAttribute = $selectedCategory->attributes->firstWhere('scope', 'unit'))
                            <div class="col-12 mt-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top pt-3">
                                    <div>
                                        <div class="form-section-title mb-1">{{ $variantAttributes->isNotEmpty() ? 'Variantes del producto' : 'Identificación y control' }}</div>
                                        <div class="small text-muted">{{ $variantAttributes->isNotEmpty() ? 'Registra una fila por talla o presentación.' : ($unitAttribute ? 'Cada activo se controla mediante su identificador único.' : 'Configura el nivel mínimo de existencia.') }}</div>
                                    </div>
                                    @if($variantAttributes->isNotEmpty())
                                        <button type="button" wire:click="addVariant" class="btn btn-sm btn-outline-primary"><i class="bx bx-plus me-1"></i>Agregar variante</button>
                                    @endif
                                </div>

                                @error('variants')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                                @error('tracking_type')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror

                                <div class="document-items">
                                    @foreach($variants as $index => $variant)
                                        <div class="document-item" wire:key="product-variant-{{ $variant['id'] ?? 'new' }}-{{ $index }}">
                                            <div class="row g-3">
                                                @if($variantAttributes->isNotEmpty())
                                                    <div class="col-md-3">
                                                        <label for="variant-sku-{{ $index }}" class="form-label">Código / SKU <span class="field-optional">Opcional</span></label>
                                                        <input id="variant-sku-{{ $index }}" wire:model="variants.{{ $index }}.sku" maxlength="100" placeholder="Automático" class="form-control text-uppercase @error('variants.'.$index.'.sku') is-invalid @enderror">
                                                        @error('variants.'.$index.'.sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                @endif

                                                @unless($unitAttribute)
                                                    <div class="col-md-2">
                                                        <label for="variant-stock-{{ $index }}" class="form-label">Stock mínimo <span class="field-optional">Opcional</span></label>
                                                        <input id="variant-stock-{{ $index }}" type="number" min="0" max="999999999" step="0.001" wire:model="variants.{{ $index }}.minimum_stock" class="form-control @error('variants.'.$index.'.minimum_stock') is-invalid @enderror">
                                                        @error('variants.'.$index.'.minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                @endunless

                                                @foreach($variantAttributes as $attribute)
                                                    <div class="col-md-3">
                                                        <label class="form-label">{{ $attribute->name }} @if($attribute->pivot->required)<span class="text-danger">*</span>@else<span class="field-optional">Opcional</span>@endif</label>
                                                        @include('livewire.products.attribute-input', ['model' => 'variants.'.$index.'.values.'.$attribute->id, 'attribute' => $attribute])
                                                        @error('variants.'.$index.'.values.'.$attribute->id)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                    </div>
                                                @endforeach

                                                @if($unitAttribute)
                                                    <div class="col-12">
                                                        <label for="variant-serials-{{ $index }}" class="form-label">{{ $unitAttribute->name }} @if($unitAttribute->pivot->required)<span class="text-danger">*</span>@else<span class="field-optional">Opcional</span>@endif</label>
                                                        <textarea id="variant-serials-{{ $index }}" wire:model="variants.{{ $index }}.serials" rows="3" class="form-control @error('variants.'.$index.'.serials') is-invalid @enderror" placeholder="Uno por línea. Ej.: SN-001"></textarea>
                                                        <div class="form-text">Cada identificador debe ser único en todo el sistema.</div>
                                                        @error('variants.'.$index.'.serials')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </div>
                                                @endif

                                                @if($variantAttributes->isNotEmpty())
                                                    <div class="col-12 text-end">
                                                        <button type="button" wire:click="removeVariant({{ $index }})" class="btn btn-sm btn-outline-danger" @disabled(count($variants) === 1)>
                                                            <i class="bx bx-trash me-1"></i>Quitar variante
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="alert alert-light border mb-0"><i class="bx bx-info-circle me-1"></i>Selecciona una categoría para mostrar sus atributos y habilitar el registro.</div>
                            </div>
                        @endif
                    </div>

                    @if($selectedCategory)
                        <div class="form-actions">
                            @if($productId)
                                <button type="button" wire:click="resetForm" class="btn btn-outline-secondary">Cancelar</button>
                            @endif
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save"><i class="bx bx-save me-1"></i>{{ $productId ? 'Guardar cambios' : 'Guardar producto' }}</span>
                                <span wire:loading wire:target="save"><i class="bx bx-loader-alt bx-spin me-1"></i>Guardando...</span>
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @endcanany

    <div class="card module-list-card">
        <div class="card-header filter-header">
            <div class="row g-2 align-items-end w-100">
                <div class="col-md-6">
                    <div class="filter-title"><i class="bx bx-list-ul"></i>Productos registrados</div>
                </div>
                <div class="col-md-6">
                    <label for="product-search" class="filter-label">Buscar producto</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input id="product-search" type="search" wire:model.live.debounce.300ms="searchTerm" class="form-control" placeholder="Nombre, código, categoría o SKU" aria-label="Buscar productos">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-with-actions">
                    <thead><tr><th>Producto</th><th>Categoría</th><th>Datos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr wire:key="product-row-{{ $product->id }}">
                                <td><strong>{{ $product->name }}</strong><div class="small text-muted font-monospace">{{ $product->code }}</div></td>
                                <td>{{ $product->category->name }}</td>
                                <td>
                                    @foreach($product->attributeValues as $value)
                                        @if(filled($value->value))<span class="badge bg-light text-dark border me-1">{{ $value->value }}</span>@endif
                                    @endforeach
                                    @if($product->variants->where('status', true)->count() > 1)<span class="badge bg-info text-dark me-1">{{ $product->variants->where('status', true)->count() }} variantes</span>@endif
                                    @if($product->tracking_type === 'serialized')<span class="badge bg-primary">Serializado</span>@endif
                                </td>
                                <td><span class="badge bg-{{ $product->status ? 'success' : 'secondary' }}">{{ $product->status ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="text-end text-nowrap">
                                    @can('editar-producto')
                                        <button type="button" wire:click="edit({{ $product->id }})" class="btn btn-sm btn-outline-primary" title="Editar producto" aria-label="Editar producto {{ $product->name }}"><i class="bx bx-edit"></i></button>
                                    @endcan
                                    @can('eliminar-producto')
                                        <button type="button" wire:click="toggle({{ $product->id }})" class="btn btn-sm btn-outline-{{ $product->status ? 'danger' : 'success' }}" title="{{ $product->status ? 'Desactivar' : 'Activar' }} producto" aria-label="{{ $product->status ? 'Desactivar' : 'Activar' }} producto {{ $product->name }}"><i class="bx bx-power-off"></i></button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">No hay productos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
    </div>
</div>
