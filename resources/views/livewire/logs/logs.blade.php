@push('title', 'Logs de Auditoría')
<div>
    <div class="page-content"
        style="height: calc(100vh - 60px); overflow: hidden; display: flex; flex-direction: column; padding-bottom: 0;">

        <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <ol class="breadcrumb mb-0 d-flex align-items-center">
                    <li class="breadcrumb-item">Administración</li>
                    <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Logs de Auditoría</li>
                </ol>
            </div>
        </div>

        <div class="card mb-2" style="flex-shrink: 0;">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2 col-6">
                        <label class="form-label mb-1">Fecha Inicial</label>
                        <div class="position-relative input-icon" wire:ignore>
                            <input class="form-control flatpickr" type="text" wire:model="fromDate"
                                placeholder="Fecha Inicial">
                            <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label mb-1">Fecha Final</label>
                        <div class="position-relative input-icon" wire:ignore>
                            <input class="form-control flatpickr" type="text" wire:model="toDate"
                                placeholder="Fecha Final">
                            <span class="position-absolute top-50 translate-middle-y"><i class='bx bx-calendar'></i></span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label mb-1">Módulo</label>
                        <select wire:model.live="filter_modulo" class="form-select ">
                            @foreach ($moduloOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label mb-1">Acción</label>
                        <select wire:model.live="filter_accion" class="form-select">
                            @foreach ($accionOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-auto">
                        <button wire:click="filterLogs" wire:loading.attr="disabled"
                            class="btn btn-outline-secondary btnIcon">
                            <span wire:loading.remove wire:target="filterLogs">
                                <i class="bx bx-search-alt"></i> CONSULTAR
                            </span>
                            <span wire:loading wire:target="filterLogs">
                                <i class="bx bx-spin bx-loader"></i> PROCESANDO...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
            <div class="card-header d-flex justify-content-between align-items-center px-3 py-2" style="flex-shrink: 0;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-shield-quarter"></i>
                    <span class="fw-semibold">Log de Auditoría del Sistema</span>
                </div>
                <span class="badge bg-secondary">{{ $logs->total() }} registros</span>
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
                    <table class="table table-hover align-middle table-striped table-sm" style="width: 100%;">
                        <thead class="sticky-top">
                            <tr>
                                <th style="width:50px">N°</th>
                                <th style="width:140px">FECHA</th>
                                <th style="width:110px">MÓDULO</th>
                                <th style="width:120px">ACCIÓN</th>
                                <th>DESCRIPCIÓN</th>
                                <th style="width:120px">USUARIO</th>
                                <th style="width:110px">IP</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $index => $log)
                                <tr>
                                    <td class="text-muted">{{ $startCount - $index }}</td>
                                    <td class="small text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td>
                                        @php
                                            $moduloBadge = match($log->modulo) {
                                                'ACCESO'        => 'badge rounded-pill text-primary bg-light-primary text-uppercase',
                                                'USUARIOS'      => 'badge rounded-pill text-white bg-primary',
                                                'REMITOS'       => 'badge rounded-pill text-white bg-success',
                                                'PRODUCTOS'     => 'badge rounded-pill text-dark bg-warning',
                                                'TRABAJADORES'  => 'badge rounded-pill text-white bg-secondary',
                                                'ROLES'         => 'badge rounded-pill text-white bg-dark',
                                                'SUCURSALES'    => 'badge rounded-pill text-white bg-danger',
                                                default         => 'badge rounded-pill text-muted bg-light',
                                            };
                                        @endphp
                                        <span class="{{ $moduloBadge }}">
                                            {{ $log->modulo ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $accionBadge = match($log->accion) {
                                                'CREAR'          => 'badge rounded-pill text-success',
                                                'EDITAR'         => 'badge rounded-pill text-primary',
                                                'ELIMINAR'       => 'badge rounded-pill text-danger',
                                                'RESTAURAR'      => 'badge rounded-pill text-warning',
                                                'ANULAR'         => 'badge rounded-pill text-danger',
                                                'INICIO_SESION'  => 'badge rounded-pill text-info',
                                                'CIERRE_SESION'  => 'badge rounded-pill text-secondary',
                                                'CAMBIO_CONTRASENA' => 'badge rounded-pill text-warning',
                                                default          => 'badge rounded-pill text-muted bg-light',
                                            };
                                            $accionLabel = match($log->accion) {
                                                'INICIO_SESION'     => 'INICIO SESIÓN',
                                                'CIERRE_SESION'     => 'CIERRE SESIÓN',
                                                'CAMBIO_CONTRASENA' => 'CAMBIO CLAVE',
                                                default             => $log->accion ?? '-',
                                            };
                                        @endphp
                                        <span class="small {{ $accionBadge }}">{{ $accionLabel }}</span>
                                    </td>
                                    <td class=">{{ $log->descripcion ?? '-' }}</td>
                                    <td class="small fw-semibold">{{ $log->user->login ?? '—' }}</td>
                                    <td class="small text-muted">{{ $log->ip ?? '-' }}</td>
                                    <td>
                                        @if($log->valores_anteriores || $log->valores_nuevos)
                                            <button type="button"
                                                class="btn btn-outline-secondary p-0 px-1"
                                                title="Ver detalle"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailModal"
                                                onclick="showLogDetail(
                                                    {{ json_encode($log->descripcion) }},
                                                    {{ json_encode($log->valores_anteriores) }},
                                                    {{ json_encode($log->valores_nuevos) }}
                                                )">
                                                <i class="bx bx-info-circle"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bx bx-search-alt fs-4"></i>
                                        <div>No se encontraron registros.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="flex-shrink: 0; padding-top: 0.4rem;">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-detail me-1"></i> Detalle del Cambio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="detail-desc"></p>
                    <div class="row g-3">
                        <div class="col-md-6" id="col-before">
                            <h6 class="text-danger"><i class="bx bx-minus-circle me-1"></i>Valores Anteriores</h6>
                            <pre id="detail-before" class="bg-light p-2 rounded small" style="white-space:pre-wrap;max-height:300px;overflow:auto"></pre>
                        </div>
                        <div class="col-md-6" id="col-after">
                            <h6 class="text-success"><i class="bx bx-plus-circle me-1"></i>Valores Nuevos</h6>
                            <pre id="detail-after" class="bg-light p-2 rounded small" style="white-space:pre-wrap;max-height:300px;overflow:auto"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showLogDetail(desc, before, after) {
        document.getElementById('detail-desc').textContent = desc || '';

        var colBefore = document.getElementById('col-before');
        var colAfter  = document.getElementById('col-after');

        if (before) {
            document.getElementById('detail-before').textContent = JSON.stringify(before, null, 2);
            colBefore.style.display = '';
        } else {
            colBefore.style.display = 'none';
        }

        if (after) {
            document.getElementById('detail-after').textContent = JSON.stringify(after, null, 2);
            colAfter.style.display = '';
        } else {
            colAfter.style.display = 'none';
        }
    }

    document.addEventListener('livewire:init', function() {
        Livewire.on('errorDate', (Msg) => {
            toast(Msg, 'error');
        });
    });
    </script>
</div>