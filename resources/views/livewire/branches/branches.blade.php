@push('title', 'Sucursales')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-primary">Administracion</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Sucursales</li>
            </ol>
            @can('crear-productos')
            @include('components.tools.buttonRegister')
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-box"></i>
                <span class="fw-semibold">Listar Sucursales</span>
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
                <table class="table table-hover align-middle table-striped" style="width: 100%;">                   <thead>
                        <tr>
                            <th>N°</th>
                            <th>CODIGO</th>
                            <th>TIPO SUCURSAL</th>
                            <th>NOMBRE</th>
                            <th>TELEFONO</th>
                            <th>DIRECCION</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($branches->isEmpty())
                        <tr>
                            <td colspan="8" class="text-center">No se encontraron registros.</td>
                        </tr>
                        @else
                        @foreach($branches as $index => $branche)
                        <tr>
                            <td>{{ $startCount - $index }}</td>

                            <td>{{$branche->code ?: 'S/N'}} </td>
                            <td>{{$branche->branch_type ?: 'S/N'}} </td>
                            <td>{{$branche->name ?: 'S/N'}}</td>
                            <td>{{$branche->phone ?: 'S/N'}}</td>
                            <td>{{$branche->address ?: 'S/N'}}</td>

                            <td>
                                @if($branche->status == 1)
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
                                    <a href="javascript:;" wire:click="edit({{ $branche->id }})" data-bs-toggle="modal"
                                        data-bs-target="#theModal" class="btn-action-primary"><i
                                            class="bx bxs-edit-alt"></i></a>
                                    <a href="javascript:;" wire:click="syncInventory({{ $branche->id }})"
                                        class="btn-action-primary ms-1" title="Sincronizar Productos" style="background-color: #0dcaf0; border-color: #0dcaf0; color: white;"><i
                                            class="bx bx-sync"></i></a>
                                    @if($branche->status == 1)
                                    @can('eliminar-usuario')
                                    <a href="javascript:;" onclick="confirmDelete({{ $branche->id }}, 'delete')"
                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                    @endcan
                                    @else
                                    @can('restaurar-usuario')
                                    <a href="javascript:;" onclick="confirmDelete({{ $branche->id }}, 'restore')"
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
            {{ $branches->links() }}

            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-building"></i>
                                {{ $isEditMode ? 'Actualizar Sucursal' : 'Registrar Sucursal' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 p-2">
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Tipo de Sucursal</label>
                                    <div class="input-group">
                                        <select wire:model.live="branch_type" class="form-select">
                                            <option value="">Seleccione una opción</option>
                                            <option value="Casa Matriz">Casa Matriz</option>
                                            <option value="Sucursal">Sucursal</option>
                                        </select>
                                    </div>
                                    @error('branch_type')<span class="text-danger er">{{$message}}</span> @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Codigo</label>
                                    <div class="input-group">
                                        <input type="text" wire:model="code" class="form-control" placeholder="Codigo"
                                            readonly>
                                    </div>
                                    @error('code')<span class="text-danger er">{{$message}}</span> @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Nombre</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="name" class="form-control"
                                            placeholder="Nombre" maxlength="30">
                                    </div>
                                    @error('name')<span class="text-danger er">{{$message}}</span> @enderror
                                </div>

                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Teléfono</label>
                                    <div class="input-group">
                                        <input type="number" wire:model.lazy="phone" class="form-control"
                                            placeholder="Teléfono" max="7">
                                    </div>
                                    @error('phone')<span class="text-danger er">{{$message}}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <div class="form-group" wire:ignore>
                                        <label for="address">Direccion </label>
                                        <input type="text" class="form-control" name="address"
                                            placeholder="Mensaje de Agradecimiento" wire:model="address"></input>
                                    </div>
                                    @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">

                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                wire:click="resetInputFields">
                                Cerrar
                            </button>

                            <button type="button" wire:click.prevent="storeOrUpdate()" class="btn btn-danger"
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
document.addEventListener('livewire:init', function() {
    Livewire.on('branchesStoreOrUpdate', (Msg, type) => {
        $('#theModal').modal('hide');
        toast(Msg, 'success')
    });

    Livewire.on('branchesDeleted', (Msg, type) => {
        toast(Msg, 'success')
    });

    Livewire.on('alert', (data) => {
        const [msg, type] = data;
        toast(msg, type);
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