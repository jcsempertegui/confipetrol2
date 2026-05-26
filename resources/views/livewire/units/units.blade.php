@push('title', 'Unidades de Medida')

<div class="page-content"
    style="height: calc(100vh - 60px); overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0;">
    <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Administracion</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Unidades de Medida</li>
            </ol>
            @include('components.tools.buttonRegister')
        </div>
    </div>

    <div class="card" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2" style="flex-shrink: 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-ruler"></i>
                <span>Listar Unidades de Medida</span>
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
                    @component('components.tools.filterbox', ['filterCount' => ($filter_status != 1 ? 1 : 0)])
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
                            <th>UNIDAD DE MEDIDA</th>
                            <th>UNIDAD</th>
                            <th>FACTOR</th>
                            <th>FECHA</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($units->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach ($units as $index => $unit)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $unit->name ?: 'S/N' }}</td>
                                    <td>{{ $unit->base_unit ?: '-' }}</td>
                                    <td>{{ $unit->factor ? number_format($unit->factor, 4) : '-' }}</td>
                                    <td>{{ $unit->created_at ?: 'S/N' }}</td>
                                    <td>
                                        @if ($unit->status == 1)
                                            <div class="badge rounded-pill text-success bg-light-success text-uppercase">
                                                ACTIVO</div>
                                        @else
                                            <div class="badge rounded-pill text-danger bg-light-danger text-uppercase">
                                                INACTIVO</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            <a href="javascript:;" wire:click="edit({{ $unit->id }})" data-bs-toggle="modal"
                                                data-bs-target="#theModal" class="btn-action-primary"><i
                                                    class="bx bxs-edit-alt"></i></a>
                                            @if ($unit->status == 1)
                                                <a href="javascript:;" onclick="confirmDelete({{ $unit->id }}, 'delete')"
                                                    class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                            @else
                                                <a href="javascript:;" onclick="confirmDelete({{ $unit->id }}, 'restore')"
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
                {{ $units->links() }}
            </div>

            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-ruler"></i>
                                {{ $isEditMode ? 'ACTUALIZAR UNIDAD DE MEDIDA' : 'REGISTRAR UNIDAD DE MEDIDA' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body"
                            style="max-height: calc(100vh - 200px); overflow-y: auto; overflow-x: hidden;">
                            <div class="row mb-2 p-2">
                                <div class="col-lg-12 col-sm-12 mb-2">
                                    <label>Unidad de Medida <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="name" class="form-control"
                                            placeholder="Ej: CAJA x 12 UNIDADES, MEDIO KILO" maxlength="100">
                                    </div>
                                    @error('name') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-lg-8 col-sm-8 mb-2">
                                    <label>Unidad</label>
                                    <select wire:model.lazy="base_unit" class="form-select">
                                        <option value="">-- Seleccionar --</option>
                                        @foreach ($baseUnitOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('base_unit') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-lg-4 col-sm-4 mb-2">
                                    <label>Factor</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="factor" class="form-control"
                                            placeholder="1.0000" inputmode="decimal" maxlength="10"
                                            oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1').replace(/^(\d{1,5})(\.\d{0,4})?.*/, '$1$2')">
                                    </div>
                                    @error('factor') <span class="text-danger er">{{ $message }}</span> @enderror
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
    document.addEventListener('livewire:init', function () {
        Livewire.on('unitStoreOrUpdate', (Msg) => {
            $('#theModal').modal('hide');
            toast(Msg, 'success');
        });

        Livewire.on('unitDeleted', (Msg) => {
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