@push('title', 'Clientes')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Inicio</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Clientes</li>
            </ol>
            <div class="d-flex align-items-center gap-2">

                @can('crear-productos')
                    @include('components.tools.buttonRegister')
                @endcan
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-box"></i>
                <span class="fw-semibold">Listar Clientes</span>
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
                </div> @include('components.tools.searchbox')
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>RAZON SOCIAL</th>
                            <th>TIPO DOC.</th>
                            <th>CI/NIT/CEX</th>
                            <th>TELEFONO</th>
                            <th>CORREO</th>
                            <th>DIRECCION</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($customers->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($customers as $index => $customer)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $customer->name ?: 'S/N' }}</td>
                                    <td>{{ $customer->document_type ?: 'S/N' }}</td>
                                    <td>{{ $customer->document ?: 'S/N' }}</td>
                                    <td>{{ $customer->phone ?: 'S/N' }}</td>
                                    <td>{{ $customer->email ?: 'S/N' }}</td>
                                    <td>{{ $customer->address ?: 'S/N' }}</td>
                                    <td>
                                        @if ($customer->status == 1)
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
                                            @can('editar-clientes')
                                                <a href="javascript:;" wire:click="edit({{ $customer->id }})" data-bs-toggle="modal"
                                                    data-bs-target="#theModal" class="btn-action-primary"><i
                                                        class="bx bxs-edit-alt"></i></a>
                                            @endcan
                                            @if ($customer->status == 1)
                                                @can('eliminar-clientes')
                                                    <a href="javascript:;" onclick="confirmDelete({{ $customer->id }}, 'delete')"
                                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                                @endcan
                                            @else
                                                @can('restaurar-clientes')
                                                    <a href="javascript:;" onclick="confirmDelete({{ $customer->id }}, 'restore')"
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
            {{ $customers->links() }}

            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-group"></i>
                                {{ $isEditMode ? 'Actualizar Cliente' : 'Registrar Cliente' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                    @error('document_type')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Documento</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="document" class="form-control"
                                            inputmode="decimal" maxlength="15"
                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                            placeholder="Documento">
                                    </div>
                                    @error('document')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Razon Social</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="name" class="form-control"
                                            placeholder="Nombre" maxlength="30">
                                    </div>
                                    @error('name')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Teléfono</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="phone" class="form-control"
                                            inputmode="decimal" maxlength="12"
                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')"
                                            placeholder="Teléfono">
                                    </div>
                                    @error('phone')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Correo</label>
                                    <div class="input-group">
                                        <input type="email" wire:model.lazy="email" class="form-control" maxlength="40"
                                            placeholder="correo@gmail.com">
                                    </div>
                                    @error('email')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-12 col-sm-6 mb-2">
                                    <label>Dirección</label>
                                    <div class="input-group">
                                        <textarea class="form-control" name="message" maxlength="200" rows="2"
                                            placeholder="Direccion del Cliente" wire:model="address"></textarea>
                                    </div>
                                    @error('address')
                                        <span class="text-danger">{{ $message }}</span>
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
        Livewire.on('customerStoreOrUpdate', (Msg, type) => {
            $('#theModal').modal('hide');
            toast(Msg, 'success')
        });

        Livewire.on('customerDeleted', (Msg, type) => {
            toast(Msg, 'success')
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