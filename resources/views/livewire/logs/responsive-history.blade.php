<div class="audit-responsive-history">
    @forelse($logs as $log)
        @php($changes = $log->changes())
        <article class="audit-event-card">
            <div>
                <span class="audit-field-label">Fecha y hora</span>
                <strong>{{ $log->created_at->format('d/m/Y') }}</strong>
                <span class="d-block text-muted">{{ $log->created_at->format('H:i:s') }}</span>
            </div>
            <div>
                <span class="audit-field-label">Responsable</span>
                <div class="d-flex align-items-center">
                    <span class="audit-avatar">{{ strtoupper(substr($log->actor_login ?? $log->user?->login ?? 'S', 0, 1)) }}</span>
                    <div>
                        <strong>{{ $log->actor_login ?? $log->user?->login ?? 'Sistema' }}</strong>
                        <div class="small text-muted">IP {{ $log->ip ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div>
                <span class="audit-field-label">Evento</span>
                <div><span class="badge {{ match($log->accion){'CREAR'=>'bg-success','EDITAR'=>'bg-primary','ELIMINAR'=>'bg-danger','RESTAURAR'=>'bg-warning text-dark',default=>'bg-secondary'} }}">{{ $accionOptions[$log->accion] ?? str_replace('_', ' ', $log->accion) }}</span></div>
                <small class="text-muted">{{ $moduloOptions[$log->modulo] ?? $log->modulo }}</small>
            </div>
            <div>
                <span class="audit-field-label">Registro afectado</span>
                <strong class="d-block">{{ $log->descripcion ?: 'Sin descripción' }}</strong>
                @if($log->modelo_id)<small class="text-muted">ID: {{ $log->modelo_id }}</small>@endif
            </div>
            <div class="audit-event-change">
                <span class="audit-field-label">Cambios detectados</span>
                @if(count($changes))
                    @php($first = $changes[0])
                    <div class="small mb-2">
                        <strong>{{ ucfirst(str_replace('_', ' ', $first['field'])) }}:</strong>
                        <span class="text-danger">{{ filled($first['before']) ? $first['before'] : 'Vacío' }}</span>
                        <i class="bx bx-right-arrow-alt"></i>
                        <span class="text-success">{{ filled($first['after']) ? $first['after'] : 'Vacío' }}</span>
                    </div>
                    <button wire:click="toggleDetails({{ $log->id }})" type="button" class="btn btn-sm {{ $expandedLogs[$log->id] ?? false ? 'btn-primary' : 'btn-outline-primary' }} audit-change-button">
                        <i class="bx {{ $expandedLogs[$log->id] ?? false ? 'bx-chevron-up' : 'bx-detail' }}"></i>
                        {{ $expandedLogs[$log->id] ?? false ? 'Ocultar detalle' : 'Ver todos los cambios ('.count($changes).')' }}
                    </button>
                @else
                    <span class="text-muted small">Evento sin modificación de datos.</span>
                @endif
            </div>
            @if(count($changes) && ($expandedLogs[$log->id] ?? false))
                <div class="audit-event-detail">
                    <div class="fw-semibold mb-2">Detalle completo</div>
                    @include('livewire.logs.change-list', ['changes' => $changes])
                </div>
            @endif
        </article>
    @empty
        <div class="module-empty">
            <i class="bx bx-search-alt"></i>
            No se encontraron eventos con los filtros seleccionados.
        </div>
    @endforelse
</div>
