<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/png" />
    <!-- Loader -->
    <link href="assets/css/pace.min.css" rel="stylesheet" />
    <script src="assets/js/pace.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-extended.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <link href="assets/css/icons.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Recuperar Contraseña - Sistema</title>
</head>

<body>
    <!-- Wrapper -->
    <div class="wrapper">
        <div class="authentication-forgot d-flex align-items-center justify-content-center">
            <div class="card forgot-box">
                <div class="card-body">
                    <div class="p-3">
                        <!-- Icono -->
                        <div class="text-center">
                            <img src="assets/images/logo.png" width="100" alt="Olvidé contraseña" />
                        </div>
                        <!-- Título -->
                        <h4 class="mt-4 font-weight-bold text-center">¿Olvidaste tu contraseña?</h4>
                        <p class="text-muted text-center">
                            Ingresa tu correo electrónico registrado para restablecer tu contraseña.
                        </p>
                        <!-- Formulario -->
                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf
                            <div class="my-4">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    placeholder="ejemplo@usuario.com" value="{{ old('email') }}" autofocus>
                                @error('email')
                                <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row mb-3">
                                <a href="{{ route('login') }}" class="text-decoration-none">
                                    <i class='bx bx-arrow-back me-1'></i>Volver al inicio de sesión
                                </a>
                            </div>
                            <!-- Botones -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Enviar enlace</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Wrapper -->

    <!-- SweetAlert2 Alert -->
    @if (session('status'))
    <script>
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: '{{ session("status") }}',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Aceptar'
    });
    </script>
    @endif
</body>

</html>