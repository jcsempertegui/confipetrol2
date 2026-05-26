@push('title', 'Usuarios')

<div class="page-content"
    style="height: calc(100vh - 60px); overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0;">
    <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Administracion</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Usuarios</li>
            </ol>
            @can('crear-usuario')
            @include('components.tools.buttonRegister')
            @endcan
        </div>
    </div>

    <div class="card" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2" style="flex-shrink: 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-user"></i>
                <span class="fw-semibold">Listar Usuarios</span>
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
                @include('components.tools.searchbox')
            </div>

            <div class="table-responsive" style="flex: 1; min-height: 0; overflow: auto;">
                <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>USUARIO</th>
                            <th>NOMBRE</th>
                            <th>CI</th>
                            <th>CORREO</th>
                            <th>TELÉFONO</th>
                            <th>SUCURSAL</th>
                            <th>ROL</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($users->isEmpty())
                        <tr>
                            <td colspan="10" class="text-center">No se encontraron registros.</td>
                        </tr>
                        @else
                        @foreach($users as $index => $user)
                        <tr>
                            <td>{{ $startCount - $index }}</td>
                            <td>{{$user->login ?: 'S/N'}} </td>
                            <td>{{$user->name ?: 'S/N'}} {{$user->lastname}}</td>
                            <td>{{$user->document ?: 'S/N'}}</td>
                            <td>{{$user->email ?: 'S/N'}}</td>
                            <td>{{$user->phone ?: 'S/N'}}</td>
                            <td>{{ $user->branche?->name ?: 'S/N' }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                {{$role->name}}
                                @endforeach
                            </td>
                            <td>
                                @if($user->status == 1)
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
                                    @can('editar-usuario')
                                    <a href="javascript:;" wire:click="edit({{ $user->id }})" data-bs-toggle="modal"
                                        data-bs-target="#theModal" class="btn-action-primary"><i
                                            class="bx bxs-edit-alt"></i></a>
                                    @endcan

                                    @can('editar-usuario')
                                    <a href="javascript:;" wire:click="openPasswordModal({{ $user->id }})"
                                        data-bs-toggle="modal" data-bs-target="#passwordModal"
                                        class="btn-action-warning ms-1"><i class="bx bxs-lock"></i></a>
                                    @endcan

                                    @if($user->status == 1)
                                    @can('eliminar-usuario')
                                    <a href="javascript:;" onclick="confirmDelete({{ $user->id }}, 'delete')"
                                        class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                    @endcan
                                    @else
                                    @can('restaurar-usuario')
                                    <a href="javascript:;" onclick="confirmDelete({{ $user->id }}, 'restore')"
                                        class="btn-action-success ms-1"><i class="bx bx-refresh"></i></a>
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

            <div style="flex-shrink: 0; padding-top: 0.4rem;">
                {{ $users->links() }}
            </div>

            <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" aria-labelledby="theModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="theModalLabel">
                                <i class="bx bx-user"></i>
                                {{ $isEditMode ? 'ACTUALIZAR USUARIO' : 'REGISTRAR USUARIO' }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 p-2">
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Nombre</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="name" class="form-control"
                                            placeholder="Nombre" maxlength="30">
                                    </div>
                                    @error('name')<span class="text-danger er">{{$message}}</span> @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Apellido</label>
                                    <div class="input-group">
                                        <input type="text" wire:model.lazy="lastname" class="form-control"
                                            placeholder="Apellidos" maxlength="30">
                                    </div>
                                    @error('lastname')<span class="text-danger er">{{$message}}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Login</label>
                                    <div class="input-group">
                                        <input type="text" wire:model="login" class="form-control" placeholder="login"
                                            readonly>
                                    </div>
                                    @error('login')<span class="text-danger er">{{$message}}</span>@enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Correo</label>
                                    <div class="input-group">
                                        <input type="email" wire:model.lazy="email" class="form-control"
                                            placeholder="correo">
                                    </div>
                                    @error('email')<span class="text-danger er">{{$message}}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Cedula de Identidad</label>
                                    <div class="input-group">
                                        <input type="number" wire:model.lazy="document" class="form-control"
                                            placeholder="Cedula de Identidad" max="9">
                                    </div>
                                    @error('document')<span class="text-danger er">{{$message}}</span>
                                    @enderror
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
                                    <label>Sucursal</label>
                                    <div class="input-group">
                                        <select wire:model.lazy="branch_id" class="form-select">
                                            <option value="" selected>Seleccionar</option>
                                            @foreach($branches as $branche)
                                            <option value="{{$branche->id}}" selected>{{$branche->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('branch_id')<span class="text-danger er">{{$message}}</span> @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 mb-2">
                                    <label>Rol</label>
                                    <div class="input-group">
                                        <select wire:model.lazy="role" class="form-select">
                                            <option value="" selected>Seleccionar</option>
                                            @foreach($roles as $role)
                                            <option value="{{$role->name}}" selected>{{$role->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('role')<span class="text-danger er">{{$message}}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
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

            <div wire:ignore.self class="modal fade" id="passwordModal" tabindex="-1"
                aria-labelledby="passwordModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="passwordModalLabel">
                                <i class="bx bxs-lock"></i>
                                CAMBIAR CONTRASEÑA
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 p-2">
                                <div class="col-12 mb-3">
                                    <label class="fw-bold">Nombre:</label>
                                    <p class="mb-0">{{ $password_name }} {{ $password_lastname }}</p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="fw-bold">Login:</label>
                                    <p class="mb-0">{{ $password_login }}</p>
                                </div>
                                <div class="col-12 mb-2">
                                    <label>Nueva Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" wire:model.defer="new_password" class="form-control"
                                            placeholder="Ingrese la nueva contraseña">
                                    </div>
                                    @error('new_password')<span class="text-danger er">{{$message}}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                                wire:click="resetPasswordFields">
                                Cancelar
                            </button>
                            <button type="button" wire:click.prevent="changePassword()" class="btn btn-primary"
                                wire:loading.attr="disabled" wire:target="changePassword">
                                <span wire:loading.remove wire:target="changePassword">
                                    Cambiar Contraseña
                                </span>
                                <span wire:loading wire:target="changePassword">
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
    Livewire.on('userStoreOrUpdate', (data) => {
        if(Array.isArray(data)){
             toast(data[0], data[1]);
        } else {
             console.log(data);
        }
    });

    Livewire.on('userStoreOrUpdate', (event) => {
        const msg = event[0];
        const type = event[1] || 'success';
        if(type !== 'error'){
            $('#theModal').modal('hide');
        }
        toast(msg, type);
    });

    Livewire.on('userDeleted', (event) => {
        const msg = event[0];
        const type = event[1] || 'success';
        toast(msg, type);
    });

    Livewire.on('passwordChanged', (event) => {
        const msg = event[0];
        const type = event[1] || 'success';
        $('#passwordModal').modal('hide');
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