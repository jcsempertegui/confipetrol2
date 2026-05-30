<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class BackupsController extends Component
{
    public bool $isCreating = false;

    protected $listeners = ['confirmDelete'];

    public function createBackup(): void
    {
        $this->isCreating = true;

        try {
            $result = Artisan::call('backup:database');

            if ($result === 0) {
                $this->dispatch('backupSuccess', 'Backup creado correctamente.');
            } else {
                $output = Artisan::output();
                $this->dispatch('backupError', 'Error al crear el backup. ' . $output);
            }
        } catch (\Throwable $e) {
            $this->dispatch('backupError', 'Error inesperado: ' . $e->getMessage());
        }

        $this->isCreating = false;
    }

    public function confirmDelete(string $filename): void
    {
        $this->deleteBackup($filename);
    }

    public function deleteBackup(string $filename): void
    {
        $filename = basename($filename);

        if (!str_ends_with($filename, '.sql')) {
            return;
        }

        $path = storage_path('app/backups/' . $filename);

        if (file_exists($path)) {
            unlink($path);
            $this->dispatch('backupSuccess', 'Backup eliminado correctamente.');
        }
    }

    private function getBackups(): array
    {
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql') ?: [];

        $backups = [];
        foreach ($files as $file) {
            $size  = filesize($file);
            $mtime = filemtime($file);

            $backups[] = [
                'filename'   => basename($file),
                'size'       => $this->formatSize($size),
                'size_bytes' => $size,
                'date'       => Carbon::createFromTimestamp($mtime),
                'is_today'   => Carbon::createFromTimestamp($mtime)->isToday(),
            ];
        }

        usort($backups, fn($a, $b) => $b['date'] <=> $a['date']);

        return $backups;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function render()
    {
        return view('livewire.backups.index', [
            'backups'       => $this->getBackups(),
            'backupDirSize' => $this->getTotalSize(),
        ])->extends('layouts.theme.app');
    }

    private function getTotalSize(): string
    {
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            return '0 B';
        }

        $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql') ?: [];
        $total = array_sum(array_map('filesize', $files));

        return $this->formatSize($total);
    }

    public function download(string $filename)
    {
        $filename = basename($filename);

        if (!str_ends_with($filename, '.sql')) {
            abort(404);
        }

        $path = storage_path('app/backups/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }
}
