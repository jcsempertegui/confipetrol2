<div class="page-content audit-page">
    <div class="module-header">
        <div>
            <h4 class="mb-1">Trazabilidad del sistema</h4>
            <p class="text-muted mb-0">Consulta quién realizó cada acción y qué información cambió.</p>
        </div>
        @can('exportar-log')
            <button wire:click="exportCsv" wire:loading.attr="disabled" class="btn btn-success">
                <i class="bx bx-download me-1"></i>Guardar registros CSV
            </button>
        @endcan
    </div>

    <div class="module-metrics-grid module-metrics-grid-3">
        <div class="module-metric-card">
            <span class="module-metric-icon"><i class="bx bx-list-check"></i></span>
            <div>
                <div class="module-metric-label">Eventos encontrados</div>
                <div class="module-metric-value">{{ $summary['total'] }}</div>
            </div>
        </div>
        <div class="module-metric-card tone-info">
            <span class="module-metric-icon"><i class="bx bx-edit-alt"></i></span>
            <div>
                <div class="module-metric-label">Modificaciones</div>
                <div class="module-metric-value">{{ $summary['changes'] }}</div>
            </div>
        </div>
        <div class="module-metric-card tone-success">
            <span class="module-metric-icon"><i class="bx bx-user-check"></i></span>
            <div>
                <div class="module-metric-label">Usuarios involucrados</div>
                <div class="module-metric-value">{{ $summary['users'] }}</div>
            </div>
        </div>
    </div>

    <div class="card module-filter-card">
        <div class="card-header">
            <div>
                <strong><i class="bx bx-filter-alt me-1 text-primary"></i>Filtros de auditoría</strong>
                <div class="form-card-subtitle">Busca por responsable, registro, código, valor o periodo.</div>
            </div>
        </div>
        <div class="card-body">
            <form wire:submit="filterLogs">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-xl-4">
                        <label class="form-label">Buscar</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input wire:model.live.debounce.400ms="searchTerm" class="form-control" placeholder="Usuario, producto, código o valor...">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label">Desde</label>
                        <input wire:model="fromDate" type="date" class="form-control @error('fromDate') is-invalid @enderror">
                        @error('fromDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label">Hasta</label>
                        <input wire:model="toDate" type="date" class="form-control @error('toDate') is-invalid @enderror">
                        @error('toDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label">Módulo</label>
                        <select wire:model.live="filter_modulo" class="form-select">
                            @foreach($moduloOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label">Acción</label>
                        <select wire:model.live="filter_accion" class="form-select">
                            @foreach($accionOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" wire:click="clearFilters" class="btn btn-outline-secondary"><i class="bx bx-reset"></i>Limpiar</button>
                    <button class="btn btn-primary"><i class="bx bx-search-alt"></i>Consultar periodo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card module-list-card">
        <div class="card-header">
            <div>
                <strong><i class="bx bx-history me-1 text-primary"></i>Historial de actividad</strong>
                <div class="form-card-subtitle">Cada evento conserva el valor anterior y el valor nuevo.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="audit-per-page" class="small text-muted mb-0">Mostrar</label>
                <select id="audit-per-page" wire:model.live="perPage" class="form-select form-select-sm" style="width:80px">
                    @foreach($perPageOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            @include('livewire.logs.responsive-history')
        </div>
        @if($logs->hasPages())
            <div class="card-footer">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
