<div class="page-content">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Usuarios</h4>
            <p class="text-muted mb-0">Administra las cuentas, roles y estados de acceso al sistema.</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="module-counter">{{ $users->total() }} registrados</span>
            @can('crear-usuario')
                <button type="button" wire:click="create" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Nuevo usuario
                </button>
            @endcan
        </div>
    </div>

    <div class="card module-list-card">
        <div class="card-header filter-header">
            <div class="filter-title">
                <i class="bx bx-filter-alt"></i>
                <span>Buscar usuarios</span>
            </div>

            <div class="flex-grow-1">
                <label for="user-search" class="visually-hidden">Buscar usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input
                        id="user-search"
                        type="search"
                        wire:model.live.debounce.300ms="searchTerm"
                        class="form-control"
                        placeholder="Buscar por usuario, nombre, documento o correo"
                        autocomplete="off"
                    >
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-with-actions">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr wire:key="user-row-{{ $user->id }}">
                                <td><strong>{{ $user->login }}</strong></td>
                                <td>{{ trim($user->name.' '.$user->lastname) }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->status ? 'success' : 'secondary' }}">
                                        {{ $user->status ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    @can('editar-usuario')
                                        <button
                                            type="button"
                                            wire:click="edit({{ $user->id }})"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Editar usuario"
                                            aria-label="Editar usuario {{ $user->login }}"
                                        >
                                            <i class="bx bx-edit"></i>
                                        </button>
                                    @endcan

                                    @can('eliminar-usuario')
                                        <button
                                            type="button"
                                            wire:click="toggleStatus({{ $user->id }})"
                                            wire:confirm="¿Confirma el cambio de estado?"
                                            class="btn btn-sm btn-outline-{{ $user->status ? 'danger' : 'success' }}"
                                            title="{{ $user->status ? 'Desactivar' : 'Activar' }} usuario"
                                            aria-label="{{ $user->status ? 'Desactivar' : 'Activar' }} usuario {{ $user->login }}"
                                            @disabled($user->id === auth()->id())
                                        >
                                            <i class="bx bx-power-off"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bx bx-user-x fs-2 d-block mb-2"></i>
                                    No se encontraron usuarios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>

    <div wire:ignore.self class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content module-form-card">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $isEditMode ? 'Editar usuario' : 'Nuevo usuario' }}</h5>
                        <div class="form-card-subtitle">Completa los datos de identificación y acceso de la cuenta.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="user-login" class="form-label">Usuario <span class="text-danger">*</span></label>
                                <input id="user-login" wire:model="login" type="text" maxlength="100" autocomplete="username" class="form-control @error('login') is-invalid @enderror">
                                @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input id="user-name" wire:model="name" type="text" maxlength="150" class="form-control @error('name') is-invalid @enderror">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-lastname" class="form-label">Apellidos <span class="field-optional">Opcional</span></label>
                                <input id="user-lastname" wire:model="lastname" type="text" maxlength="150" class="form-control @error('lastname') is-invalid @enderror">
                                @error('lastname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-document" class="form-label">Documento <span class="text-danger">*</span></label>
                                <input id="user-document" wire:model="document" type="text" maxlength="50" class="form-control @error('document') is-invalid @enderror">
                                @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-email" class="form-label">Correo <span class="text-danger">*</span></label>
                                <input id="user-email" wire:model="email" type="email" maxlength="255" autocomplete="email" class="form-control @error('email') is-invalid @enderror">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-phone" class="form-label">Teléfono <span class="field-optional">Opcional</span></label>
                                <input id="user-phone" wire:model="phone" type="text" maxlength="30" autocomplete="tel" class="form-control @error('phone') is-invalid @enderror">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-role" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select id="user-role" wire:model="role" class="form-select @error('role') is-invalid @enderror">
                                    <option value="">Seleccione un rol</option>
                                    @foreach($roles as $item)
                                        <option value="{{ $item->name }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-status" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select id="user-status" wire:model="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-password" class="form-label">
                                    Contraseña
                                    @if($isEditMode)
                                        <span class="field-optional">Opcional</span>
                                    @else
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input id="user-password" wire:model="password" type="password" minlength="8" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="user-password-confirmation" class="form-label">
                                    Confirmar contraseña
                                    @if($isEditMode)
                                        <span class="field-optional">Opcional</span>
                                    @else
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input id="user-password-confirmation" wire:model="password_confirmation" type="password" minlength="8" autocomplete="new-password" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer form-actions mt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save"><i class="bx bx-save me-1"></i>Guardar usuario</span>
                            <span wire:loading wire:target="save"><i class="bx bx-loader-alt bx-spin me-1"></i>Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @script
        <script>
            $wire.on('show-user-modal', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).show());
            $wire.on('hide-user-modal', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).hide());
        </script>
    @endscript
</div>
