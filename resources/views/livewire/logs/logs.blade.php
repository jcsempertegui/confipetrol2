<div class="page-content audit-page">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div><h4 class="mb-1">Trazabilidad del sistema</h4><p class="text-muted mb-0">Revisa quién hizo cada acción y qué valores fueron modificados.</p></div>
        <span class="badge bg-light text-dark border px-3 py-2"><i class="bx bx-calendar me-1"></i> Últimos 7 días</span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="card h-100 mb-0"><div class="card-body py-3"><small class="text-muted">Eventos encontrados</small><div class="fs-4 fw-bold">{{ $summary['total'] }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card h-100 mb-0"><div class="card-body py-3"><small class="text-muted">Modificaciones</small><div class="fs-4 fw-bold text-primary">{{ $summary['changes'] }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card h-100 mb-0"><div class="card-body py-3"><small class="text-muted">Usuarios involucrados</small><div class="fs-4 fw-bold text-success">{{ $summary['users'] }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card h-100 mb-0"><div class="card-body py-3"><small class="text-muted">Periodo consultado</small><div class="fw-semibold mt-1">{{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('d/m/Y') : '-' }} — {{ $toDate ? \Carbon\Carbon::parse($toDate)->format('d/m/Y') : '-' }}</div></div></div></div>
    </div>

    <div class="card"><div class="card-body">
        <div class="d-flex align-items-center mb-3"><i class="bx bx-filter-alt fs-4 text-primary me-2"></i><div><h5 class="mb-0">Filtros de búsqueda</h5><small class="text-muted">Combina los filtros para localizar una acción específica.</small></div></div>
        <form wire:submit="filterLogs"><div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4"><label class="form-label">Buscar en el historial</label><div class="input-group"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.400ms="searchTerm" class="form-control" placeholder="Usuario, producto, código o valor..."></div></div>
            <div class="col-6 col-md-3 col-lg-2"><label class="form-label">Desde</label><input wire:model="fromDate" type="date" class="form-control @error('fromDate') is-invalid @enderror">@error('fromDate')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-6 col-md-3 col-lg-2"><label class="form-label">Hasta</label><input wire:model="toDate" type="date" class="form-control @error('toDate') is-invalid @enderror">@error('toDate')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-6 col-md-3 col-lg-2"><label class="form-label">Módulo</label><select wire:model.live="filter_modulo" class="form-select">@foreach($moduloOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-6 col-md-3 col-lg-2"><label class="form-label">Acción</label><select wire:model.live="filter_accion" class="form-select">@foreach($accionOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-12 d-flex justify-content-end gap-2"><button type="button" wire:click="clearFilters" class="btn btn-light border"><i class="bx bx-reset"></i> Limpiar</button><button class="btn btn-primary"><i class="bx bx-search-alt"></i> Consultar periodo</button></div>
        </div></form>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="mb-0">Historial de actividad</h5><small class="text-muted">Pulsa “Ver cambios” para revisar todos los valores.</small></div><div class="d-flex align-items-center gap-2"><label class="text-muted small text-nowrap">Mostrar</label><select wire:model.live="perPage" class="form-select form-select-sm" style="width:80px">@foreach($perPageOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div></div>

        @include('livewire.logs.responsive-history')
        <div class="table-responsive d-none d-xl-block"><table class="table table-hover align-middle audit-table"><thead><tr><th>Fecha</th><th>Responsable</th><th>Evento</th><th>Registro afectado</th><th>Resumen del cambio</th><th>Acciones</th></tr></thead><tbody>
        @forelse($logs as $log)@php($changes = $log->changes())<tr>
            <td class="text-nowrap"><strong>{{ $log->created_at->format('d/m/Y') }}</strong><br><small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small></td>
            <td><div class="d-flex align-items-center"><span class="audit-avatar">{{ strtoupper(substr($log->actor_login ?? $log->user?->login ?? 'S', 0, 1)) }}</span><div><strong>{{ $log->actor_login ?? $log->user?->login ?? 'Sistema' }}</strong><br><small class="text-muted">IP {{ $log->ip ?? '-' }}</small></div></div></td>
            <td><span class="badge {{ match($log->accion){'CREAR'=>'bg-success','EDITAR'=>'bg-primary','ELIMINAR'=>'bg-danger','RESTAURAR'=>'bg-warning text-dark',default=>'bg-secondary'} }}">{{ $accionOptions[$log->accion] ?? str_replace('_',' ',$log->accion) }}</span><div class="small text-muted mt-1">{{ $moduloOptions[$log->modulo] ?? ucfirst(strtolower($log->modulo ?? 'Sistema')) }}</div></td>
            <td><strong>{{ $log->descripcion ?: 'Sin descripción' }}</strong>@if($log->modelo_id)<br><small class="text-muted">ID del registro: {{ $log->modelo_id }}</small>@endif</td>
            <td style="min-width:380px">@if(count($changes))@php($first = $changes[0])<div class="small"><strong>{{ ucfirst(str_replace('_',' ',$first['field'])) }}</strong><br><span class="text-danger">{{ filled($first['before']) ? $first['before'] : 'Vacío' }}</span> <i class="bx bx-right-arrow-alt"></i> <span class="text-success">{{ filled($first['after']) ? $first['after'] : 'Vacío' }}</span>@if(count($changes)>1 && !($expandedLogs[$log->id] ?? false))<div class="text-muted mt-1">+ {{ count($changes)-1 }} cambio(s) adicional(es)</div>@endif</div>
                @if($expandedLogs[$log->id] ?? false)<div class="audit-inline-detail mt-3"><div class="fw-semibold mb-2">Todos los cambios ({{ count($changes) }})</div>@include('livewire.logs.change-list', ['changes' => $changes])</div>@endif
                @else<span class="text-muted small">Evento sin modificación de datos</span>@endif</td>
            <td class="text-end">@if(count($changes))<button wire:click="toggleDetails({{ $log->id }})" class="btn btn-sm {{ $expandedLogs[$log->id] ?? false ? 'btn-primary' : 'btn-outline-primary' }} text-nowrap" type="button"><i class="bx {{ $expandedLogs[$log->id] ?? false ? 'bx-chevron-up' : 'bx-detail' }}"></i> {{ $expandedLogs[$log->id] ?? false ? 'Ocultar detalle' : 'Ver todos los cambios' }}</button>@endif</td>
        </tr>
        @empty<tr><td colspan="6" class="text-center py-5"><i class="bx bx-search-alt fs-1 text-muted"></i><h6 class="mt-2">No se encontraron eventos</h6><p class="text-muted mb-0">Prueba ampliando el periodo o limpiando los filtros.</p></td></tr>@endforelse
        </tbody></table></div>

        <div class="d-xl-none"><div class="alert alert-light border py-2 small"><i class="bx bx-mobile-alt me-1"></i> Vista adaptada para esta pantalla: cada evento se muestra como una tarjeta.</div>@forelse($logs as $log)@php($changes = $log->changes())<div class="audit-mobile-card"><div class="d-flex justify-content-between align-items-start"><div><span class="badge {{ match($log->accion){'CREAR'=>'bg-success','EDITAR'=>'bg-primary','ELIMINAR'=>'bg-danger','RESTAURAR'=>'bg-warning text-dark',default=>'bg-secondary'} }}">{{ $accionOptions[$log->accion] ?? str_replace('_',' ',$log->accion) }}</span><small class="text-muted ms-1">{{ $moduloOptions[$log->modulo] ?? $log->modulo }}</small></div><small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small></div><h6 class="mt-3 mb-1">{{ $log->descripcion ?: 'Sin descripción' }}</h6><div class="small text-muted mb-2">{{ $log->actor_login ?? $log->user?->login ?? 'Sistema' }} · IP {{ $log->ip ?? '-' }}</div>@if(count($changes))<button wire:click="toggleDetails({{ $log->id }})" class="btn btn-sm {{ $expandedLogs[$log->id] ?? false ? 'btn-primary' : 'btn-outline-primary' }} w-100" type="button">{{ $expandedLogs[$log->id] ?? false ? 'Ocultar cambios' : 'Ver los '.count($changes).' cambios' }}</button>@if($expandedLogs[$log->id] ?? false)<div class="mt-3">@include('livewire.logs.change-list', ['changes' => $changes])</div>@endif @else<div class="alert alert-light border small py-2 mb-0">Evento sin modificación de datos.</div>@endif</div>@empty<div class="text-center text-muted py-5">No se encontraron eventos.</div>@endforelse</div>

        <div class="mt-3">{{ $logs->links() }}</div>
    </div></div>
</div>
