@push('title', 'Almacenes')

<div class="page-content"
    style="height: calc(100vh - 60px); overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0;">
    <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-primary">Inventario</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Almacenes</li>
            </ol>
            @include('components.tools.buttonRegister')
        </div>
    </div>

    <div class="card" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2" style="flex-shrink: 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-building-house"></i>
                <span>Listar Almacenes</span>
            </div>
        </div>

        <div class="card-body px-3"
            style="flex: 1; min-height: 0; overflow: hidden; display: flex; flex-direction: column;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2"
                style="flex-shrink: 0;">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Mostrar</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                        @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">registros</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @component('components.tools.filterbox', ['filterCount' => ($filter_branch ? 1 : 0) +
                    ($filter_status != 1 ? 1 : 0)])
                    <div class="mb-2">
                        <select wire:model.live="filter_branch" class="form-select filter-pro-select">
                            <option value="">FILTRO POR SUCURSAL</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ mb_strtoupper($branch->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <select wire:model.live="filter_status" class="form-select filter-pro-select">
                            <option value="1">ACTIVO</option>
                            <option value="0">INACTIVO</option>
                            <option value="">TODOS</option>
                        </select>
                    </div>
                    @endcomponent
                    @include('components.tools.searchbox')
                </div>
            </div>

            <div class="table-responsive" style="flex: 1; min-height: 0; overflow: auto;">
                <table class="table table-hover align-middle table-striped" style="width: 100%;">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>NOMBRE</th>
                            <th>SUCURSAL</th>
                            <th>POR DEFECTO</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($warehouses->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center">No se encontraron registros.</td>
                        </tr>
                        @else
                        @foreach ($warehouses as $index => $warehouse)
                        <tr>
                            <td>{{ $startCount - $index }}</td>
                            <td>{{ $warehouse->name }}</td>
                            <td>{{ $warehouse->branch->name ?? 'S/N' }}</td>
                            <td>
                                @if ($warehouse->is_default == 1)
                                <div class="badge rounded-pill text-success bg-light-success text-uppercase">
                                    Sí</div>
                                @else
                                <div class="badge rounded-pill text-secondary bg-light-secondary text-uppercase">
                                    No</div>
                                @endif
                            </td>
                            <td>
                                @if ($warehouse->status == 1)
                                <div class="badge rounded-pill text-success bg-light-success text-uppercase">
                                    ACTIVO</div>
                                @else
                                <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">
                                    INACTIVO</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="javascript:;" wire:click="edit({{ $warehouse->id }})"
                                        data-bs-toggle="modal" data-bs-target="#theModal" class="btn-action-primary"><i
                                            class="bx bxs-edit-alt"></i></a>
                                    <a href="javascript:;" wire:click="syncInventory({{ $warehouse->id }})"
                                        class="btn-action-primary ms-1" title="Sincronizar Productos" style="background-color: #0dcaf0; border-color: #0dcaf0; color: white;"><i
                                            class="bx bx-sync"></i></a>
                                    @if ($warehouse->status == 1)
                                    <a href="javascript:;" onclick="confirmDelete({{ $warehouse->id }}, 'delete')"
                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                    @else
                                    <a href="javascript:;" onclick="confirmDelete({{ $warehouse->id }}, 'restore')"
                                        class="btn-action-warning ms-1"><i class="bx bx-refresh"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div style="flex-shrink: 0; padding-top: 0.4rem;">
                {{ $warehouses->links() }}
            </div>

            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-building-house"></i>
                                {{ $isEditMode ? 'ACTUALIZAR ALMACÉN' : 'REGISTRAR ALMACÉN' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 p-2">
                                <div class="col-md-12 mb-3">
                                    <label>Nombre del Almacén</label>
                                    <input type="text" wire:model.lazy="name" class="form-control"
                                        placeholder="Nombre del almacén" maxlength="100">
                                    @error('name') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label>Sucursal</label>
                                    <select wire:model="branch_id_field" class="form-select">
                                        <option value="">Seleccionar</option>
                                        @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected($branch->id == $branch_id_field)>
                                            {{ $branch->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id_field') <span class="text-danger er">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-2">
                                    <div class="form-check form-switch form-check-danger">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="isDefaultSwitch" wire:model.live="is_default">
                                        <label class="form-check-label fw-bold" for="isDefaultSwitch">
                                            ¿Es el almacén por defecto de la sucursal?
                                        </label>
                                    </div>
                                    @if ($is_default)
                                    <small class="text-warning d-block mt-1">
                                        <i class="bx bx-info-circle"></i>
                                        Al guardar, este almacén será el único por defecto para la sucursal
                                        seleccionada.
                                    </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                                wire:click="resetInputFields">Cerrar</button>
                            <button type="button" wire:click.prevent="storeOrUpdate()" class="btn btn-danger"
                                wire:loading.attr="disabled" wire:target="storeOrUpdate">
                                <span wire:loading.remove
                                    wire:target="storeOrUpdate">{{ $isEditMode ? 'Actualizar' : 'Guardar' }}</span>
                                <span wire:loading wire:target="storeOrUpdate"><i class="bx bx-spin bx-loader"></i>
                                    Procesando...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
        if (result.isConfirmed) @this.call('delete', id);
    });
}

document.addEventListener('livewire:init', function() {
    Livewire.on('warehouseStoreOrUpdate', (Msg) => {
        $('#theModal').modal('hide');
        toast(Msg, 'success');
    });

    Livewire.on('warehouseDeleted', (Msg) => {
        toast(Msg, 'success');
    });

    Livewire.on('alert', (data) => {
        const [msg, type] = data;
        toast(msg, type);
    });
});
</script>