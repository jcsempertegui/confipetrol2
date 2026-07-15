<?php

namespace App\Livewire;

use App\Traits\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
            $result = Artisan::call('backup:database');

            if ($result === 0) {
                $latest = collect($this->getBackups())->first();
                $this->logActivity('BACKUPS', 'CREAR', 'Creación de respaldo de base de datos', null, null, $latest ? ['archivo' => $latest['filename'], 'tamaño' => $latest['size']] : null);
                $this->dispatch('backupSuccess', 'Backup creado correctamente.');
            } else {
                $output = Artisan::output();
                $this->dispatch('backupError', 'Error al crear el backup. '.$output);
            }
        } catch (\Throwable $e) {
            $this->dispatch('backupError', 'Error inesperado: '.$e->getMessage());
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

        if (! str_ends_with($filename, '.sql')) {
            return;
        }

        $path = storage_path('app/backups/'.$filename);

        if (file_exists($path)) {
            $before = ['archivo' => $filename, 'tamaño_bytes' => filesize($path), 'fecha' => date('Y-m-d H:i:s', filemtime($path))];
            unlink($path);
            $this->logActivity('BACKUPS', 'ELIMINAR', 'Eliminación del respaldo '.$filename, null, $before, null);
            $this->dispatch('backupSuccess', 'Backup eliminado correctamente.');
        }
    }

    public function confirmRestoreFromList(string $filename): void
    {
        abort_unless(auth()->user()->can('restaurar-backup'), 403);
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
            $this->executeRestoreFile($path);
            $this->logActivity('BACKUPS', 'RESTAURAR', 'Restauración de base de datos', null, ['base_de_datos' => DB::connection()->getDatabaseName()], ['archivo_restaurado' => $filename]);
            $this->dispatch('backupSuccess', 'Base de datos restaurada desde: '.$filename);
        } catch (\Throwable $e) {
            $this->dispatch('backupError', 'Error al restaurar: '.$e->getMessage());
        }

        $this->isRestoring = false;
    }

    public function uploadAndRestore(): void
    {
        abort_unless(auth()->user()->can('restaurar-backup'), 403);
        $this->validate(
            ['sqlFile' => 'required|file|max:102400'],
            [
                'sqlFile.required' => 'Seleccione un archivo .sql',
                'sqlFile.max' => 'El archivo no puede superar 100MB',
            ]
        );

        if (strtolower($this->sqlFile->getClientOriginalExtension()) !== 'sql') {
            $this->addError('sqlFile', 'Solo se permiten archivos .sql');

            return;
        }

        $this->isRestoring = true;

        try {
            $originalName = $this->sqlFile->getClientOriginalName();
            $this->executeRestoreFile($this->sqlFile->getRealPath());
            $this->logActivity('BACKUPS', 'RESTAURAR', 'Restauración desde archivo subido', null, ['base_de_datos' => DB::connection()->getDatabaseName()], ['archivo_restaurado' => $originalName]);
            $this->sqlFile = null;
            $this->dispatch('backupSuccess', 'Base de datos restaurada desde archivo subido.');
        } catch (\Throwable $e) {
            $this->dispatch('backupError', 'Error al restaurar: '.$e->getMessage());
        }

        $this->isRestoring = false;
    }

    private function executeRestoreFile(string $path): void
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
        $handle = null;

        try {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new \RuntimeException('No se pudo abrir el archivo de respaldo.');
            }

            $statement = '';
            while (($line = fgets($handle)) !== false) {
                $trimmed = rtrim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                    continue;
                }
                $statement .= $line;
                if (str_ends_with(rtrim($trimmed), ';')) {
                    $clean = trim($statement);
                    if ($clean !== '') {
                        DB::unprepared($clean);
                    }
                    $statement = '';
                }
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function getBackups(): array
    {
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir.DIRECTORY_SEPARATOR.'backup_*.sql') ?: [];

        $backups = [];
        foreach ($files as $file) {
            $size = filesize($file);
            $mtime = filemtime($file);

            $backups[] = [
                'filename' => basename($file),
                'size' => $this->formatSize($size),
                'size_bytes' => $size,
                'date' => Carbon::createFromTimestamp($mtime),
                'is_today' => Carbon::createFromTimestamp($mtime)->isToday(),
            ];
        }

        usort($backups, fn ($a, $b) => $b['date'] <=> $a['date']);

        return $backups;
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

        $files = glob($backupDir.DIRECTORY_SEPARATOR.'backup_*.sql') ?: [];
        $total = array_sum(array_map('filesize', $files));

        return $this->formatSize($total);
    }

    public function download(string $filename)
    {
        $filename = basename($filename);

        if (! str_ends_with($filename, '.sql')) {
            abort(404);
        }

        $path = storage_path('app/backups/'.$filename);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }
}
