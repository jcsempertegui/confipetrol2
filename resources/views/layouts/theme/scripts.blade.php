<script src="{{asset('assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('assets/js/jquery.min.js')}}"></script>

<script src="{{asset('assets/plugins/simplebar/js/simplebar.min.js')}}"></script>
<script src="{{asset('assets/plugins/metismenu/js/metisMenu.min.js')}}"></script>
<script src="{{asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js')}}"></script>
<script src="{{asset('assets/plugins/chartjs/js/Chart.min.js')}}"></script>
<script src="{{asset('assets/plugins/chartjs/js/Chart.extension.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{asset('assets/js/ckeditor.js')}}"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{asset('assets/plugins/select2/js/select2-custom.js')}}"></script>

<script src="{{asset('assets/plugins/flatpickr/flatpickr.js')}}"></script>
<script src="{{asset('assets/plugins/flatpickr/flatpickr-custom.js')}}"></script>

<script src="{{asset('assets/js/app.js')}}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>

<script>
    // 1. Configuración Base del Toast (Notificación pequeña esquina superior)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // Función global por si necesitas llamarla manualmente en JS
    window.toast = (msg, type = 'success') => {
        Toast.fire({
            icon: type,
            title: msg
        });
    }

    // ============================================================
    //  INTERCEPCIÓN AUTOMÁTICA DE MENSAJES (LARAVEL SESSION)
    // ============================================================
    // Esto mostrará automáticamente las alertas cuando redirijas desde el controlador
    
    @if(session('success'))
        Toast.fire({
            icon: 'success',
            title: "{{ session('success') }}"
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: "{{ session('error') }}",
            confirmButtonColor: '#f44336'
        });
    @endif

    // ESTE ES EL QUE MANEJA TU ALERTA DE SESIÓN CERRADA
    @if(session('warning'))
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: "{{ session('warning') }}",
            confirmButtonText: "Entendido",
            confirmButtonColor: '#ffc107',
            customClass: {
                confirmButton: 'btn btn-warning px-5 radius-30 text-white',
                popup: 'rounded-4 p-4 border-0'
            }
        });
    @endif

    @if(session('info'))
        Toast.fire({
            icon: 'info',
            title: "{{ session('info') }}"
        });
    @endif

    // ============================================================
    //  EVENTOS DE LIVEWIRE (Para cuando uses $this->dispatch)
    // ============================================================
    document.addEventListener('livewire:init', () => {
        
        // Para mensajes de éxito simples: $this->dispatch('msg', 'Guardado con éxito');
        Livewire.on('msg', (message) => {
            Toast.fire({
                icon: 'success',
                title: message
            });
        });

        // Para mensajes de error: $this->dispatch('msg-error', 'Algo salió mal');
        Livewire.on('msg-error', (message) => {
            Toast.fire({
                icon: 'error',
                title: message
            });
        });

        // Para cerrar modales automáticamente: $this->dispatch('close-modal');
        Livewire.on('close-modal', () => {
            $('.modal').modal('hide'); // Cierra cualquier modal de bootstrap abierto
        });
    });

    // ============================================================
    //  UTILIDADES DEL SISTEMA (Recarga y Modo Oscuro)
    // ============================================================

    // Recarga inteligente al volver atrás
    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type == 2)) {
            location.reload();
        }
    });

    // Lógica de Modo Oscuro
    function toggleDarkMode() {
        var element = document.body;
        element.classList.toggle("dark-mode");
        
        var isDark = element.classList.contains("dark-mode");
        localStorage.setItem("theme", isDark ? "dark-mode" : "light");
        
        updateDarkModeIcon(isDark);
    }

    function updateDarkModeIcon(isDark) {
        var btn = document.getElementById('darkModeBtn');
        if(btn) {
            var icon = btn.querySelector('i');
            if(isDark) {
                icon.className = 'bx bx-sun'; 
                btn.style.color = '#ffc107'; 
            } else {
                icon.className = 'bx bx-moon'; 
                btn.style.color = ''; 
            }
        }
    }

    // Inicializar Modo Oscuro
    document.addEventListener("DOMContentLoaded", function() {
        if (localStorage.getItem("theme") === "dark-mode") {
            updateDarkModeIcon(true);
        }
    });
</script>

@livewireScripts
@stack('scripts')