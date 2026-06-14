@push('title', 'Tallas')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item">Administracion</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Tallas</li>
            </ol>
            @can('crear-tallas')
                @include('components.tools.buttonRegister')
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-body px-4 mt-2">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <h5 class="card-title mb-0">Listar Tallas</h5>
                @include('components.tools.searchbox')
            </div>

            <hr>
            <div class="table-responsive">
                <table class="table" id="theTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>NOMBRES</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($sizes->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($sizes as $index => $size)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $size->name ?: 'S/N' }}</td>
                                    <td>
                                        @if ($size->status == 1)
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
                                            @can('editar-tallas')
                                            <a href="javascript:;" wire:click="edit({{ $size->id }})"
                                                data-bs-toggle="modal" data-bs-target="#theModal" class="btn-action-primary"><i
                                                    class="bx bxs-edit-alt"></i></a>
                                            @endcan
                                            @if ($size->status == 1)
                                                @can('eliminar-tallas')
                                                    <a href="javascript:;" onclick="confirmDeleteSize({{ $size->id }}, 'delete')"
                                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                                @endcan
                                            @else
                                                @can('restaurar-tallas')
                                                    <a href="javascript:;" onclick="confirmDeleteSize({{ $size->id }}, 'restore')"
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
            {{ $sizes->links() }}

            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-move"></i>
                                {{ $isEditMode ? 'ACTUALIZAR TALLAS' : 'REGISTRAR TALLAS' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 p-2">
                                <div class="col-lg-12 col-sm-6 mb-2">
                                    <label>Nombre</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="name" class="form-control"
                                            placeholder="Nombre" maxlength="30">
                                    </div>
                                    @error('name')
                                        <span class="text-danger er">{{ $message }}</span>
                                    @enderror
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
    document.addEventListener('livewire:init', function () {
        Livewire.on('sizeStoreOrUpdate', (Msg) => {
            $('#theModal').modal('hide');
            toast(Msg, 'success')
        });

        Livewire.on('sizeDeleted', (Msg) => {
            toast(Msg, 'success')
        });
    });

    function confirmDeleteSize(id, action) {
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