<!-- Modal de Categorias -->
<div wire:ignore.self class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="theModalLabel"
    aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="theModalLabel">
                    REGISTRAR CATEGORIA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-2 p-2">
                    <div class="col-lg-12 col-sm-6 mb-2">
                        <label>Nombre</label>
                        <div class="input-group">
                            <input type="text" wire:model.lazy="name_category" class="form-control" placeholder="Nombre"
                                maxlength="30">
                        </div>
                        @error('name_category')
                        <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                    wire:click="resetInputCategoryBrandUnit">
                    Cerrar
                </button>
                <button type="button" wire:click.prevent="storeCategory()" class="btn btn-danger"
                    wire:loading.attr="disabled" wire:target="storeCategory">
                    <span wire:loading.remove wire:target="storeCategory">
                        {{ $isEditMode ? 'Actualizar' : 'Guardar' }}
                    </span>
                    <span wire:loading wire:target="storeCategory">
                        <i class="bx bx-spin bx-loader"></i> Procesando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modal de Marcas -->
<div wire:ignore.self class="modal fade" id="brandModal" tabindex="-1" aria-labelledby="theModalLabel"
    aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="theModalLabel">
                    REGISTRAR MARCA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-2 p-2">
                    <div class="col-lg-12 col-sm-6 mb-2">
                        <label>Nombre</label>
                        <div class="input-group">
                            <input type="text" wire:model.lazy="name_brand" class="form-control" placeholder="Nombre"
                                maxlength="30">
                        </div>
                        @error('name_brand')
                        <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                    wire:click="resetInputCategoryBrandUnit">
                    Cerrar
                </button>
                <button type="button" wire:click.prevent="storeBrand()" class="btn btn-danger"
                    wire:loading.attr="disabled" wire:target="storeBrand">
                    <span wire:loading.remove wire:target="storeBrand">
                        {{ $isEditMode ? 'Actualizar' : 'Guardar' }}
                    </span>
                    <span wire:loading wire:target="storeBrand">
                        <i class="bx bx-spin bx-loader"></i> Procesando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Unidad -->
<div wire:ignore.self class="modal fade" id="unitModal" tabindex="-1" aria-labelledby="theModalLabel" aria-hidden="true"
    data-bs-keyboard="false" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="theModalLabel">
                    REGISTRAR UNIDAD DE MEDIDA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-2 p-2">
                    <div class="col-lg-12 col-sm-12 mb-2">
                        <label>Unidad de Medida <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" wire:model.lazy="name_unit" class="form-control"
                                placeholder="Ej: CAJA x 12 UNIDADES, MEDIO KILO" maxlength="100">
                        </div>
                        @error('name_unit')
                        <span class="text-danger er">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-8 col-sm-8 mb-2">
                        <label>Unidad</label>
                        <select wire:model.lazy="unit_base_unit" class="form-select">
                            <option value="">-- Seleccionar --</option>
                            @foreach (['BALDE','BARRILES','BOLSA','BOTELLAS','CAJA','CARTONES','CENTIMETRO CUADRADO','CENTIMETRO CUBICO','CENTIMETRO LINEAL','CIENTO DE UNIDADES','DOCENAS','FARDO','GALON','GRAMO','JUEGO','KILOGRAMO','KIT','LIBRAS','LITRO','METRO','METRO CUADRADO','METRO CUBICO','MILIGRAMO','MILILITRO','MILLAR','ONZA','PAQUETE','PAR','PIEZA','PLANCHA','PLIEGO','PUNTO','RESMA','ROLLO','SACO','SET','TAMBOR','TONELADA','UNIDAD','YARDA'] as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 col-sm-4 mb-2">
                        <label>Factor</label>
                        <div class="input-group">
                            <input type="text" wire:model.lazy="unit_factor" class="form-control"
                                placeholder="1.0000" inputmode="decimal" maxlength="10"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/^(\d{1,5})(\.\d{0,4})?.*/, '$1$2')">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                    wire:click="resetInputCategoryBrandUnit">
                    Cerrar
                </button>
                <button type="button" wire:click.prevent="storeUnit()" class="btn btn-danger"
                    wire:loading.attr="disabled" wire:target="storeUnit">
                    <span wire:loading.remove wire:target="storeUnit">
                        {{ $isEditMode ? 'Actualizar' : 'Guardar' }}
                    </span>
                    <span wire:loading wire:target="storeUnit">
                        <i class="bx bx-spin bx-loader"></i> Procesando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>