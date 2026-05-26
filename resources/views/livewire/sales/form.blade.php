<div wire:ignore.self class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModal"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerModal">
                    {{ $isEditMode ? 'ACTUALIZAR CLIENTE' : 'REGISTRAR CLIENTE' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-2 p-2">

                    <div class="col-lg-6 col-sm-6 mb-2">
                        <label>Tipo Documento</label>
                        <div class="input-group">
                            <select wire:model.lazy="document_type" class="form-select">
                                <option value="" selected>Seleccionar</option>
                                <option value="CI">CI</option>
                                <option value="NIT">NIT</option>
                                <option value="CEX">CEX</option>
                            </select>
                        </div>
                        @error('document_type')<span class="text-danger er">{{$message}}</span> @enderror
                    </div>

                    <div class="col-lg-6 col-sm-6 mb-2">
                        <label>Documento</label>
                        <div class="input-group">
                            <input type="text" wire:model.lazy="document" class="form-control"
                                placeholder="Cedula de Identidad" maxlength="12"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        @error('document')<span class="text-danger er">{{$message}}</span>
                        @enderror
                    </div>

                    <div class="col-lg-6 col-sm-6 mb-2">
                        <label>Razon Social</label>
                        <div class="input-group">
                            <input type="text" wire:model.lazy="name" class="form-control" placeholder="Razon Social"
                                maxlength="30">
                        </div>
                        @error('name')<span class="text-danger er">{{$message}}</span> @enderror
                    </div>

                    <div class="col-lg-6 col-sm-6 mb-2">
                        <label>Teléfono</label>
                        <div class="input-group">
                            <input type="tel" wire:model.lazy="phone" class="form-control" placeholder="Teléfono"
                                maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        @error('phone')<span class="text-danger er">{{$message}}</span>
                        @enderror
                    </div>

                    <div class="col-lg-6 col-sm-6 mb-2">
                        <label>Correo</label>
                        <div class="input-group">
                            <input type="email" wire:model.lazy="email" class="form-control" placeholder="correo">
                        </div>
                        @error('email')<span class="text-danger er">{{$message}}</span>
                        @enderror
                    </div>

                    <div class="col-lg-12 col-sm-6 mb-2">
                        <label>Dirección</label>
                        <div class="input-group">
                            <textarea class="form-control" name="message" rows="2" placeholder="Direccion del Cliente"
                                wire:model="address"></textarea>
                        </div>
                        @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer">

                <button type="button" wire:click.prevent="storeCustomer()" class="btn btn-primary"
                    wire:loading.attr="disabled" wire:target="storeCustomer">
                    <span wire:loading.remove wire:target="storeCustomer">
                        {{ $isEditMode ? 'Actualizar' : 'Guardar' }}
                    </span>
                    <span wire:loading wire:target="storeCustomer">
                        <i class="bx bx-spin bx-loader"></i> Procesando...
                    </span>
                </button>

                <button type="button" class="btn btn-danger" data-bs-dismiss="modal" wire:click="resetInputCustomer">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>