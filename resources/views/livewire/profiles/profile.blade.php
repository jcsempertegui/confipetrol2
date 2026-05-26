@push('title', 'Perfil')

<div class="page-content">
    <div class="container">
       <div class="row align-items-center mb-3 px-2">
            <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <ol class="breadcrumb mb-0 d-flex align-items-center">
                    <li class="breadcrumb-item">Inicio</li>
                    <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Perfil</li>
                </ol>
            </div>
        </div>

        <div class="main-body">
            <div class="row">
                <div class="col-lg-4">
                    <div style="box-sizing: border-box;box-shadow: 6px 8px 9px #b9b9b9" class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex flex-column align-items-center text-center">
                                @if($user->image)
                                <img src="{{ asset('storage/' . $user->image) }}" alt="Admin" class="rounded-circle p-1"
                                    width="110" height="110"
                                    style="margin: auto; border-radius: 50%;box-sizing: border-box;box-shadow: 7px 7px 10px #cbced1, -7px -7px 10px white;">
                                @else
                                <img src="{{asset('assets/images/avatar.png')}}" alt="Imagen por defecto"
                                    class="rounded-circle p-1" width="110" height="110"
                                    style="margin: auto; border-radius: 50%;box-sizing: border-box;box-shadow: 7px 7px 10px #cbced1, -7px -7px 10px white;">
                                @endif
                                <div class="mt-3">
                                    <h4>{{old('name',$user->name)}} {{old('lastname',$user->lastname)}}</h4>
                                    <p class="text-secondary mb-1"><i class="fas fa-envelope"></i>
                                        {{old('email',$user->email)}}</p>
                                    <p class="text-secondary mb-1"><i class="fas fa-id-card"></i>
                                        {{old('document',$user->document)}}</p>
                                    <hr>
                                    <h5 class="text-center">Fecha Registro</h5>
                                    <p class="text-muted font-size-sm">{{ $user->created_at->format('d/m/Y H:i:s') }}
                                    </p>
                                    <hr>
                                    <h5 class="text-center">Rol</h5>
                                    <p class="text-muted font-size-sm">{{ $user->getRoleNames()->first() }}</p>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div wire:ignore.self class="col-lg-8">
                    <div style="box-sizing: border-box;box-shadow: 6px 8px 9px #b9b9b9" class="card radius-10">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Nombre</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="text" class="form-control" wire:model="name" />
                                    @error('name')
                                    <span class="text-danger">{{'*'.$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Apellido</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="text" class="form-control" wire:model="lastname" />
                                    @error('lastname')
                                    <span class="text-danger">{{'*'.$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Cédula Identidad</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="text" class="form-control" wire:model="document" />
                                    @error('document')
                                    <span class="text-danger">{{'*'.$message}}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Correo</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="text" class="form-control" wire:model="email" />
                                    @error('email')
                                    <span class="text-danger">{{'*'.$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Contraseña</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="password" class="form-control" wire:model="password"
                                        placeholder="Contraseña" />
                                    @error('password')
                                    <span class="text-danger">{{'*'.$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Confirmar Contraseña</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="password" class="form-control" wire:model="password_confirm"
                                        placeholder="Contraseña" />
                                    @error('password_confirm')
                                    <span class="text-danger">{{'*'.$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Foto de Perfil</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="file" class="form-control" wire:model="photo"
                                        accept=".png, .jpg, .jpeg" />
                                </div>
                                <div wire:loading wire:target="photo">Cargando... <div class="progress"
                                        style="height:7px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100"
                                            style="width: 75%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 text-center">
                                    <button type="button" wire:click.prevent="Update()"
                                        class="btn btn-primary close-modal" wire:loading.attr="disabled">
                                        <span wire:loading.remove>Guardar</span>
                                        <span wire:loading>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    Livewire.on('profile-updated', (Msg, type) => {
        toast(Msg, 'success')
    });
});
</script>