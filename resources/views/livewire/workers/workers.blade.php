<div class="page-content">
    <div class="module-header">
        <div><h4 class="mb-1">Trabajadores</h4><p class="text-muted mb-0">Personas que podrán recibir productos y activos del almacén.</p></div>
        <span class="module-counter">{{ $workers->total() }} registrados</span>
    </div>

    @canany(['crear-trabajador', 'editar-trabajador'])
    <div class="card module-form-card" id="worker-form">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="bx bx-user-plus me-1"></i><strong>{{ $workerId ? 'Editar trabajador' : 'Registrar trabajador' }}</strong></div>
            @if($workerId)<button type="button" wire:click="resetForm" class="btn btn-sm btn-light">Cancelar edición</button>@endif
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Documento <span class="text-danger">*</span></label><input wire:model="document" maxlength="40" class="form-control @error('document') is-invalid @enderror" placeholder="Ej.: 12345678">@error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Nombres <span class="text-danger">*</span></label><input wire:model="name" maxlength="100" class="form-control @error('name') is-invalid @enderror">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Apellidos <span class="text-danger">*</span></label><input wire:model="lastname" maxlength="100" class="form-control @error('lastname') is-invalid @enderror">@error('lastname')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Fecha de ingreso</label><input wire:model="start_date" type="date" max="{{ now()->format('Y-m-d') }}" class="form-control @error('start_date') is-invalid @enderror">@error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Área</label><input wire:model="area" maxlength="120" class="form-control @error('area') is-invalid @enderror" placeholder="Ej.: Mantenimiento">@error('area')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Cargo</label><input wire:model="position" maxlength="120" class="form-control @error('position') is-invalid @enderror">@error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Teléfono</label><input wire:model="phone" maxlength="30" class="form-control @error('phone') is-invalid @enderror">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Correo</label><input wire:model="email" type="email" maxlength="150" class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label class="form-label">Observaciones <span class="field-optional">Opcional</span></label><textarea wire:model="notes" rows="2" maxlength="1000" class="form-control @error('notes') is-invalid @enderror" placeholder="Información adicional relevante"></textarea>@error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
                <div class="form-actions">@if($workerId)<button type="button" wire:click="resetForm" class="btn btn-outline-secondary">Cancelar</button>@endif<button class="btn btn-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save"><i class="bx bx-save me-1"></i>{{ $workerId ? 'Guardar cambios' : 'Registrar trabajador' }}</span><span wire:loading wire:target="save">Guardando...</span></button></div>
            </form>
        </div>
    </div>
    @endcanany

    <div class="card">
        <div class="card-header"><div class="row g-2 align-items-center">
            <div class="col-md-8"><div class="input-group"><span class="input-group-text"><i class="bx bx-search"></i></span><input wire:model.live.debounce.350ms="searchTerm" class="form-control" placeholder="Buscar por código, documento, nombre, área o cargo"></div></div>
            <div class="col-md-4"><select wire:model.live="statusFilter" class="form-select"><option value="active">Solo activos</option><option value="inactive">Solo inactivos</option><option value="all">Todos los trabajadores</option></select></div>
        </div></div>
        <div class="card-body p-0">
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle mb-0 table-with-actions">
                    <thead><tr><th>Código</th><th>Trabajador</th><th>Documento</th><th>Área / cargo</th><th>Contacto</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>@forelse($workers as $worker)<tr wire:key="worker-row-{{ $worker->id }}">
                        <td class="font-monospace small">{{ $worker->code }}</td>
                        <td><strong>{{ $worker->full_name }}</strong><div class="small text-muted">Ingreso: {{ $worker->start_date?->format('d/m/Y') ?? 'No registrada' }}</div></td>
                        <td>{{ $worker->document }}</td>
                        <td>{{ $worker->area ?: '—' }}<div class="small text-muted">{{ $worker->position ?: 'Sin cargo' }}</div></td>
                        <td><div>{{ $worker->phone ?: '—' }}</div><div class="small text-muted">{{ $worker->email }}</div></td>
                        <td><span class="badge bg-{{ $worker->status ? 'success' : 'secondary' }}">{{ $worker->status ? 'Activo' : 'Inactivo' }}</span></td>
                        <td class="text-end text-nowrap">@can('editar-trabajador')<button wire:click="edit({{ $worker->id }})" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bx bx-edit"></i></button>@endcan @if($worker->status) @can('eliminar-trabajador')<button wire:click="toggleStatus({{ $worker->id }})" wire:confirm="¿Desactivar a este trabajador?" class="btn btn-sm btn-outline-danger" title="Desactivar"><i class="bx bx-user-x"></i></button>@endcan @else @can('restaurar-trabajador')<button wire:click="toggleStatus({{ $worker->id }})" class="btn btn-sm btn-outline-success" title="Activar"><i class="bx bx-user-check"></i></button>@endcan @endif</td>
                    </tr>@empty<tr><td colspan="7" class="text-center text-muted py-5">No se encontraron trabajadores.</td></tr>@endforelse</tbody>
                </table>
            </div>

            <div class="d-lg-none p-2">@forelse($workers as $worker)<div class="border rounded p-3 mb-2" wire:key="worker-card-{{ $worker->id }}">
                <div class="d-flex justify-content-between gap-2"><div><strong>{{ $worker->full_name }}</strong><div class="small font-monospace text-muted">{{ $worker->code }} · {{ $worker->document }}</div></div><span class="badge align-self-start bg-{{ $worker->status ? 'success' : 'secondary' }}">{{ $worker->status ? 'Activo' : 'Inactivo' }}</span></div>
                <div class="small mt-2"><i class="bx bx-building me-1"></i>{{ $worker->area ?: 'Sin área' }} · {{ $worker->position ?: 'Sin cargo' }}</div>
                @if($worker->phone || $worker->email)<div class="small text-muted mt-1"><i class="bx bx-phone me-1"></i>{{ $worker->phone ?: $worker->email }}</div>@endif
                <div class="d-flex gap-2 mt-3">@can('editar-trabajador')<button wire:click="edit({{ $worker->id }})" class="btn btn-sm btn-outline-primary flex-fill"><i class="bx bx-edit me-1"></i>Editar</button>@endcan @if($worker->status) @can('eliminar-trabajador')<button wire:click="toggleStatus({{ $worker->id }})" wire:confirm="¿Desactivar a este trabajador?" class="btn btn-sm btn-outline-danger flex-fill">Desactivar</button>@endcan @else @can('restaurar-trabajador')<button wire:click="toggleStatus({{ $worker->id }})" class="btn btn-sm btn-outline-success flex-fill">Activar</button>@endcan @endif</div>
            </div>@empty<div class="text-center text-muted py-5">No se encontraron trabajadores.</div>@endforelse</div>
        </div>
        @if($workers->hasPages())<div class="card-footer">{{ $workers->links() }}</div>@endif
    </div>
</div>
