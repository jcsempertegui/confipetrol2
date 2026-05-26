<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/png" />
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
    <title>Restablecer Contraseña</title>
</head>

<body>
    <div class="wrapper">
        <div class="authentication-reset-password d-flex align-items-center justify-content-center">

            <div class="card forgot-box">
                <div class="card-body">
                    <div class="p-3">
                        <!-- Logo -->
                        <div class="text-center">
                            <img src="{{ asset('assets/images/logo.png') }}" width="100" alt="logo" />
                        </div>
                        <!-- Título -->
                        <h4 class="mt-4 font-weight-bold text-center">Generar nueva contraseña</h4>
                        <p class="text-muted text-center">
                            Hemos recibido tu solicitud para restablecer la contraseña. Ingresa tu nueva contraseña.
                        </p>

                        <!-- FORMULARIO -->
                        <form method="POST" action="{{ route('password.store') }}">
                            @csrf

                            <!-- Token -->
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            <!-- Email -->
                            <div class="mb-3 mt-4 d-none">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email', $request->email) }}" required autofocus
                                    autocomplete="username" realoandy>
                                @error('email')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Nueva contraseña -->
                            <div class="mb-3">
                                <label class="form-label">Nueva contraseña</label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Ingresa nueva contraseña" required autocomplete="new-password">
                                @error('password')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Confirmar contraseña -->
                            <div class="mb-3">
                                <label class="form-label">Confirmar contraseña</label>
                                <input type="password" name="password_confirmation" class="form-control"
                                    placeholder="Confirma la contraseña" required autocomplete="new-password">
                                @error('password_confirmation')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <!-- Botón enviar -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                            </div>

                            <!-- Enlace volver -->
                            <div class="mt-3">
                                <a href="{{ route('login') }}" class="text-decoration-none">
                                    <i class='bx bx-arrow-back me-1'></i> Volver al inicio de sesión
                                </a>
                            </div>
                        </form>
                        <!-- FIN FORMULARIO -->

                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>