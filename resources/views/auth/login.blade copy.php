<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mastec Pos - Login</title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        background: #f8fafc;
        display: flex;
        overflow: hidden;
    }

    /* Left Side - Hero Section */
    .hero-section {
        flex: 1;
        background: linear-gradient(135deg, #fc0038 0%, #c41e3a 100%);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .hero-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image:
            radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
            linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.02) 50%, transparent 70%);
        background-size: 400px 400px, 600px 600px, 200px 200px;
        animation: heroFloat 20s ease-in-out infinite;
    }

    @keyframes heroFloat {

        0%,
        100% {
            transform: translateX(0) translateY(0);
        }

        25% {
            transform: translateX(-20px) translateY(-15px);
        }

        50% {
            transform: translateX(15px) translateY(-25px);
        }

        75% {
            transform: translateX(-10px) translateY(10px);
        }
    }

    /* Geometric shapes for professional look */
    .hero-shape {
        position: absolute;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
    }

    .hero-shape:nth-child(2) {
        top: 15%;
        right: 10%;
        width: 120px;
        height: 120px;
        border-radius: 20px;
        animation: shapeFloat 8s ease-in-out infinite;
    }

    .hero-shape:nth-child(3) {
        bottom: 20%;
        left: 8%;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        animation: shapeFloat 10s ease-in-out infinite reverse;
    }

    .hero-shape:nth-child(4) {
        top: 40%;
        left: 5%;
        width: 60px;
        height: 100px;
        border-radius: 30px;
        animation: shapeFloat 12s ease-in-out infinite;
    }

    @keyframes shapeFloat {

        0%,
        100% {
            transform: translateY(0) rotate(0deg);
        }

        50% {
            transform: translateY(-30px) rotate(180deg);
        }
    }

    .hero-content {
        text-align: center;
        z-index: 10;
        color: white;
        max-width: 500px;
        padding: 2rem;
    }

    .hero-icon {
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 25px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        margin-bottom: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(20px);
        animation: iconPulse 3s ease-in-out infinite;
    }

    @keyframes iconPulse {

        0%,
        100% {
            transform: scale(1);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.2);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 0 50px rgba(255, 255, 255, 0.3);
        }
    }

    .hero-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        line-height: 1.2;
    }

    .hero-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .hero-features {
        list-style: none;
        text-align: left;
        max-width: 300px;
        margin: 0 auto;
    }

    .hero-features li {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        font-size: 0.95rem;
        opacity: 0.9;
    }

    .hero-features li i {
        margin-right: 0.8rem;
        font-size: 1.1rem;
        width: 20px;
    }

    /* Right Side - Login Form */
    .login-section {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        position: relative;
    }

    .login-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background:
            radial-gradient(circle at 100% 0%, rgba(252, 0, 56, 0.02) 0%, transparent 50%),
            radial-gradient(circle at 0% 100%, rgba(252, 0, 56, 0.01) 0%, transparent 50%);
        pointer-events: none;
    }

    .login-container {
        width: 100%;
        max-width: 450px;
        padding: 2rem;
        z-index: 10;
        position: relative;
    }

    .login-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .company-logo {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #fc0038, #ff4d6d);
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
        margin-bottom: 1.5rem;
        box-shadow:
            0 10px 30px rgba(252, 0, 56, 0.2),
            0 0 0 1px rgba(252, 0, 56, 0.1);
        transition: transform 0.3s ease;
    }

    .company-logo:hover {
        transform: translateY(-3px) scale(1.05);
    }

    .login-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 0.5rem;
    }

    .login-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        font-weight: 400;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        letter-spacing: 0.025em;
    }

    .input-container {
        position: relative;
    }

    .form-input {
        width: 100%;
        padding: 0.875rem 1rem 0.875rem 3rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        background: #ffffff;
        transition: all 0.2s ease;
        outline: none;
        font-weight: 400;
    }

    .form-input:focus {
        border-color: #fc0038;
        box-shadow: 0 0 0 3px rgba(252, 0, 56, 0.05);
        background: #ffffff;
    }

    .form-input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
        transition: color 0.2s ease;
    }

    .form-input:focus+.input-icon {
        color: #fc0038;
    }

    /* Password field */
    .password-toggle {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        font-size: 1rem;
        transition: color 0.2s ease;
        padding: 0.5rem;
        border-radius: 6px;
    }

    .password-toggle:hover {
        color: #fc0038;
        background: rgba(252, 0, 56, 0.05);
    }

    .forgot-password {
        text-align: right;
        margin-bottom: 1.5rem;
    }

    .forgot-password a {
        color: #fc0038;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: opacity 0.2s ease;
    }

    .forgot-password a:hover {
        opacity: 0.8;
        text-decoration: underline;
    }

    .login-button {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #fc0038, #dc2626);
        border: none;
        border-radius: 12px;
        color: white;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(252, 0, 56, 0.25);
        letter-spacing: 0.025em;
    }

    .login-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(252, 0, 56, 0.3);
    }

    .login-button:active {
        transform: translateY(0);
    }

    .error-message {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.5rem;
        font-weight: 500;
    }

    .loading {
        display: none;
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top: 2px solid white;
        animation: spin 1s linear infinite;
        margin-left: 0.5rem;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Security badge */
    .security-info {
        margin-top: 2rem;
        padding: 1rem;
        background: rgba(34, 197, 94, 0.05);
        border: 1px solid rgba(34, 197, 94, 0.1);
        border-radius: 10px;
        text-align: center;
    }

    .security-info p {
        font-size: 0.8rem;
        color: #059669;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .security-info i {
        margin-right: 0.5rem;
        font-size: 0.9rem;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        body {
            flex-direction: column;
        }

        .hero-section {
            min-height: 40vh;
            flex: none;
        }

        .hero-content {
            padding: 1.5rem;
        }

        .hero-title {
            font-size: 2rem;
        }

        .hero-features {
            display: none;
        }

        .login-section {
            flex: none;
            min-height: 60vh;
        }
    }

    @media (max-width: 768px) {
        .hero-section {
            min-height: 35vh;
        }

        .hero-title {
            font-size: 1.8rem;
        }

        .hero-subtitle {
            font-size: 1rem;
        }

        .login-container {
            padding: 1.5rem;
            max-width: 400px;
        }

        .hero-shape {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .login-container {
            padding: 1rem;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .company-logo {
            width: 60px;
            height: 60px;
            font-size: 24px;
        }
    }
    </style>
</head>

<body>
    <!-- Left Hero Section -->
    <div class="hero-section">
        <div class="hero-background"></div>
        <div class="hero-shape"></div>
        <div class="hero-shape"></div>
        <div class="hero-shape"></div>
        <div class="hero-shape"></div>

        <div class="hero-content">
            <div class="hero-icon">
                <i class="fas fa-cash-register"></i>
            </div>
            <h1 class="hero-title">Sistema POS Profesional</h1>
            <p class="hero-subtitle">Gestiona tu negocio de manera eficiente con nuestra plataforma integral de punto de
                venta</p>

            <ul class="hero-features">
                <li><i class="fas fa-check"></i> Ventas en tiempo real</li>
                <li><i class="fas fa-chart-line"></i> Reportes avanzados</li>
                <li><i class="fas fa-inventory"></i> Control de inventario</li>
                <li><i class="fas fa-users"></i> Gestión de clientes</li>
            </ul>
        </div>
    </div>

    <!-- Right Login Section -->
    <div class="login-section">
        <div class="login-container">
            <!-- Header -->
            <div class="login-header">
                <img src="assets/images/login.png" class="company-logo" width="70" alt="Mastec POS" />
                <h1 class="login-title">Mastec POS</h1>
                <p class="login-subtitle">Acceso al sistema de ventas</p>
            </div>

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="login">Usuario</label>
                    <div class="input-container">
                        <input type="text" id="login" name="login" class="form-input" placeholder="Ingresa tu usuario"
                            required autofocus autocomplete="username" value="{{ old('login') }}">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="input-container">
                        <input type="password" id="password" name="password" class="form-input"
                            placeholder="Ingresa tu contraseña" required autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    @error('login')
                    <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="forgot-password">
                    @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">
                        ¿Has olvidado tu contraseña?
                    </a>
                    @endif
                </div>

                <button type="submit" class="login-button" id="loginBtn">
                    <span>Iniciar sesión</span>
                    <div class="loading" id="loading"></div>
                </button>
            </form>

            <!-- Security Info -->
            <div class="security-info">
                <p><i class="fas fa-shield-alt"></i> Conexión segura y encriptada</p>
            </div>
        </div>
    </div>

    <script>
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        if (type === 'password') {
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    });

    // Form enhancement
    const formInputs = document.querySelectorAll('.form-input');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.parentElement.classList.remove('focused');
            }
        });
    });
    </script>
</body>

</html>