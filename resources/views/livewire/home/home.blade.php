<div class="page-content">
        <div class="d-flex align-items-center mb-4">
            <div>
                <h4 class="mb-1">Panel de administración</h4>
                <p class="mb-0 text-secondary">Bienvenido, {{ auth()->user()->name }}.</p>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
            @forelse ($metrics as [$label, $value, $icon, $color])
                <div class="col">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="widgets-icons bg-light-{{ $color }} text-{{ $color }}"><i class="bx {{ $icon }}"></i></div>
                            <div class="ms-auto text-end"><h4 class="mb-0">{{ $value }}</h4><p class="mb-0 text-secondary">{{ $label }}</p></div>
                        </div>
                    </div>
                </div>
            @empty<div class="col-12"><div class="alert alert-info">Tu cuenta está activa. Utiliza el menú para acceder a los módulos que tienes asignados.</div></div>@endforelse
        </div>

        @if($canViewLogs)<div class="card radius-10">
            <div class="card-body">
                <h5 class="mb-3">Actividad reciente</h5>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Descripción</th></tr></thead>
                        <tbody>
                            @forelse ($recentLogs as $log)
                                <tr><td>{{ $log->created_at?->format('d/m/Y H:i') }}</td><td>{{ $log->user?->login ?? 'Sistema' }}</td><td>{{ $log->modulo }}</td><td>{{ $log->accion }}</td><td>{{ $log->descripcion }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-4">Todavía no hay actividad registrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>@endif
</div>
