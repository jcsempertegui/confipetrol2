<li class="nav-item dropdown dropdown-large">
    
    {{-- 1. CAMPANA (Estilo Rocker) --}}
    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        @if($has_notifications)
            <span class="alert-count">{{ count($notifications) }}</span>
        @endif
        <i class='bx bx-bell'></i>
    </a>

    {{-- 2. DROPDOWN (Estilo Rocker) --}}
    <div class="dropdown-menu dropdown-menu-end">
        <a href="javascript:;">
            <div class="msg-header">
                <p class="msg-header-title">Notificaciones</p>
                <p class="msg-header-badge">{{ count($notifications) }} Nuevas</p>
            </div>
        </a>
        
        <div class="header-notifications-list">
            @forelse($notifications as $notify)
                <a class="dropdown-item" href="javascript:;">
                    <div class="d-flex align-items-center">
                        <div class="notify bg-light-{{ $notify['color'] }} text-{{ $notify['color'] }}">
                            <i class='bx {{ $notify['icon'] }}'></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="msg-name">Alerta Sistema <span class="msg-time float-end">{{ $notify['time'] }}</span></h6>
                            <p class="msg-info">{{ $notify['message'] }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <a class="dropdown-item" href="javascript:;">
                    <div class="text-center p-3">
                        <i class='bx bx-bell-off fs-3 text-secondary'></i>
                        <p class="mb-0 text-secondary mt-2">Sin notificaciones nuevas</p>
                    </div>
                </a>
            @endforelse
        </div>
        
        <a href="javascript:;">
            <div class="text-center msg-footer">Ver todas las notificaciones</div>
        </a>
    </div>

    {{-- 
       3. MODAL DE ALERTA DE LICENCIA 
    --}}
    <div class="modal fade" id="licenseAlertModal" tabindex="-1" aria-hidden="true" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-0 border-4 border-warning rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-warning fw-bold"><i class='bx bx-error'></i> Atención Requerida</h5>
                    {{-- Le pusimos ID al boton de cerrar para ocultarlo si expira --}}
                    <button type="button" class="btn-close" id="modalCloseBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="text-center my-3">
                        <div class="mb-3">
                            <i class='bx bx-time-five text-warning' style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Su licencia está por vencer</h4>
                        <p class="fs-6 px-3 text-secondary" id="modalLicenseMessage"></p>
                        <p class="text-muted mt-3 small bg-light p-2 rounded">Por favor, contacte al administrador para renovar su suscripción.</p>
                    </div>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    {{-- Le pusimos ID al botón de acción para cambiar color y funcion --}}
                    <button type="button" id="modalActionBtn" class="btn btn-primary px-5 radius-30">Entendido</button>
                </div>
                
            </div>
        </div>
    </div>

    {{-- SCRIPT DEL MODAL LÓGICA EXPIRADA --}}
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('show-license-modal', (data) => {
                const info = data[0]; 
                
                var modalEl = document.getElementById('licenseAlertModal');
                var actionBtn = document.getElementById('modalActionBtn');
                var closeBtn = document.getElementById('modalCloseBtn');
                var msgEl = document.getElementById('modalLicenseMessage');
                
                if(modalEl) {
                    msgEl.innerText = info.message;
                    
                    // --- LÓGICA DE BLOQUEO SI EXPIRÓ ---
                    if (info.is_expired) {
                        // 1. Ocultar la X de cerrar
                        closeBtn.style.display = 'none';

                        // 2. Botón Rojo (Danger)
                        actionBtn.classList.remove('btn-primary');
                        actionBtn.classList.add('btn-danger');
                        actionBtn.innerText = "Cerrar Sesión"; // Texto más claro para la acción

                        // 3. Acción: Cerrar Sesión
                        actionBtn.onclick = function() {
                            // Buscamos el formulario de logout que ya existe en tu header
                            var logoutForm = document.getElementById('logout-form');
                            if(logoutForm) {
                                logoutForm.submit();
                            } else {
                                // Fallback por si no encuentra el ID
                                window.location.href = "{{ route('login') }}"; 
                            }
                        };
                    } else {
                        // --- ESTADO NORMAL (ADVERTENCIA) ---
                        closeBtn.style.display = 'block';
                        actionBtn.classList.add('btn-primary');
                        actionBtn.classList.remove('btn-danger');
                        actionBtn.innerText = "Entendido";
                        
                        // Acción: Cerrar Modal
                        actionBtn.onclick = function() {
                            var myModalInstance = bootstrap.Modal.getInstance(modalEl);
                            myModalInstance.hide();
                        };
                    }
                    
                    // Mover al body para evitar conflictos visuales
                    document.body.appendChild(modalEl);

                    var myModal = new bootstrap.Modal(modalEl, {
                        backdrop: 'static', // Evita cerrar clicando fuera
                        keyboard: false     // Evita cerrar con ESC
                    });
                    
                    myModal.show();
                }
            });
        });
    </script>
</li>