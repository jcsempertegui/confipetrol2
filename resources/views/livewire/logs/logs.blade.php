@push('title', 'Logs de Acceso')
<div class="page-content"
    style="height: calc(100vh - 60px); overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0;">
    <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item">Administracion</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Logs de Acceso</li>
            </ol>
        </div>
    </div>

    <div class="card mb-2" style="flex-shrink: 0;">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="desde">Fecha Inicial</label>
                        <div class="position-relative input-icon">
                            <input id="desde" class="form-control flatpickr" type="text" wire:model="fromDate"
                                placeholder="Seleccionar Fecha Inicial">
                            <span class="position-absolute top-50 translate-middle-y"><i
                                    class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="form-group" wire:ignore>
                        <label for="hasta">Fecha Final</label>
                        <div class="position-relative input-icon">
                            <input id="hasta" class="form-control flatpickr" type="text" wire:model="toDate"
                                placeholder="Seleccionar Fecha Final">
                            <span class="position-absolute top-50 translate-middle-y"><i
                                    class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="form-group d-flex align-items-end">
                        <button wire:click="filterLogs" wire:loading.attr="disabled"
                            class="btn btn-outline-secondary btnIcon" :disabled="!@this.fromDate || !@this.toDate">
                            <span wire:loading.remove wire:target="filterLogs">
                                <i class="bx bx-search-alt"></i>
                                CONSULTAR
                            </span>
                            <span wire:loading wire:target="filterLogs">
                                <i class="bx bx-spin bx-loader"></i>
                                PROCESANDO...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2" style="flex-shrink: 0;">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-list-ul"></i>
                <span class="fw-semibold">Listar Logs de Acceso</span>
            </div>
        </div>

        <div class="card-body px-3"
            style="flex: 1; min-height: 0; overflow: hidden; display: flex; flex-direction: column;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2"
                style="flex-shrink: 0;">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Mostrar</span>
                    <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">registros</span>
                </div>
                @include('components.tools.searchbox')
            </div>

            <div class="table-responsive" style="flex: 1; min-height: 0; overflow: auto;">
                <table class="table table-hover align-middle table-striped" style="width: 100%;">
                    <thead class="sticky-top">
                        <tr>
                            <th>N°</th>
                            <th>FECHA</th>
                            <th>EVENTO</th>
                            <th>USUARIO</th>
                            <th>IP</th>
                            <th>DETALLE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($logs->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">No se encontraron registros.</td>
                            </tr>
                        @else
                            @foreach($logs as $index => $log)
                                <tr>
                                    <td>{{ $startCount - $index }}</td>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $log->evento }}</td>
                                    <td>{{ $log->user->login ?? 'Usuario no encontrado' }}</td>
                                    <td>{{ $log->ip }}</td>
                                    <td>{{ $log->detalle }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div style="flex-shrink: 0; padding-top: 0.4rem;">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', function() {
    Livewire.on('errorDate', (Msg, type) => {
        toast(Msg, 'error')
    });
});
</script>