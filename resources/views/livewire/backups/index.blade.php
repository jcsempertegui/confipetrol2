@push('title', 'Backups de Base de Datos')

<div>
    <div class="page-content">
        <div class="module-header">
            <div>
                <h4 class="mb-1">Backups de base de datos</h4>
                <p class="text-muted mb-0">Crea, descarga y administra los respaldos de seguridad del sistema.</p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="module-counter">{{ count($backups) }} archivos</span>

                @if(auth()->user()->hasRole('SUPER ADMIN') && auth()->user()->can('restaurar-backup'))
                    <button
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadRestoreModal"
                        class="btn btn-outline-warning"
                        wire:loading.attr="disabled"
                        wire:target="confirmRestoreFromList,uploadAndRestore"
                    >
                        <i class="bx bx-upload me-1"></i>Restaurar archivo
                    </button>
                @endif

                @can('crear-backup')
                    <button
                        type="button"
                        wire:click="createBackup"
                        wire:loading.attr="disabled"
                        wire:target="createBackup"
                        class="btn btn-primary"
                    >
                        <span wire:loading.remove wire:target="createBackup">
                            <i class="bx bx-plus-circle me-1"></i>Crear backup
                        </span>
                        <span wire:loading wire:target="createBackup">
                            <i class="bx bx-spin bx-loader me-1"></i>Generando...
                        </span>
                    </button>
                @endcan
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="detail-label">Backups guardados</span>
                        <div class="fs-4 fw-bold text-primary">{{ count($backups) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="detail-label">Espacio total</span>
                        <div class="fs-5 fw-bold text-success">{{ $backupDirSize }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="detail-label">Último backup</span>
                        <div class="fw-semibold text-info">
                            @if(count($backups) > 0)
                                {{ $backups[0]['date']->format('d/m/Y H:i') }}
                            @else
                                Sin backups
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="detail-label">Backup de hoy</span>
                        @php $todayHasBackup = collect($backups)->firstWhere('is_today', true); @endphp
                        <div class="fw-semibold {{ $todayHasBackup ? 'text-success' : 'text-warning' }}">
                            @if($todayHasBackup)
                                <i class="bx bx-check-circle me-1"></i>Realizado
                            @else
                                <i class="bx bx-time-five me-1"></i>Pendiente
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card module-list-card">
            <div class="card-header">
                <div>
                    <strong><i class="bx bx-data me-1"></i>Historial de backups</strong>
                    <div class="form-card-subtitle">Se conservan como máximo 30 archivos.</div>
                </div>
                <span class="module-counter">{{ count($backups) }} archivos</span>
            </div>

            <div class="card-body p-0">
                <div
                    wire:loading
                    wire:target="confirmRestoreFromList,uploadAndRestore"
                    class="alert alert-warning m-3 mb-0"
                    role="status"
                >
                    <i class="bx bx-spin bx-loader me-2"></i>Restaurando la base de datos, por favor espera...
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle table-with-actions">
                        <thead>
                            <tr>
                                <th>N.º</th>
                                <th>Nombre del archivo</th>
                                <th>Tipo</th>
                                <th>Fecha y hora</th>
                                <th>Tamaño</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $index => $backup)
                                <tr wire:key="backup-row-{{ md5($backup['filename']) }}">
                                    <td class="text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bx bx-file text-secondary"></i>
                                            <span class="small font-monospace">{{ $backup['filename'] }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge {{ $backup['type']['class'] }}">{{ $backup['type']['label'] }}</span></td>
                                    <td class="text-nowrap">{{ $backup['date']->format('d/m/Y H:i:s') }}</td>
                                    <td>{{ $backup['size'] }}</td>
                                    <td>
                                        @if($backup['is_today'])
                                            <span class="badge rounded-pill text-success bg-light-success border border-success">Hoy</span>
                                        @else
                                            <span class="badge rounded-pill text-muted bg-light">Anterior</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a
                                            href="{{ route('backup.download', ['filename' => $backup['filename']]) }}"
                                            class="btn btn-outline-primary btn-sm"
                                            title="Descargar backup"
                                            aria-label="Descargar {{ $backup['filename'] }}"
                                        >
                                            <i class="bx bx-download"></i>
                                        </a>

                                        @if(auth()->user()->hasRole('SUPER ADMIN') && auth()->user()->can('restaurar-backup'))
                                            <button
                                                type="button"
                                                class="btn btn-outline-warning btn-sm"
                                                title="Restaurar este backup"
                                                aria-label="Restaurar {{ $backup['filename'] }}"
                                                onclick="confirmRestoreBackup('{{ $backup['filename'] }}')"
                                            >
                                                <i class="bx bx-reset"></i>
                                            </button>
                                        @endif

                                        @can('eliminar-backup')
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                title="Eliminar backup"
                                                aria-label="Eliminar {{ $backup['filename'] }}"
                                                onclick="confirmDeleteBackup('{{ $backup['filename'] }}')"
                                            >
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
                                        <div class="small mt-1">Usa <strong>Crear backup</strong> para generar el primer respaldo.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer py-3">
                <div class="row g-2 small text-muted">
                    <div class="col-md-6">
                        <i class="bx bx-info-circle me-1"></i>El backup automático se ejecuta diariamente a la 1:00 a. m.
                    </div>
                    <div class="col-md-6 text-md-end">
                        <i class="bx bx-folder me-1"></i>Se conservan automáticamente los últimos <strong>30</strong> backups.
                    </div>
                    <div class="col-12">
                        <i class="bx bx-shield-quarter me-1"></i>Antes de restaurar se crea un respaldo de seguridad identificado como <strong>Pre-restauración</strong>.
                    </div>
                </div>
            </div>
        </div>

    @if(auth()->user()->hasRole('SUPER ADMIN') && auth()->user()->can('restaurar-backup'))
        <div wire:ignore.self class="modal fade" id="uploadRestoreModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content module-form-card">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title"><i class="bx bx-upload me-1"></i>Restaurar desde un archivo</h5>
                            <div class="form-card-subtitle">Selecciona un respaldo SQL externo.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-warning mb-3">
                            <i class="bx bx-error me-1"></i>Esta acción reemplazará <strong>toda</strong> la base de datos actual. Es irreversible.
                        </div>

                        <label for="backup-sql-file" class="form-label">Archivo SQL <span class="text-danger">*</span></label>
                        <input
                            id="backup-sql-file"
                            type="file"
                            wire:model="sqlFile"
                            accept=".sql"
                            class="form-control @error('sqlFile') is-invalid @enderror"
                        >
                        @error('sqlFile')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        <div class="form-text mt-2">Formato permitido: .sql. Tamaño máximo: 100 MB.</div>

                        <div wire:loading wire:target="sqlFile" class="small text-primary mt-2" role="status">
                            <i class="bx bx-loader-alt bx-spin me-1"></i>Cargando archivo...
                        </div>
                    </div>

                    <div class="modal-footer form-actions mt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button
                            type="button"
                            wire:click="uploadAndRestore"
                            wire:loading.attr="disabled"
                            wire:target="uploadAndRestore,sqlFile"
                            class="btn btn-warning"
                            onclick="bootstrap.Modal.getInstance(document.getElementById('uploadRestoreModal'))?.hide()"
                        >
                            <span wire:loading.remove wire:target="uploadAndRestore,sqlFile">
                                <i class="bx bx-reset me-1"></i>Restaurar backup
                            </span>
                            <span wire:loading wire:target="sqlFile">
                                <i class="bx bx-spin bx-loader me-1"></i>Cargando...
                            </span>
                            <span wire:loading wire:target="uploadAndRestore">
                                <i class="bx bx-spin bx-loader me-1"></i>Restaurando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    </div>

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
        } else if (confirm('¿Eliminar este backup?\n' + filename)) {
            @this.call('confirmDelete', filename);
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
        } else if (confirm('¿Restaurar la base de datos desde?\n' + filename + '\n\nEsta acción es irreversible.')) {
            @this.call('confirmRestoreFromList', filename);
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
