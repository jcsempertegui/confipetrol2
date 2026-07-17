<div class="page-content">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Mi perfil</h4>
            <p class="text-muted mb-0">Actualiza tus datos personales y la seguridad de tu cuenta.</p>
        </div>
        <span class="module-counter"><i class="bx bx-user me-1"></i>{{ auth()->user()->login }}</span>
    </div>

    @if(session('status') === 'password-updated')
        <div class="alert alert-success" role="alert">
            <i class="bx bx-check-circle me-1"></i>La contraseña se actualizó correctamente.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card module-form-card h-100">
                <div class="card-header">
                    <div>
                        <strong><i class="bx bx-id-card me-1"></i>Datos personales</strong>
                        <div class="form-card-subtitle">Información visible en tu cuenta y en los registros del sistema.</div>
                    </div>
                </div>

                <div class="card-body">
                    <form wire:submit="save">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="profile-name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input id="profile-name" wire:model="name" type="text" maxlength="150" autocomplete="given-name" class="form-control @error('name') is-invalid @enderror">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="profile-lastname" class="form-label">Apellidos <span class="field-optional">Opcional</span></label>
                                <input id="profile-lastname" wire:model="lastname" type="text" maxlength="150" autocomplete="family-name" class="form-control @error('lastname') is-invalid @enderror">
                                @error('lastname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="profile-email" class="form-label">Correo <span class="text-danger">*</span></label>
                                <input id="profile-email" wire:model="email" type="email" maxlength="255" autocomplete="email" class="form-control @error('email') is-invalid @enderror">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="profile-phone" class="form-label">Teléfono <span class="field-optional">Opcional</span></label>
                                <input id="profile-phone" wire:model="phone" type="text" maxlength="30" autocomplete="tel" class="form-control @error('phone') is-invalid @enderror">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save"><i class="bx bx-save me-1"></i>Guardar cambios</span>
                                <span wire:loading wire:target="save"><i class="bx bx-loader-alt bx-spin me-1"></i>Guardando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card module-form-card h-100">
                <div class="card-header">
                    <div>
                        <strong><i class="bx bx-lock-alt me-1"></i>Cambiar contraseña</strong>
                        <div class="form-card-subtitle">Usa una contraseña segura que no reutilices en otros servicios.</div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current-password" class="form-label">Contraseña actual <span class="text-danger">*</span></label>
                            <input
                                id="current-password"
                                name="current_password"
                                type="password"
                                autocomplete="current-password"
                                class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                required
                            >
                            @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="new-password" class="form-label">Nueva contraseña <span class="text-danger">*</span></label>
                            <input
                                id="new-password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                required
                            >
                            @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="new-password-confirmation" class="form-label">Confirmar contraseña <span class="text-danger">*</span></label>
                            <input
                                id="new-password-confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-key me-1"></i>Actualizar contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
