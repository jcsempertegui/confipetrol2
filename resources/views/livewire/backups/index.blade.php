@push('title', 'Backups de Base de Datos')
<div>
    <div class="page-content"
        style="height: calc(100vh - 60px); overflow-y: auto; display: flex; flex-direction: column; padding-bottom: 0;">

        <div class="row align-items-center mb-2 px-2" style="flex-shrink: 0;">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <ol class="breadcrumb mb-0 d-flex align-items-center">
                    <li class="breadcrumb-item">Administración</li>
                    <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Backups de Base de Datos</li>
                </ol>
            </div>
        </div>

        <div class="row g-2 mb-2 px-1" style="flex-shrink: 0;">
            <div class="col-6 col-md-3">
                <div class="card text-center py-2">
                    <div class="card-body py-1 px-2">
                        <div class="fs-4 fw-bold text-primary">{{ count($backups) }}</div>
                        <div class="small text-muted">Backups guardados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-2">
                    <div class="card-body py-1 px-2">
                        <div class="fs-5 fw-bold text-success">{{ $backupDirSize }}</div>
                        <div class="small text-muted">Espacio total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-2">
                    <div class="card-body py-1 px-2">
                        <div class="fs-6 fw-bold text-info">
                            @if(count($backups) > 0)
                                {{ $backups[0]['date']->format('d/m/Y H:i') }}
                            @else
                                Sin backups
                            @endif
                        </div>
                        <div class="small text-muted">Último backup</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center py-2">
                    <div class="card-body py-1 px-2">
                        <div class="fs-6 fw-bold text-warning">
                            @php $todayHasBackup = collect($backups)->firstWhere('is_today', true); @endphp
                            @if($todayHasBackup)
                                <i class="bx bx-check-circle text-success"></i> Realizado
                            @else
                                <i class="bx bx-time-five text-warning"></i> Pendiente
                            @endif
                        </div>
                        <div class="small text-muted">Backup de hoy</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
            <div class="card-header d-flex justify-content-between align-items-center px-3 py-2" style="flex-shrink: 0;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-data"></i>
                    <span class="fw-semibold">Historial de Backups (máx. 30 archivos)</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-secondary">{{ count($backups) }} archivos</span>
                    @if(auth()->user()->hasRole('SUPER ADMIN') && auth()->user()->can('restaurar-backup'))
                    <button type="button"
                        data-bs-toggle="modal" data-bs-target="#uploadRestoreModal"
                        class="btn btn-outline-warning btn-sm"
                        wire:loading.attr="disabled"
                        wire:target="confirmRestoreFromList,uploadAndRestore">
                        <i class="bx bx-upload me-1"></i> RESTAURAR ARCHIVO
                    </button>
                    @endif
                    @can('crear-backup')
                    <button wire:click="createBackup"
                        wire:loading.attr="disabled"
                        wire:target="createBackup"
                        class="btn btn-primary btn-sm">
                        <span wire:loading.remove wire:target="createBackup">
                            <i class="bx bx-plus-circle me-1"></i> CREAR BACKUP AHORA
                        </span>
                        <span wire:loading wire:target="createBackup">
                            <i class="bx bx-spin bx-loader me-1"></i> GENERANDO...
                        </span>
                    </button>
                    @endcan
                </div>
            </div>

            <div class="card-body px-3"
                style="flex: 1; min-height: 0; overflow: hidden; display: flex; flex-direction: column;">

                <div wire:loading wire:target="confirmRestoreFromList,uploadAndRestore"
                    class="alert alert-warning py-2 mb-2 small" style="flex-shrink: 0;">
                    <i class="bx bx-spin bx-loader me-2"></i> Restaurando base de datos, por favor espere...
                </div>

                <div class="table-responsive" style="flex: 1; min-height: 0; overflow: auto;">
                    <table class="table table-hover align-middle table-striped table-sm" style="width: 100%;">
                        <thead class="sticky-top">
                            <tr>
                                <th style="width: 50px">N°</th>
                                <th>NOMBRE DEL ARCHIVO</th>
                                <th style="width: 150px">TIPO</th>
                                <th style="width: 170px">FECHA Y HORA</th>
                                <th style="width: 100px">TAMAÑO</th>
                                <th style="width: 80px">ESTADO</th>
                                <th style="width: 140px" class="text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $index => $backup)
                                <tr>
                                    <td class="text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bx bx-file text-secondary"></i>
                                            <span class="small font-monospace">{{ $backup['filename'] }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge {{ $backup['type']['class'] }}">{{ $backup['type']['label'] }}</span></td>
                                    <td class="small text-nowrap">
                                        {{ $backup['date']->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="small">{{ $backup['size'] }}</td>
                                    <td>
                                        @if($backup['is_today'])
                                            <span class="badge rounded-pill text-success bg-light-success border border-success">Hoy</span>
                                        @else
                                            <span class="badge rounded-pill text-muted bg-light">Anterior</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('backup.download', ['filename' => $backup['filename']]) }}"
                                            class="btn btn-outline-primary btn-sm p-0 px-1 me-1"
                                            title="Descargar backup">
                                            <i class="bx bx-download"></i>
                                        </a>
                                        @if(auth()->user()->hasRole('SUPER ADMIN') && auth()->user()->can('restaurar-backup'))
                                        <button type="button"
                                            class="btn btn-outline-warning btn-sm p-0 px-1 me-1"
                                            title="Restaurar este backup"
                                            onclick="confirmRestoreBackup('{{ $backup['filename'] }}')">
                                            <i class="bx bx-reset"></i>
                                        </button>
                                        @endif
                                        @can('eliminar-backup')
                                        <button type="button"
                                            class="btn btn-outline-danger btn-sm p-0 px-1"
                                            title="Eliminar backup"
                                            onclick="confirmDeleteBackup('{{ $backup['filename'] }}')">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bx bx-data fs-2 d-block mb-2"></i>
                                        <div>No hay backups disponibles.</div>
                                        <div class="small mt-1">Haga clic en <strong>CREAR BACKUP AHORA</strong> para generar el primer backup.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-top pt-2 mt-1" style="flex-shrink: 0;">
                    <div class="row g-2 small text-muted">
                        <div class="col-md-6">
                            <i class="bx bx-info-circle me-1"></i>
                            El backup automático se ejecuta diariamente a la 1:00 AM.
                        </div>
                        <div class="col-md-6 text-md-end">
                            <i class="bx bx-folder me-1"></i>
                            Se conservan los últimos <strong>30</strong> backups automáticamente.
                        </div>
                        <div class="col-12">
                            <i class="bx bx-shield-quarter me-1"></i>
                            Antes de cada restauración se crea un respaldo de seguridad identificado como <strong>Pre-restauración</strong>.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(auth()->user()->hasRole('SUPER ADMIN') && auth()->user()->can('restaurar-backup'))
        <div class="card mt-2 mb-2" style="flex-shrink: 0;">
            <div class="card-header px-3 py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-cloud-upload"></i>
                    <span class="fw-semibold">Restaurar desde Archivo Externo</span>
                </div>
            </div>
            <div class="card-body px-3 py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Seleccionar archivo .sql</label>
                        <input type="file" wire:model="sqlFile" accept=".sql" class="form-control form-control-sm">
                        @error('sqlFile')
                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                        @enderror
                        <div class="small text-muted mt-1">
                            <i class="bx bx-error-circle text-warning me-1"></i>
                            Esta acción reemplazará toda la base de datos actual. Use con precaución.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button wire:click="uploadAndRestore"
                            wire:loading.attr="disabled"
                            wire:target="uploadAndRestore,sqlFile"
                            class="btn btn-warning btn-sm w-100">
                            <span wire:loading.remove wire:target="uploadAndRestore,sqlFile">
                                <i class="bx bx-upload me-1"></i> RESTAURAR ARCHIVO
                            </span>
                            <span wire:loading wire:target="sqlFile">
                                <i class="bx bx-spin bx-loader me-1"></i> CARGANDO...
                            </span>
                            <span wire:loading wire:target="uploadAndRestore">
                                <i class="bx bx-spin bx-loader me-1"></i> RESTAURANDO...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    @if(auth()->user()->hasRole('SUPER ADMIN') && auth()->user()->can('restaurar-backup'))
    <div wire:ignore.self class="modal fade" id="uploadRestoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-upload me-2"></i>Restaurar desde Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="bx bx-error me-1"></i>
                        Esta acción reemplazará <strong>toda</strong> la base de datos actual. Es irreversible.
                    </div>
                    <label class="form-label fw-semibold">Seleccionar archivo .sql</label>
                    <input type="file" wire:model="sqlFile" accept=".sql" class="form-control">
                    @error('sqlFile')
                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button wire:click="uploadAndRestore"
                        wire:loading.attr="disabled"
                        wire:target="uploadAndRestore,sqlFile"
                        class="btn btn-warning btn-sm"
                        onclick="bootstrap.Modal.getInstance(document.getElementById('uploadRestoreModal'))?.hide()">
                        <span wire:loading.remove wire:target="uploadAndRestore">
                            <i class="bx bx-upload me-1"></i> RESTAURAR
                        </span>
                        <span wire:loading wire:target="uploadAndRestore">
                            <i class="bx bx-spin bx-loader me-1"></i> RESTAURANDO...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
    function confirmDeleteBackup(filename) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Eliminar backup?',
                text: filename,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('confirmDelete', filename);
                }
            });
        } else {
            if (confirm('¿Eliminar este backup?\n' + filename)) {
                @this.call('confirmDelete', filename);
            }
        }
    }

    function confirmRestoreBackup(filename) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Restaurar base de datos?',
                html: '<b>' + filename + '</b><br><br>Esta acción reemplazará toda la base de datos actual. Es irreversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, restaurar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('confirmRestoreFromList', filename);
                }
            });
        } else {
            if (confirm('¿Restaurar la base de datos desde?\n' + filename + '\n\nEsta acción es irreversible.')) {
                @this.call('confirmRestoreFromList', filename);
            }
        }
    }

    document.addEventListener('livewire:init', function () {
        Livewire.on('backupSuccess', (msg) => {
            if (typeof toast === 'function') toast(msg, 'success');
            else alert(msg);
        });
        Livewire.on('backupError', (msg) => {
            if (typeof toast === 'function') toast(msg, 'error');
            else alert(msg);
        });
    });
    </script>
</div>
