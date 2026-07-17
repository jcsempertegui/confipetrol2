@push('title', 'Categorías')

<div class="page-content">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Categorías y atributos</h4>
            <p class="text-muted mb-0">Primero crea una categoría y después define los datos que solicitarán sus productos.</p>
        </div>
        <span class="module-counter"><i class="bx bx-category me-1"></i>{{ $categories->count() }} categorías</span>
    </div>

    @canany(['crear-categoria', 'editar-categoria'])
        <div class="card module-form-card">
            <div class="card-header">
                <div>
                    <strong><span class="badge rounded-pill bg-primary me-2">1</span>{{ $categoryId ? 'Editar categoría' : 'Crear categoría' }}</strong>
                    <div class="form-card-subtitle">Registra la clasificación principal de tus productos.</div>
                </div>
            </div>
            <div class="card-body">
                <form wire:submit="saveCategory">
                    <div class="form-section-title">Información de la categoría</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="category-name" class="form-label">Nombre de la categoría <span class="text-danger">*</span></label>
                            <input id="category-name" wire:model="name" maxlength="150" placeholder="Ej.: Activo" class="form-control @error('name') is-invalid @enderror">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label for="category-code" class="form-label">Código corto <span class="field-optional">Opcional</span></label>
                            <input id="category-code" wire:model="code" maxlength="50" placeholder="Automático: ACT" class="form-control text-uppercase @error('code') is-invalid @enderror">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label for="category-description" class="form-label">Descripción <span class="field-optional">Opcional</span></label>
                            <input id="category-description" wire:model="description" maxlength="1000" placeholder="Describe brevemente qué productos agrupa" class="form-control @error('description') is-invalid @enderror">
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-actions">
                        @if($categoryId)
                            <button type="button" wire:click="resetCategory" class="btn btn-outline-secondary">Cancelar</button>
                        @endif
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveCategory">
                            <span wire:loading.remove wire:target="saveCategory"><i class="bx bx-save me-1"></i>{{ $categoryId ? 'Guardar cambios' : 'Crear categoría' }}</span>
                            <span wire:loading wire:target="saveCategory"><i class="bx bx-loader-alt bx-spin me-1"></i>Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcanany

    <div class="card module-list-card">
        <div class="card-header">
            <div>
                <strong>Categorías registradas</strong>
                <div class="form-card-subtitle">Selecciona una categoría para gestionar sus atributos.</div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-with-actions">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Código</th>
                            <th>Productos</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr wire:key="category-row-{{ $category->id }}">
                                <td>
                                    <strong>{{ $category->name }}</strong>
                                    @if($selectedCategoryId == $category->id)
                                        <span class="badge bg-primary ms-1">Seleccionada</span>
                                    @endif
                                    <div class="small text-muted">{{ $category->description ?: 'Sin descripción' }}</div>
                                </td>
                                <td class="font-monospace">{{ $category->code }}</td>
                                <td>{{ $category->products_count }}</td>
                                <td><span class="badge bg-{{ $category->status ? 'success' : 'secondary' }}">{{ $category->status ? 'Activa' : 'Inactiva' }}</span></td>
                                <td class="text-end text-nowrap">
                                    <button type="button" wire:click="selectCategory({{ $category->id }})" class="btn btn-sm {{ $selectedCategoryId == $category->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                        <i class="bx bx-list-check me-1"></i>Atributos
                                    </button>
                                    @can('editar-categoria')
                                        <button type="button" wire:click="editCategory({{ $category->id }})" class="btn btn-sm btn-outline-secondary" title="Editar categoría" aria-label="Editar categoría {{ $category->name }}">
                                            <i class="bx bx-edit"></i>
                                        </button>
                                    @endcan
                                    @can('eliminar-categoria')
                                        <button
                                            type="button"
                                            wire:click="toggleCategory({{ $category->id }})"
                                            wire:confirm="¿{{ $category->status ? 'Desactivar' : 'Activar' }} la categoría {{ $category->name }}?"
                                            class="btn btn-sm btn-outline-{{ $category->status ? 'danger' : 'success' }}"
                                            title="{{ $category->status ? 'Desactivar' : 'Activar' }} categoría"
                                            aria-label="{{ $category->status ? 'Desactivar' : 'Activar' }} categoría {{ $category->name }}"
                                        >
                                            <i class="bx bx-power-off"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">Crea tu primera categoría para continuar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($selectedCategory)
        <div class="card module-form-card">
            <div class="card-header">
                <div>
                    <strong><span class="badge rounded-pill bg-primary me-2">2</span>Atributos de “{{ $selectedCategory->name }}”</strong>
                    <div class="form-card-subtitle">Define qué información deberá completarse al registrar sus productos.</div>
                </div>
            </div>
            <div class="card-body">
                @can('gestionar-atributos')
                    <form wire:submit="saveAttribute">
                        <div class="form-section-title">{{ $attributeId ? 'Editar atributo' : 'Nuevo atributo' }}</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="attribute-name" class="form-label">Nombre del dato <span class="text-danger">*</span></label>
                                <input id="attribute-name" wire:model="attributeName" maxlength="150" placeholder="Ej.: Marca" class="form-control @error('attributeName') is-invalid @enderror @error('attributeCode') is-invalid @enderror">
                                @error('attributeName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @error('attributeCode')<div class="invalid-feedback d-block">No se pudo generar un código único: {{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="attribute-type" class="form-label">Tipo de respuesta <span class="text-danger">*</span></label>
                                <select id="attribute-type" wire:model.live="attributeType" class="form-select @error('attributeType') is-invalid @enderror">
                                    <option value="text">Texto</option>
                                    <option value="number">Número</option>
                                    <option value="select">Elegir de una lista</option>
                                    <option value="date">Fecha</option>
                                    <option value="boolean">Sí / No</option>
                                </select>
                                @error('attributeType')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="attribute-scope" class="form-label">¿Cómo se utiliza? <span class="text-danger">*</span></label>
                                <select id="attribute-scope" wire:model="attributeScope" class="form-select @error('attributeScope') is-invalid @enderror">
                                    <option value="product">Dato general del producto</option>
                                    <option value="variant">Variante, por ejemplo talla</option>
                                    <option value="unit">Identificador único, por ejemplo número de serie</option>
                                </select>
                                @error('attributeScope')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if($attributeType === 'select')
                                <div class="col-md-8">
                                    <label for="attribute-options" class="form-label">Opciones de la lista <span class="field-optional">Separadas por comas</span></label>
                                    <input id="attribute-options" wire:model="attributeOptions" maxlength="2000" placeholder="Ej.: 7, 8, 9, 10" class="form-control @error('attributeOptions') is-invalid @enderror">
                                    @error('attributeOptions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif

                            <div class="{{ $attributeType === 'select' ? 'col-md-4' : 'col-12' }} d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input id="attribute-required" wire:model="attributeRequired" type="checkbox" class="form-check-input">
                                    <label for="attribute-required" class="form-check-label">Solicitar este dato como obligatorio en productos</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            @if($attributeId)
                                <button type="button" wire:click="resetAttribute" class="btn btn-outline-secondary">Cancelar</button>
                            @endif
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveAttribute">
                                <span wire:loading.remove wire:target="saveAttribute"><i class="bx bx-save me-1"></i>{{ $attributeId ? 'Guardar cambios' : 'Agregar atributo' }}</span>
                                <span wire:loading wire:target="saveAttribute"><i class="bx bx-loader-alt bx-spin me-1"></i>Guardando...</span>
                            </button>
                        </div>
                    </form>
                @endcan

                <div class="form-section-title {{ auth()->user()->can('gestionar-atributos') ? 'mt-4 pt-3 border-top' : '' }}">Atributos configurados</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-with-actions">
                        <thead><tr><th>Atributo</th><th>Respuesta</th><th>Uso</th><th>Obligatorio</th><th class="text-end">Acciones</th></tr></thead>
                        <tbody>
                            @forelse($selectedCategory->attributes as $attribute)
                                <tr wire:key="category-attribute-{{ $selectedCategory->id }}-{{ $attribute->id }}">
                                    <td><strong>{{ $attribute->name }}</strong></td>
                                    <td>{{ ['text' => 'Texto', 'number' => 'Número', 'select' => 'Lista', 'date' => 'Fecha', 'boolean' => 'Sí / No'][$attribute->type] }}</td>
                                    <td>{{ match($attribute->scope) { 'variant' => 'Variante (talla o presentación)', 'unit' => 'Identificador único por unidad', default => 'Dato general del producto' } }}</td>
                                    <td><span class="badge bg-{{ $attribute->pivot->required ? 'primary' : 'light text-dark border' }}">{{ $attribute->pivot->required ? 'Sí' : 'No' }}</span></td>
                                    <td class="text-end text-nowrap">
                                        @can('gestionar-atributos')
                                            <button type="button" wire:click="editAttribute({{ $attribute->id }})" class="btn btn-sm btn-outline-primary" title="Editar atributo" aria-label="Editar atributo {{ $attribute->name }}"><i class="bx bx-edit"></i></button>
                                            <button type="button" wire:click="removeAttribute({{ $attribute->id }})" wire:confirm="¿Quitar este atributo de la categoría?" class="btn btn-sm btn-outline-danger" title="Quitar atributo" aria-label="Quitar atributo {{ $attribute->name }}"><i class="bx bx-trash"></i></button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Agrega Marca, Modelo o Número de serie, por ejemplo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bx bx-info-circle me-1"></i>Cuando termines, abre <a href="{{ route('products') }}" class="alert-link">Productos</a> para registrar tus productos.
                </div>
            </div>
        </div>
    @endif
</div>
