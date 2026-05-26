@push('title', 'Trabajadores')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Inicio</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Trabajadores</li>
            </ol>
            <div class="d-flex align-items-center gap-2">
                @can('crear-trabajadores')
                    @include('components.tools.buttonRegister')
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-group"></i>
                <span class="fw-semibold">Listar Trabajadores</span>
            </div>
        </div>

        <div class="card-body px-3">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Mostrar</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">registros</span>
                </div>
                @include('components.tools.searchbox')
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>NOMBRE</th>
                            <th>APELLIDOS</th>
                            <th>DOCUMENTO</th>
                            <th>CARGO</th>
                            <th>F. NACIMIENTO</th>
                            <th>TELÉFONO</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($workers->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($workers as $index => $worker)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $worker->name ?: 'S/N' }}</td>
                                    <td>{{ $worker->last_name ?: 'S/N' }}</td>
                                    <td>{{ $worker->document ?: 'S/N' }}</td>
                                    <td>{{ $worker->cargo ?: 'S/N' }}</td>
                                    <td>{{ $worker->birth_date ? \Carbon\Carbon::parse($worker->birth_date)->format('d/m/Y') : 'S/N' }}</td>
                                    <td>{{ $worker->phone ?: 'S/N' }}</td>
                                    <td>
                                        @if ($worker->status == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">
                                                ACTIVO
                                            </div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">
                                                INACTIVO
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            @can('editar-trabajadores')
                                                <a href="javascript:;" wire:click="edit({{ $worker->id }})"
                                                    data-bs-toggle="modal" data-bs-target="#theModal"
                                                    class="btn-action-primary">
                                                    <i class="bx bxs-edit-alt"></i>
                                                </a>
                                            @endcan
                                            @if ($worker->status == 1)
                                                @can('eliminar-trabajadores')
                                                    <a href="javascript:;"
                                                        onclick="confirmDelete({{ $worker->id }}, 'delete')"
                                                        class="btn-action-danger ms-1">
                                                        <i class="bx bxs-trash"></i>
                                                    </a>
                                                @endcan
                                            @else
                                                @can('restaurar-trabajadores')
                                                    <a href="javascript:;"
                                                        onclick="confirmDelete({{ $worker->id }}, 'restore')"
                                                        class="btn-action-warning ms-1">
                                                        <i class="bx bx-refresh"></i>
                                                    </a>
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
            {{ $workers->links() }}

            {{-- MODAL --}}
            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-group"></i>
                                {{ $isEditMode ? 'Actualizar Trabajador' : 'Registrar Trabajador' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 p-2">

                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Nombre</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="name" class="form-control"
                                            placeholder="Nombre" maxlength="100">
                                    </div>
                                    @error('name')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Apellidos</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="last_name" class="form-control"
                                            placeholder="Apellidos" maxlength="100">
                                    </div>
                                    @error('last_name')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Documento</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="document" class="form-control"
                                            inputmode="decimal" maxlength="15"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                            placeholder="Documento">
                                    </div>
                                    @error('document')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Cargo</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="cargo" class="form-control"
                                            placeholder="Cargo" maxlength="100">
                                    </div>
                                    @error('cargo')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Fecha de Nacimiento</label>
                                    <div class="input-group">
                                        <input type="date" wire:model.lazy="birth_date" class="form-control">
                                    </div>
                                    @error('birth_date')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Teléfono</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="phone" class="form-control"
                                            inputmode="decimal" maxlength="12"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                            placeholder="Teléfono">
                                    </div>
                                    @error('phone')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                wire:click="resetInputFields">
                                Cancelar
                            </button>
                            <button type="button" wire:click.prevent="storeOrUpdate()" class="btn btn-primary"
                                wire:loading.attr="disabled" wire:target="storeOrUpdate">
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
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('workerStoreOrUpdate', (Msg) => {
            $('#theModal').modal('hide');
            toast(Msg, 'success');
        });

        Livewire.on('workerDeleted', (Msg) => {
            toast(Msg, 'success');
        });
    });

    function confirmDelete(id, action) {
        Swal.fire({
            title: action === 'delete' ? "¿Está seguro de eliminar?" : "¿Está seguro de restaurar?",
            text: action === 'delete' ?
                "El registro no se eliminará de forma permanente, solo cambiará el estado!" :
                "El registro será restaurado, cambiando su estado a activo!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: action === 'delete' ? "Si, Eliminar!" : "Si, Restaurar!",
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('delete', id);
            }
        });
    }
</script>
