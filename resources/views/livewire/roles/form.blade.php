<div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="role-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form wire:submit="{{ $selected_id > 0 ? 'Update' : 'Create' }}">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="role-modal-title">
                            <i class="bx bx-shield-quarter me-1"></i>{{ $selected_id > 0 ? 'Editar rol' : 'Nuevo rol' }}
                        </h5>
                        <p class="small text-muted mb-0">Asigna un nombre y selecciona las acciones permitidas.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="resetInputFields"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="role-name" class="form-label">Nombre del rol <span class="text-danger">*</span></label>
                            <input
                                id="role-name"
                                type="text"
                                wire:model="name"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Ej.: Encargado de almacén"
                                autocomplete="off"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
                                <div>
                                    <label for="permission-search" class="form-label mb-1">Permisos <span class="text-danger">*</span></label>
                                    <div class="small text-muted">Marca grupos completos o permisos individuales.</div>
                                </div>
                                <div class="input-group" style="max-width: 330px;">
                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                    <input
                                        id="permission-search"
                                        type="search"
                                        wire:model.live.debounce.300ms="searchPermission"
                                        class="form-control"
                                        placeholder="Buscar permiso o módulo"
                                        autocomplete="off"
                                        aria-label="Buscar permisos"
                                    >
                                </div>
                            </div>

                            @error('permisosSelected')
                                <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                            @enderror

                            <div class="table-responsive border rounded" style="max-height: 360px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th>Permiso</th>
                                            <th class="text-center" style="width: 140px;">Seleccionar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($permisos->groupBy('grupo') as $grupo => $items)
                                            <tr class="table-light" wire:key="permission-group-{{ $grupo }}-{{ $componentKey }}">
                                                <td>
                                                    <strong>{{ $grupo ?: 'Otros permisos' }}</strong>
                                                    <div class="small text-muted">{{ $items->count() }} permiso(s)</div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check d-inline-flex align-items-center gap-2 mb-0">
                                                        <input
                                                            id="permission-group-{{ md5($grupo) }}-{{ $componentKey }}"
                                                            type="checkbox"
                                                            class="form-check-input mt-0"
                                                            wire:change="toggleGroup('{{ $grupo }}', $event.target.checked)"
                                                            @checked($this->isGroupSelected($grupo))
                                                        >
                                                        <label class="form-check-label small" for="permission-group-{{ md5($grupo) }}-{{ $componentKey }}">Todo el grupo</label>
                                                    </div>
                                                </td>
                                            </tr>

                                            @foreach($items as $item)
                                                <tr wire:key="permission-row-{{ $item->id }}-{{ $componentKey }}">
                                                    <td class="ps-4">
                                                        <label class="mb-0" for="permission-{{ $item->id }}-{{ $componentKey }}">{{ $item->name }}</label>
                                                    </td>
                                                    <td class="text-center">
                                                        <input
                                                            id="permission-{{ $item->id }}-{{ $componentKey }}"
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            value="{{ $item->name }}"
                                                            wire:model="permisosSelected"
                                                            aria-label="Seleccionar permiso {{ $item->name }}"
                                                        >
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center text-muted py-4">No se encontraron permisos.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        wire:click="resetInputFields"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="Create,Update"
                    >
                        <span wire:loading.remove wire:target="Create,Update">
                            <i class="bx bx-save me-1"></i>{{ $selected_id > 0 ? 'Guardar cambios' : 'Crear rol' }}
                        </span>
                        <span wire:loading wire:target="Create,Update">
                            <i class="bx bx-loader-alt bx-spin me-1"></i>Guardando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
