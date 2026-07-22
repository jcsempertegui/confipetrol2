<?php

namespace App\Livewire;

use App\Services\BackupCryptoService;
use App\Traits\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class BackupsController extends Component
{
    use AuditLog, WithFileUploads;

    public bool $isCreating = false;

    public bool $isRestoring = false;

    public $sqlFile = null;

    protected $listeners = [];

    public function createBackup(): void
    {
        abort_unless(auth()->user()->can('crear-backup'), 403);
        $this->isCreating = true;

        try {
            $result = Artisan::call('backup:database', ['--type' => 'manual']);

            if ($result === 0) {
                $latest = collect($this->getBackups())->first();
                $this->logActivity('BACKUPS', 'CREAR', 'Creación de respaldo de base de datos', null, null, $latest ? ['archivo' => $latest['filename'], 'tamaño' => $latest['size']] : null);
                $this->dispatch('backupSuccess', 'Backup creado correctamente.');
            } else {
                $this->dispatch('backupError', 'No se pudo crear el respaldo. Revise el registro técnico del sistema.');
            }
        } catch (\Throwable $e) {
            $this->dispatchBackupFailure('crear el respaldo', $e);
        }

        $this->isCreating = false;
    }

    public function confirmDelete(string $filename): void
    {
        $this->deleteBackup($filename);
    }

    public function deleteBackup(string $filename): void
    {
        abort_unless(auth()->user()->can('eliminar-backup'), 403);
        $filename = basename($filename);

        if (! $this->isSupportedBackupName($filename)) {
            return;
        }

        $path = storage_path('app/backups/'.$filename);

        if (file_exists($path)) {
            $before = ['archivo' => $filename, 'tamaño_bytes' => filesize($path), 'fecha' => date('Y-m-d H:i:s', filemtime($path))];
            unlink($path);
            if (is_file($path.'.sha256')) {
                unlink($path.'.sha256');
            }
            $this->logActivity('BACKUPS', 'ELIMINAR', 'Eliminación del respaldo '.$filename, null, $before, null);
            $this->dispatch('backupSuccess', 'Backup eliminado correctamente.');
        }
    }

    public function confirmRestoreFromList(string $filename): void
    {
        $this->authorizeRestore();
        $filename = basename($filename);

        if (! str_ends_with($filename, '.sql')) {
            $this->dispatch('backupError', 'Archivo inválido.');

            return;
        }

        $path = storage_path('app/backups/'.$filename);

        if (! file_exists($path)) {
            $this->dispatch('backupError', 'Archivo no encontrado.');

            return;
        }
        $this->isRestoring = true;

        try {
            $this->verifyBackupChecksum($path);
            $rollbackPath = $this->prepareRestore($filename);
            $this->executeRestoreFile($path, $rollbackPath);
            $this->writeRestoreAudit('COMPLETADA', $filename);
            $this->logActivity('BACKUPS', 'RESTAURAR', 'Restauración de base de datos', null, ['base_de_datos' => DB::connection()->getDatabaseName()], ['archivo_restaurado' => $filename]);
            $this->dispatch('backupSuccess', 'Base de datos restaurada desde: '.$filename);
        } catch (\Throwable $e) {
            $this->dispatchBackupFailure('restaurar la base de datos', $e);
        }

        $this->isRestoring = false;
    }

    public function uploadAndRestore(): void
    {
        $this->authorizeRestore();
        $this->validate(
            ['sqlFile' => 'required|file|max:102400'],
            [
                'sqlFile.required' => 'Seleccione un archivo .sql',
                'sqlFile.max' => 'El archivo no puede superar 100MB',
            ]
        );

        if (! in_array(strtolower($this->sqlFile->getClientOriginalExtension()), ['sql', 'cfpbak'], true)) {
            $this->addError('sqlFile', 'Solo se permiten respaldos .cfpbak o archivos SQL heredados.');

            return;
        }

        if (Str::length($this->sqlFile->getClientOriginalName()) > 255) {
            $this->addError('sqlFile', 'El nombre del archivo es demasiado largo.');

            return;
        }

        $this->isRestoring = true;

        try {
            $originalName = $this->sqlFile->getClientOriginalName();
            $rollbackPath = $this->prepareRestore($originalName);
            $this->executeRestoreFile($this->sqlFile->getRealPath(), $rollbackPath);
            $this->writeRestoreAudit('COMPLETADA', $originalName);
            $this->logActivity('BACKUPS', 'RESTAURAR', 'Restauración desde archivo subido', null, ['base_de_datos' => DB::connection()->getDatabaseName()], ['archivo_restaurado' => $originalName]);
            $this->sqlFile = null;
            $this->dispatch('backupSuccess', 'Base de datos restaurada desde archivo subido.');
        } catch (\Throwable $e) {
            $this->dispatchBackupFailure('restaurar el archivo subido', $e);
        }

        $this->isRestoring = false;
    }

    private function executeRestoreFile(string $path, string $rollbackPath): void
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');
        [$plainPath, $removePlainPath] = $this->plainRestorePath($path);

        try {
            $this->executePlainRestoreFile($plainPath, $rollbackPath);
        } finally {
            if ($removePlainPath) {
                @unlink($plainPath);
            }
        }
    }

    private function executePlainRestoreFile(string $plainPath, string $rollbackPath): void
    {
        $this->validateRestoreFile($plainPath);
        $database = DB::connection()->getDatabaseName();
        $temporaryDatabase = substr(preg_replace('/[^a-zA-Z0-9_]/', '_', $database), 0, 40).'_validate_'.Str::lower(Str::random(8));
        DB::statement("CREATE DATABASE `{$temporaryDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        try {
            $this->runMysqlRestore($plainPath, $temporaryDatabase);
            $requiredTables = DB::table('information_schema.tables')->where('table_schema', $temporaryDatabase)
                ->whereIn('table_name', ['users', 'migrations', 'roles', 'permissions'])->pluck('table_name');
            if ($requiredTables->count() !== 4) {
                throw new \RuntimeException('El archivo no contiene la estructura completa de Confipetrol.');
            }
        } finally {
            DB::statement("DROP DATABASE IF EXISTS `{$temporaryDatabase}`");
        }

        Artisan::call('down', ['--render' => 'errors.maintenance']);
        $leaveInMaintenance = false;
        try {
            try {
                $this->runMysqlRestore($plainPath, $database);
                DB::purge();
                DB::reconnect();
            } catch (\Throwable $restoreException) {
                try {
                    $this->restoreSafetyCopy($rollbackPath, $database);
                } catch (\Throwable $rollbackException) {
                    $leaveInMaintenance = true;
                    throw new \RuntimeException(
                        'La restauración y la recuperación automática fallaron. El sistema permanecerá en mantenimiento.',
                        0,
                        $rollbackException
                    );
                }

                throw new \RuntimeException(
                    'La restauración fue cancelada y la base original se recuperó automáticamente.',
                    0,
                    $restoreException
                );
            }
        } finally {
            if (! $leaveInMaintenance) {
                Artisan::call('up');
            }
        }
    }

    private function restoreSafetyCopy(string $rollbackPath, string $database): void
    {
        $this->verifyBackupChecksum($rollbackPath);
        [$plainRollback, $removePlainRollback] = $this->plainRestorePath($rollbackPath);
        try {
            $this->validateRestoreFile($plainRollback);
            $this->runMysqlRestore($plainRollback, $database);
            DB::purge();
            DB::reconnect();
        } finally {
            if ($removePlainRollback) {
                @unlink($plainRollback);
            }
        }
    }

    private function plainRestorePath(string $path): array
    {
        if (! app(BackupCryptoService::class)->isEncrypted($path)) {
            return [$path, false];
        }

        return [app(BackupCryptoService::class)->decryptToTemporary($path), true];
    }

    private function validateRestoreFile(string $path): void
    {
        if (! is_file($path) || filesize($path) < 100) {
            throw new \RuntimeException('El archivo de respaldo está vacío o dañado.');
        }
        $handle = fopen($path, 'rb');
        if (! is_resource($handle)) {
            throw new \RuntimeException('No se pudo leer el archivo de respaldo.');
        }

        $forbiddenPatterns = [
            '/\b(?:CREATE|ALTER|DROP)\s+DATABASE\b/i',
            '/\bUSE\s+(?:`[^`]+`|[A-Z0-9_$-]+)\s*;/i',
            '/\b(?:CREATE|ALTER|DROP|RENAME)\s+USER\b/i',
            '/\b(?:GRANT|REVOKE)\b/i',
            '/\bSET\s+PASSWORD\b/i',
            '/\bINTO\s+(?:OUTFILE|DUMPFILE)\b/i',
            '/\bLOAD\s+(?:DATA|XML)\b/i',
            '/\b(?:INSTALL|UNINSTALL)\s+(?:PLUGIN|SONAME)\b/i',
            '/\bSHUTDOWN\b|\bRESET\s+MASTER\b/i',
            '/\bCHANGE\s+(?:MASTER|REPLICATION)\b|\b(?:START|STOP)\s+REPLICA\b/i',
            '/^\s*(?:SOURCE|SYSTEM|\\!)(?:\s|$)/mi',
            '/(?<![A-Z0-9_`])`?(?:'.preg_quote(DB::connection()->getDatabaseName(), '/').'|mysql|information_schema|performance_schema|sys)`?\s*\./i',
        ];

        $tail = '';
        try {
            while (! feof($handle)) {
                $chunk = fread($handle, 1048576);
                if ($chunk === false) {
                    throw new \RuntimeException('No se pudo leer por completo el archivo de respaldo.');
                }

                $content = $tail.$chunk;
                foreach ($forbiddenPatterns as $pattern) {
                    if (preg_match($pattern, $content) === 1) {
                        throw new \RuntimeException('El respaldo contiene instrucciones SQL no permitidas.');
                    }
                }
                $tail = substr($content, -4096);
            }
        } finally {
            fclose($handle);
        }
    }

    private function verifyBackupChecksum(string $path): void
    {
        $checksumFile = $path.'.sha256';
        if (! is_file($checksumFile)) {
            return;
        }
        $expected = strtok(trim((string) file_get_contents($checksumFile)), " \t");
        if (! hash_equals((string) $expected, hash_file('sha256', $path))) {
            throw new \RuntimeException('El respaldo no superó la verificación de integridad SHA-256.');
        }
    }

    private function runMysqlRestore(string $path, string $database): void
    {
        $mysql = $this->findMysqlClient();
        $config = config('database.connections.'.config('database.default'));
        $args = [$mysql, '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username']];
        $args[] = '--default-character-set=utf8mb4';
        $args[] = $database;
        $environment = getenv();
        if (filled($config['password'])) {
            $environment['MYSQL_PWD'] = $config['password'];
        }
        $process = proc_open($args, [0 => ['file', $path, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
        if (! is_resource($process)) {
            throw new \RuntimeException('No se pudo iniciar el cliente de MySQL.');
        }
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new \RuntimeException('MySQL rechazó el respaldo: '.Str::limit(trim($error), 1000));
        }
    }

    private function findMysqlClient(): string
    {
        $paths = [
            'C:\\xampp\\mysql\\bin\\mysql.exe', 'C:\\xampp8\\mysql\\bin\\mysql.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0\\bin\\mysql.exe', 'C:\\wamp64\\bin\\mysql\\mysql8.4\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe', 'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysql.exe',
        ];
        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        exec(PHP_OS_FAMILY === 'Windows' ? 'where mysql 2>NUL' : 'which mysql 2>/dev/null', $output, $code);
        if ($code === 0 && filled($output[0] ?? null)) {
            return trim($output[0]);
        }

        throw new \RuntimeException('No se encontró el cliente mysql necesario para validar y restaurar respaldos.');
    }

    private function authorizeRestore(): void
    {
        abort_unless(auth()->user()->hasRole('SUPER ADMIN') && auth()->user()->can('restaurar-backup'), 403);
    }

    private function prepareRestore(string $source): string
    {
        $this->writeRestoreAudit('INICIADA', $source);
        if (Artisan::call('backup:database', ['--type' => 'pre_restauracion']) !== 0) {
            throw new \RuntimeException('No se pudo crear el respaldo de seguridad previo. La restauración fue cancelada.');
        }

        $files = array_merge(
            glob(storage_path('app/backups/backup_pre_restauracion_*.cfpbak')) ?: [],
            glob(storage_path('app/backups/backup_pre_restauracion_*.sql')) ?: []
        );
        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));
        if (! isset($files[0])) {
            throw new \RuntimeException('No se encontró el respaldo de seguridad previo.');
        }

        return $files[0];
    }

    private function writeRestoreAudit(string $status, string $source): void
    {
        $record = [
            'timestamp' => now()->toIso8601String(), 'status' => $status, 'source' => basename($source),
            'actor_id' => auth()->id(), 'actor_login' => auth()->user()?->login, 'ip' => request()->ip(),
            'database' => DB::connection()->getDatabaseName(),
        ];
        file_put_contents(storage_path('logs/restore-audit.log'), json_encode($record, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function getBackups(): array
    {
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            return [];
        }

        $files = array_merge(
            glob($backupDir.DIRECTORY_SEPARATOR.'backup_*.cfpbak') ?: [],
            glob($backupDir.DIRECTORY_SEPARATOR.'backup_*.sql') ?: []
        );

        $backups = [];
        foreach ($files as $file) {
            $size = filesize($file);
            $mtime = filemtime($file);

            $filename = basename($file);
            $date = Carbon::createFromTimestamp($mtime, config('app.timezone'));

            $backups[] = [
                'filename' => $filename,
                'size' => $this->formatSize($size),
                'size_bytes' => $size,
                'date' => $date,
                'is_today' => $date->isToday(),
                'type' => $this->backupType($filename),
            ];
        }

        usort($backups, fn ($a, $b) => $b['date'] <=> $a['date']);

        return $backups;
    }

    private function backupType(string $filename): array
    {
        return match (true) {
            str_starts_with($filename, 'backup_pre_restauracion_') => ['label' => 'Pre-restauración', 'class' => 'text-bg-warning'],
            str_starts_with($filename, 'backup_automatico_') => ['label' => 'Automático', 'class' => 'text-bg-info'],
            str_starts_with($filename, 'backup_manual_') => ['label' => 'Manual', 'class' => 'text-bg-primary'],
            default => ['label' => 'Anterior', 'class' => 'text-bg-secondary'],
        };
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }

    public function render()
    {
        return view('livewire.backups.index', [
            'backups' => $this->getBackups(),
            'backupDirSize' => $this->getTotalSize(),
        ])->extends('layouts.theme.app');
    }

    private function getTotalSize(): string
    {
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            return '0 B';
        }

        $files = array_merge(
            glob($backupDir.DIRECTORY_SEPARATOR.'backup_*.cfpbak') ?: [],
            glob($backupDir.DIRECTORY_SEPARATOR.'backup_*.sql') ?: []
        );
        $total = array_sum(array_map('filesize', $files));

        return $this->formatSize($total);
    }

    public function download(string $filename)
    {
        abort_unless(auth()->user()?->hasRole('SUPER ADMIN') && auth()->user()?->can('descargar-backup'), 403);
        $filename = basename($filename);

        if (! $this->isSupportedBackupName($filename)) {
            abort(404);
        }

        $path = storage_path('app/backups/'.$filename);

        if (! file_exists($path)) {
            abort(404);
        }

        $this->logActivity('BACKUPS', 'DESCARGAR', 'Descarga del respaldo '.$filename, null, null, [
            'archivo' => $filename, 'tamaño_bytes' => filesize($path),
        ]);

        return response()->download($path);
    }

    private function isSupportedBackupName(string $filename): bool
    {
        $lower = strtolower($filename);

        return str_ends_with($lower, '.cfpbak') || str_ends_with($lower, '.sql');
    }

    private function dispatchBackupFailure(string $operation, \Throwable $exception): void
    {
        $reference = Str::upper(Str::random(10));
        report(new \RuntimeException('Fallo al '.$operation.' [referencia '.$reference.']', 0, $exception));
        $this->dispatch('backupError', 'No se pudo '.$operation.'. Referencia técnica: '.$reference.'.');
    }
}
