@push('title', 'Roles')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Administracion</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Roles</li>
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
                <span class="fw-semibold">Listar Roles</span>
            </div>
        </div>

        <div class="card-body px-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
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
                <table class="table table-hover align-middle table-striped" style="width: 100%;">                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>ROL</th>
                            <th>FECHA</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($roles->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center">No se encontraron registros.</td>
                        </tr>
                        @else
                        @foreach($roles as $index => $role)
                        <tr>
                            <td>{{ $startCount - $index }}</td>

                            <td>{{$role->name}}</td>
                            <td>{{$role->created_at}}</td>
                            <td>
                                @if($role->status == 1)
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
                                    @can('editar-rol')
                                    <a href="javascript:;" wire:click="edit({{ $role->id }})" data-bs-toggle="modal"
                                        data-bs-target="#theModal" class="btn-action-primary"><i
                                            class="bx bxs-edit-alt"></i></a>
                                    @endcan
                                    @if($role->status == 1)
                                    @can('eliminar-rol')
                                    <a href="javascript:;" wire:click="$dispatch('delete-confirme', {{$role->id}})"
                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                    @endcan
                                    @else
                                    @can('restaurar-rol')
                                    <a href="javascript:;" wire:click="$dispatch('delete-confirme', {{$role->id}})"
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
            {{ $roles->links() }}
        </div>
    </div>
    @include('livewire.roles.form')
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    Livewire.on('role-added', (Msg, type) => {
        $('#theModal').modal('hide');
        toast(Msg, 'success')
    });
    Livewire.on('role-updated', (Msg, type) => {
        $('#theModal').modal('hide');
        toast(Msg, 'success')
    });
    Livewire.on('role-deleted', (Msg, type) => {
        toast(Msg, 'success')
    });
    Livewire.on('role-exists', (Msg, type) => {
        toast(Msg, 'error')
    });
    Livewire.on('role-error', (Msg, type) => {
        toast(Msg, 'error')
    });
    Livewire.on('hide-modal', () => {
        $('#theModal').modal('hide');
    });

    Livewire.on('delete-confirme', id => {
        Swal.fire({
            title: "Esta seguro de eliminar?",
            text: "El registro no se eliminará de forma permanente, solo cambiará el estado!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, Eliminar!"
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('Destroy', id)
                swal.close();
            }
        });
    });
});
</script>