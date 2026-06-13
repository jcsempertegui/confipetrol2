<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONFIPETROL - Login</title>
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
        background: linear-gradient(135deg, #f5f5f5ff 0%, #c1d4ff1c 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    /* Animated background elements */
    .bg-shapes {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 1;
    }

    .shape {
        position: absolute;
        opacity: 0.1;
        animation: float 6s ease-in-out infinite;
    }


    .shape:nth-child(1) {
        top: 12%;
        left: 8%;
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #0057a7, #1a73c8);
        border-radius: 12px;
        animation-delay: 0s;
    }

    .shape:nth-child(2) {
        top: 8%;
        right: 15%;
        width: 80px;
        height: 80px;
        background: linear-gradient(45deg, #0057a7, #1a6dc4);
        border-radius: 50%;
        animation-delay: 2s;
    }

    .shape:nth-child(3) {
        bottom: 20%;
        left: 12%;
        width: 60px;
        height: 120px;
        background: linear-gradient(180deg, #0057a7 0%, rgba(0, 87, 167, 0.3) 100%);
        border-radius: 30px;
        animation-delay: 1s;
    }

    .shape:nth-child(4) {
        top: 55%;
        right: 10%;
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #0057a7, #4d94d4);
        border-radius: 16px;
        animation-delay: 3.5s;
    }

    .shape:nth-child(5) {
        bottom: 35%;
        right: 30%;
        width: 4px;
        height: 80px;
        background: linear-gradient(180deg, transparent 0%, #0057a7 50%, transparent 100%);
        border-radius: 2px;
        animation-delay: 2.5s;
    }

    .shape:nth-child(6) {
        top: 30%;
        left: 5%;
        width: 40px;
        height: 40px;
        background: radial-gradient(circle, #0057a7 30%, transparent 70%);
        border-radius: 50%;
        animation-delay: 4s;
    }

    .shape:nth-child(7) {
        top: 65%;
        left: 25%;
        width: 90px;
        height: 30px;
        background: linear-gradient(90deg, transparent, #0057a7, transparent);
        border-radius: 15px;
        animation-delay: 5s;
    }

    .shape:nth-child(8) {
        top: 15%;
        left: 35%;
        width: 150px;
        height: 150px;
        background-image:
            linear-gradient(rgba(0, 87, 167, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 87, 167, 0.05) 1px, transparent 1px);
        background-size: 25px 25px;
        opacity: 0.6;
        animation-delay: 6s;
    }

    .shape:nth-child(9) {
        bottom: 45%;
        right: 45%;
        width: 25px;
        height: 25px;
        background: #0057a7;
        border-radius: 4px;
        animation-delay: 7s;
    }

    .shape:nth-child(10) {
        top: 25%;
        right: 25%;
        width: 35px;
        height: 35px;
        background: linear-gradient(45deg, #0057a7, transparent);
        border-radius: 6px;
        animation-delay: 1.5s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
            opacity: 0.06;
        }

        25% {
            transform: translateY(-15px) rotate(90deg);
            opacity: 0.08;
        }

        50% {
            transform: translateY(-30px) rotate(180deg);
            opacity: 0.04;
        }

        75% {
            transform: translateY(-15px) rotate(270deg);
            opacity: 0.08;
        }
    }


    /* Main container */
    .login-container {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 420px;
        margin: 0 20px;
    }

    /* 3D Card */
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 40px;
        box-shadow:
            0 25px 50px -12px rgba(0, 0, 0, 0.25),
            0 0 0 1px rgba(255, 255, 255, 0.3);
        transform-style: preserve-3d;
        transition: all 0.3s ease;
        animation: cardFloat 6s ease-in-out infinite;
    }


    @keyframes cardFloat {

        0%,
        100% {
            transform: translateY(0px) rotateX(0deg);
        }

        50% {
            transform: translateY(-12px) rotateX(2deg);
        }
    }

    .login-card:hover {
        transform: translateY(-5px) rotateX(5deg);
        box-shadow:
            0 30px 60px rgba(0, 0, 0, 0.2),
            0 15px 30px rgba(0, 0, 0, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }

    /* Logo */
    .logo-container {
        text-align: center;
        margin-bottom: 30px;
    }

    .logo {
        padding: 10px;
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #0057a7, #1a73c8);
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        color: white;
        margin-bottom: 20px;
        box-shadow:
            0 10px 30px rgba(0, 87, 167, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
        animation: logoGlow 2s ease-in-out infinite alternate;
    }

    @keyframes logoGlow {
        0% {
            box-shadow: 0 10px 20px rgba(0, 87, 167, 0.3), 0 5px 10px rgba(0, 87, 167, 0.2);
        }

        100% {
            box-shadow: 0 15px 30px rgba(0, 87, 167, 0.4), 0 8px 15px rgba(0, 87, 167, 0.3);
        }
    }

    .logo:hover {
        transform: rotateX(0deg) rotateY(0deg) scale(1.1);
    }

    .brand-text {
        margin-top: 1rem;
    }

    .brand-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 8px;
    }

    .brand-subtitle {
        color: #6b7280;
        font-size: 0.9rem;
        font-weight: 400;
    }

    /* Form styles */
    .form-group {
        margin-bottom: 24px;
        position: relative;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        transition: color 0.2s ease;
    }

    .input-container {
        position: relative;
    }

    .form-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: 2px solid rgba(229, 231, 235, 0.8);
        border-radius: 16px;
        font-size: 1rem;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        outline: none;
    }

    .form-input:focus {
        border-color: #0057a7;
        box-shadow: 0 0 0 4px rgba(0, 87, 167, 0.1);
        background: rgba(255, 255, 255, 0.95);
        transform: translateZ(10px);
    }

    .form-input::placeholder {
        color: #9ca3af;
    }

    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .form-input:focus+.input-icon {
        color: #0057a7;
        transform: translateY(-50%) scale(1.1);
    }

    /* Password field */
    .password-container {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        padding: 0.5rem;
        border-radius: 8px;
    }

    .password-toggle:hover {
        color: #0057a7;
        transform: translateY(-50%) scale(1.1);
    }

    .forgot-password {
        text-align: right;
        margin-bottom: 2rem;
    }

    .forgot-password a {
        color: #0057a7;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s ease;
        position: relative;
    }

    .forgot-password a::after {
        content: '';
        position: absolute;
        width: 0;
        height: 1px;
        bottom: -2px;
        left: 0;
        background: #0057a7;
        transition: width 0.3s ease;
    }

    .forgot-password a:hover::after {
        width: 100%;
    }

    .login-button {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #0057a7, #1a73c8);
        border: none;
        border-radius: 16px;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow:
            0 10px 30px rgba(0, 87, 167, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
    }

    .login-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .login-button:hover {
        transform: translateY(-2px) translateZ(10px);
        box-shadow:
            0 15px 35px rgba(0, 87, 167, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }

    .login-button:hover::before {
        left: 100%;
    }

    .login-button:active {
        transform: translateY(0) translateZ(5px);
    }

    .error-message {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.5rem;
        opacity: 0;
        transform: translateY(-10px);
        animation: fadeInError 0.3s ease forwards;
    }

    .alert-box {
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 0.875rem;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        animation: fadeInError 0.3s ease forwards;
    }
    .alert-warning {
        background-color: #fff4f5;
        border: 1px solid #ffe4e6;
        color: #9f1239;
    }
    .alert-info {
        background-color: #eff6ff;
        border: 1px solid #dbeafe;
        color: #1e40af;
    }
    .alert-icon {
        margin-top: 2px;
    }

    @keyframes fadeInError {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Loading animation */
    .loading {
        display: none;
        width: 20px;
        height: 20px;
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

    /* Responsive */
    @media (max-width: 480px) {
        .login-card {
            padding: 2rem;
            margin: 1rem;
        }

        .logo {
            width: 60px;
            height: 60px;
        }

        .logo i {
            font-size: 1.5rem;
        }
    }

    /* Form focus states */
    .form-group.focused .form-label {
        color: #0057a7;
    }

    .form-group.focused .input-container {
        transform: translateZ(5px);
    }
    </style>
</head>

<body>
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <img src="assets/images/login.png" class="logo" width="60" alt="" />
                <h1 class="brand-name">CONFIPETROL</h1>
                <p class="brand-subtitle">Inicia sesión en tu cuenta</p>
            </div>

            <form id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf

                @if (session('status'))
                    <div class="alert-box alert-info">
                        <i class="fas fa-info-circle alert-icon"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert-box alert-warning">
                        <i class="fas fa-exclamation-triangle alert-icon"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
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

    
                <button type="submit" class="login-button" id="loginBtn">
                    <span>Iniciar sesión</span>
                    <div class="loading" id="loading"></div>
                </button>
            </form>
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
    </script>
</body>

</html>