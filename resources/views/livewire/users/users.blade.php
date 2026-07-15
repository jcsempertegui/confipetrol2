<div class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Usuarios</h4>@can('crear-usuario')<button wire:click="create" class="btn btn-primary"><i class="bx bx-plus"></i> Nuevo usuario</button>@endcan</div>
    <div class="card"><div class="card-body">
        <div class="row mb-3"><div class="col-md-5"><input wire:model.live.debounce.300ms="searchTerm" class="form-control" placeholder="Buscar usuario..."></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Usuario</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
        @forelse($users as $user)<tr><td>{{ $user->login }}</td><td>{{ trim($user->name.' '.$user->lastname) }}</td><td>{{ $user->email }}</td><td>{{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td><td><span class="badge bg-{{ $user->status ? 'success' : 'secondary' }}">{{ $user->status ? 'Activo' : 'Inactivo' }}</span></td><td class="text-end">
            @can('editar-usuario')<button wire:click="edit({{ $user->id }})" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i></button>@endcan
            @can('eliminar-usuario')<button wire:click="toggleStatus({{ $user->id }})" wire:confirm="¿Confirma el cambio de estado?" class="btn btn-sm btn-outline-{{ $user->status ? 'danger' : 'success' }}" @disabled($user->id === auth()->id())><i class="bx bx-power-off"></i></button>@endcan
        </td></tr>@empty<tr><td colspan="6" class="text-center py-4">No se encontraron usuarios.</td></tr>@endforelse
        </tbody></table></div>{{ $users->links() }}
    </div></div>
    <div wire:ignore.self class="modal fade" id="userModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">{{ $isEditMode ? 'Editar usuario' : 'Nuevo usuario' }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form wire:submit="save"><div class="modal-body"><div class="row g-3">
        @foreach([['login','Usuario'],['name','Nombre'],['lastname','Apellidos'],['document','Documento'],['email','Correo'],['phone','Teléfono']] as [$field,$label])<div class="col-md-6"><label class="form-label">{{ $label }}</label><input wire:model="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" class="form-control @error($field) is-invalid @enderror">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>@endforeach
        <div class="col-md-6"><label class="form-label">Rol</label><select wire:model="role" class="form-select @error('role') is-invalid @enderror"><option value="">Seleccione...</option>@foreach($roles as $item)<option value="{{ $item->name }}">{{ $item->name }}</option>@endforeach</select>@error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label">Estado</label><select wire:model="status" class="form-select"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
        <div class="col-md-6"><label class="form-label">Contraseña {{ $isEditMode ? '(opcional)' : '' }}</label><input wire:model="password" type="password" class="form-control @error('password') is-invalid @enderror">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label">Confirmar contraseña</label><input wire:model="password_confirmation" type="password" class="form-control"></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div></form>
    </div></div></div>
@script<script>$wire.on('show-user-modal', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).show());$wire.on('hide-user-modal', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).hide());</script>@endscript
</div>
