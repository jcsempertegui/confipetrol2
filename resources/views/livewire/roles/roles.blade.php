@push('title', 'Roles')

<div class="page-content">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Roles y permisos</h4>
            <p class="text-muted mb-0">Define qué acciones puede realizar cada perfil dentro del sistema.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="module-counter">{{ $roles->total() }} registrados</span>
            @can('crear-rol')
                <button type="button" wire:click="resetInputFields" data-bs-toggle="modal" data-bs-target="#theModal" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Nuevo rol
                </button>
            @endcan
        </div>
    </div>

    <div class="card module-list-card">
        <div class="card-header filter-header">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input
                            type="search"
                            wire:model.live.debounce.500ms="searchTerm"
                            class="form-control"
                            placeholder="Buscar por nombre del rol"
                            maxlength="55"
                            autocomplete="off"
                            aria-label="Buscar roles"
                        >
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="input-group">
                        <label class="input-group-text" for="roles-per-page">Mostrar</label>
                        <select id="roles-per-page" wire:model.live="perPage" class="form-select" aria-label="Registros por página">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}">{{ $option }} registros</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-with-actions">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Fecha de creación</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr wire:key="role-row-{{ $role->id }}">
                                <td>
                                    <strong>{{ $role->name }}</strong>
                                    <div class="small text-muted">Perfil de acceso del sistema</div>
                                </td>
                                <td class="text-nowrap">{{ $role->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $role->status ? 'success' : 'secondary' }}">
                                        {{ $role->status ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    @can('editar-rol')
                                        <button
                                            type="button"
                                            wire:click="edit({{ $role->id }})"
                                            data-bs-toggle="modal"
                                            data-bs-target="#theModal"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Editar rol"
                                            aria-label="Editar rol {{ $role->name }}"
                                        >
                                            <i class="bx bx-edit"></i>
                                        </button>
                                    @endcan

                                    @if($role->status)
                                        @can('eliminar-rol')
                                            <button
                                                type="button"
                                                wire:click="$dispatch('delete-confirme', {{ $role->id }})"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Desactivar rol"
                                                aria-label="Desactivar rol {{ $role->name }}"
                                            >
                                                <i class="bx bx-power-off"></i>
                                            </button>
                                        @endcan
                                    @else
                                        @can('restaurar-rol')
                                            <button
                                                type="button"
                                                wire:click="$dispatch('delete-confirme', {{ $role->id }})"
                                                class="btn btn-sm btn-outline-success"
                                                title="Activar rol"
                                                aria-label="Activar rol {{ $role->name }}"
                                            >
                                                <i class="bx bx-refresh"></i>
                                            </button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="bx bx-search-alt fs-2 d-block mb-2"></i>
                                    No se encontraron roles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($roles->hasPages())
            <div class="card-footer">{{ $roles->links() }}</div>
        @endif
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
            title: '¿Cambiar el estado del rol?',
            text: 'Los usuarios asociados perderán o recuperarán el acceso correspondiente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('Destroy', id)
                Swal.close();
            }
        });
    });
});
</script>
