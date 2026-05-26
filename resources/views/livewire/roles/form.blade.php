<div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-cog"></i>
                    {{ $selected_id > 0 ? 'ACTUALIZAR ROLES' : 'REGISTRAR ROLES' }}
                </h5>
                <button class="btn-close" wire:click.prevent="resetInputFields()" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <div class="col-lg-12 col-sm-6 mb-2">
                    <label>Rol</label>
                    <div class="input-group">
                        <input type="text" wire:model.lazy="name" class="form-control" placeholder="Rol">
                    </div>
                    @error('name')
                    <span class="text-danger er">{{ $message }}</span>
                    @enderror
                </div>

                <hr>
                @error('permisosSelected')
                <span class="text-danger er">{{ $message }}</span>
                @enderror

                <div class="col-12">

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                        <h5 class="card-title mb-0">Permisos <span class="text-danger">*</span></h5>
                        <div>
                            <div class="position-relative">
                                <input type="text" class="form-control ps-5" wire:model.live="searchPermission"
                                    placeholder="Buscar..." maxlength="20" style="box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.06); 
                      border: 1px solid #e2e8f0; 
                      border-radius: 10px;">
                                <span class="position-absolute product-show translate-middle-y" style="top: 55%;"><i
                                        class="bx bx-search-alt"></i></span>
                            </div>
                        </div>

                    </div>

                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="table">
                            <thead class="thead-dark">
                                <tr>
                                    <th>DESCRIPCION PERMISO</th>
                                    <th style="width: 10%;">SELECCIONAR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permisos->groupBy('grupo') as $grupo => $items)
                                <tr class="table-primary" wire:key="grupo-{{ $grupo }}-{{ $componentKey }}">
                                    <td class="fw-bold text-primary text-uppercase">
                                        {{ $grupo }}
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input"
                                            wire:change="toggleGroup('{{ $grupo }}', $event.target.checked)"
                                            {{ $this->isGroupSelected($grupo) ? 'checked' : '' }}
                                            id="chk-group-{{ $grupo }}-{{ $componentKey }}">
                                    </td>
                                </tr>

                                @foreach ($items as $item)
                                <tr wire:key="permiso-row-{{ $item->id }}-{{ $componentKey }}">
                                    <td>{{ $item->name }}</td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->name }}"
                                            wire:model="permisosSelected" id="permiso-{{ $item->id }}-{{ $componentKey }}">
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-outline-secondary close-btn"
                    wire:click.prevent="resetInputFields()" data-bs-dismiss="modal">
                    Cerrar
                </button>

                @if ($selected_id < 1) 
                <button type="button" wire:click.prevent="Create()" class="btn btn-danger close-modal">Guardar</button>
                @else
                <button type="button" wire:click.prevent="Update()" class="btn btn-danger close-modal">Actualizar</button>
                @endif

            </div>
        </div>
    </div>
</div>