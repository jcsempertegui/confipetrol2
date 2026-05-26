@push('title', 'Categorias')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item">Administracion</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Categorias</li>
            </ol>
            @can('crear-usuario')
                @include('components.tools.buttonRegister')
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-body px-4 mt-2">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <h5 class="card-title mb-0">Listar Categorías</h5>
                @include('components.tools.searchbox')

            </div>

            <hr>
            <div class="table-responsive">
                <table class="table" id="theTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>NOMBRES</th>
                            <th>FECHA </th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($categories->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($categories as $index => $categorie)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>

                                    <td>{{ $categorie->name ?: 'S/N' }}</td>
                                    <td>{{ $categorie->created_at ?: 'S/N' }}</td>
                                    <td>
                                        @if ($categorie->status == 1)
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
                                            <a href="javascript:;" wire:click="edit({{ $categorie->id }})"
                                                data-bs-toggle="modal" data-bs-target="#theModal" class="btn-action-primary"><i
                                                    class="bx bxs-edit-alt"></i></a>
                                            @if ($categorie->status == 1)
                                                @can('eliminar-usuario')
                                                    <a href="javascript:;" onclick="confirmDelete({{ $categorie->id }}, 'delete')"
                                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                                @endcan
                                            @else
                                                @can('restaurar-usuario')
                                                    <a href="javascript:;" onclick="confirmDelete({{ $categorie->id }}, 'restore')"
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
            {{ $categories->links() }}

            <!-- Modal de Categorias -->
            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-folder"></i>
                                {{ $isEditMode ? 'ACTUALIZAR CATEGORIAS' : 'REGISTRAR CATEGORIAS' }}
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
        Livewire.on('categorieStoreOrUpdate', (Msg, type) => {
            $('#theModal').modal('hide');
            toast(Msg, 'success')
        });

        //ACTION DELETE
        Livewire.on('categorieDeleted', (Msg, type) => {
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