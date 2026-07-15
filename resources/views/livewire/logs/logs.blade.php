<div class="page-content">
    <div class="mb-3"><h4 class="mb-1">Trazabilidad del sistema</h4><p class="text-muted mb-0">Consulta quién realizó una acción, cuándo la hizo y exactamente qué información cambió.</p></div>
    <div class="card"><div class="card-body"><div class="row g-2 align-items-end">
        <div class="col-md-2"><label class="form-label">Desde</label><input wire:model="fromDate" type="date" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Hasta</label><input wire:model="toDate" type="date" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Módulo</label><select wire:model.live="filter_modulo" class="form-select">@foreach($moduloOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Acción</label><select wire:model.live="filter_accion" class="form-select">@foreach($accionOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Buscar</label><input wire:model.live.debounce.300ms="searchTerm" class="form-control" placeholder="Usuario, módulo o descripción..."></div>
        <div class="col-md-1"><button wire:click="filterLogs" class="btn btn-primary w-100"><i class="bx bx-search"></i></button></div>
    </div></div></div>

    <div class="card"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h5 class="mb-0">Historial de cambios</h5><span class="badge bg-secondary">{{ $logs->total() }} registros</span></div>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Fecha y usuario</th><th>Módulo</th><th>Acción</th><th>Registro</th><th>Cambios detectados</th><th>IP</th></tr></thead><tbody>
        @forelse($logs as $log)<tr>
            <td class="text-nowrap"><strong>{{ $log->actor_login ?? $log->user?->login ?? 'Sistema' }}</strong><br><small class="text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</small></td>
            <td><span class="badge bg-light text-dark">{{ $log->modulo ?? '-' }}</span></td>
            <td><span class="badge bg-{{ match($log->accion){'CREAR'=>'success','EDITAR'=>'primary','ELIMINAR'=>'danger','RESTAURAR'=>'warning',default=>'secondary'} }}">{{ str_replace('_', ' ', $log->accion ?? '-') }}</span></td>
            <td>{{ $log->descripcion ?? '-' }}@if($log->modelo_id)<br><small class="text-muted">Registro #{{ $log->modelo_id }}</small>@endif</td>
            <td style="min-width:380px">
                @php($changes = $log->changes())
                @if(count($changes))<div class="d-flex flex-column gap-1">@foreach(array_slice($changes, 0, 3) as $change)<div class="small"><strong>{{ ucfirst(str_replace('_',' ',$change['field'])) }}:</strong> <span class="text-danger text-decoration-line-through">{{ filled($change['before']) ? $change['before'] : 'vacío' }}</span> <i class="bx bx-right-arrow-alt"></i> <span class="text-success">{{ filled($change['after']) ? $change['after'] : 'vacío' }}</span></div>@endforeach</div>
                    @if(count($changes) > 3)<button class="btn btn-link btn-sm p-0 mt-1" data-bs-toggle="modal" data-bs-target="#logDetail{{ $log->id }}">Ver los {{ count($changes) }} cambios</button>@endif
                @else<span class="text-muted small">Sin detalle disponible</span>@endif
            </td><td><small>{{ $log->ip ?? '-' }}</small></td>
        </tr>
        @if(count($changes) > 3)<div class="modal fade" id="logDetail{{ $log->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detalle: {{ $log->descripcion }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><table class="table"><thead><tr><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead><tbody>@foreach($changes as $change)<tr><td><strong>{{ ucfirst(str_replace('_',' ',$change['field'])) }}</strong></td><td class="text-danger">{{ filled($change['before']) ? $change['before'] : 'vacío' }}</td><td class="text-success">{{ filled($change['after']) ? $change['after'] : 'vacío' }}</td></tr>@endforeach</tbody></table></div></div></div></div>@endif
        @empty<tr><td colspan="6" class="text-center text-muted py-4">No se encontraron registros.</td></tr>@endforelse
        </tbody></table></div>{{ $logs->links() }}
    </div></div>
</div>
