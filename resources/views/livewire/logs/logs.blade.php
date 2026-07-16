<div class="page-content audit-page">
    <div class="d-flex justify-content-between align-items-start mb-4"><div><h4 class="mb-1">Trazabilidad del sistema</h4><p class="text-muted mb-0">Consulta quién realizó cada acción y qué información cambió.</p></div></div>
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4"><div class="card h-100 mb-0"><div class="card-body py-3"><small class="text-muted">Eventos encontrados</small><div class="fs-4 fw-bold">{{ $summary['total'] }}</div></div></div></div>
        <div class="col-6 col-lg-4"><div class="card h-100 mb-0"><div class="card-body py-3"><small class="text-muted">Modificaciones</small><div class="fs-4 fw-bold text-primary">{{ $summary['changes'] }}</div></div></div></div>
        <div class="col-12 col-lg-4"><div class="card h-100 mb-0"><div class="card-body py-3"><small class="text-muted">Usuarios involucrados</small><div class="fs-4 fw-bold text-success">{{ $summary['users'] }}</div></div></div></div>
    </div>
    <div class="card"><div class="card-body"><div class="d-flex align-items-center mb-3"><i class="bx bx-filter-alt fs-4 text-primary me-2"></i><div><h5 class="mb-0">Filtros</h5><small class="text-muted">Busca por usuario, registro, código, valor o periodo.</small></div></div><form wire:submit="filterLogs"><div class="row g-3 align-items-end">
        <div class="col-12 col-xl-4"><label class="form-label">Buscar</label><div class="input-group"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.400ms="searchTerm" class="form-control" placeholder="Usuario, producto, código o valor..."></div></div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Desde</label><input wire:model="fromDate" type="date" class="form-control @error('fromDate') is-invalid @enderror">@error('fromDate')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Hasta</label><input wire:model="toDate" type="date" class="form-control @error('toDate') is-invalid @enderror">@error('toDate')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Módulo</label><select wire:model.live="filter_modulo" class="form-select">@foreach($moduloOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label">Acción</label><select wire:model.live="filter_accion" class="form-select">@foreach($accionOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-12 d-flex justify-content-end gap-2"><button type="button" wire:click="clearFilters" class="btn btn-light border"><i class="bx bx-reset"></i> Limpiar</button><button class="btn btn-primary"><i class="bx bx-search-alt"></i> Consultar periodo</button></div>
    </div></form></div></div>
    <div class="card"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="mb-0">Historial de actividad</h5><small class="text-muted">Los eventos se adaptan automáticamente al ancho disponible.</small></div><div class="d-flex align-items-center gap-2"><small class="text-muted">Mostrar</small><select wire:model.live="perPage" class="form-select form-select-sm" style="width:80px">@foreach($perPageOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div></div>
        @include('livewire.logs.responsive-history')
        <div class="mt-3">{{ $logs->links() }}</div>
    </div></div>
</div>
