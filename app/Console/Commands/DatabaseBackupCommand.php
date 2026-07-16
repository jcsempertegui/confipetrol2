<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'backup:database {--type=manual : Tipo de respaldo: manual, automatico o pre_restauracion}';

    protected $description = 'Crea un backup completo de la base de datos MySQL';

    public function handle(): int
    {
        $config = config('database.connections.mysql');
        $host = $config['host'];
        $port = $config['port'];
        $database = trim($config['database']);
        $username = $config['username'];
        $password = $config['password'];

        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $type = (string) $this->option('type');
        if (! in_array($type, ['manual', 'automatico', 'pre_restauracion'], true)) {
            $this->error('Tipo de respaldo inválido.');

            return 1;
        }

        $filename = 'backup_'.$type.'_'.Carbon::now(config('app.timezone'))->format('Y-m-d_H-i-s').'.sql';
        $filepath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $mysqldump = $this->findMysqldump();

        if (! $mysqldump) {
            $this->error('mysqldump no encontrado. Verifique que MySQL esté instalado y en el PATH del sistema.');

            return 1;
        }

        $args = [$mysqldump];
        $args[] = '--host='.$host;
        $args[] = '--port='.$port;
        $args[] = '--user='.$username;

        if (! empty($password)) {
            $args[] = '--password='.$password;
        }

        $args[] = '--single-transaction';
        $args[] = '--routines';
        $args[] = '--triggers';
        $args[] = '--add-drop-table';
        $args[] = $database;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $filepath, 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($args, $descriptors, $pipes);

        if (! is_resource($process)) {
            $this->error('No se pudo iniciar el proceso de backup.');

            return 1;
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);

        if ($returnCode !== 0 || ! file_exists($filepath) || filesize($filepath) === 0) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $this->error('El backup falló. Error: '.$stderr);

            return 1;
        }

        $this->cleanOldBackups($backupDir);

        $size = $this->formatSize(filesize($filepath));
        $this->info("Backup creado exitosamente: {$filename} ({$size})");

        return 0;
    }

    private function findMysqldump(): ?string
    {
        $windowsPaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp8\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.4\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.0\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.4\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysqldump.exe',
        ];

        foreach ($windowsPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try system PATH
        exec('where mysqldump 2>NUL', $out, $code);
        if ($code === 0 && ! empty($out)) {
            return trim($out[0]);
        }

        // Linux/Mac fallback
        exec('which mysqldump 2>/dev/null', $out2, $code2);
        if ($code2 === 0 && ! empty($out2)) {
            return trim($out2[0]);
        }

        return null;
    }

    private function cleanOldBackups(string $backupDir): void
    {
        $files = glob($backupDir.DIRECTORY_SEPARATOR.'backup_*.sql');

        if (! $files || count($files) <= 30) {
            return;
        }

        usort($files, fn ($a, $b) => filemtime($a) <=> filemtime($b));

        foreach (array_slice($files, 0, count($files) - 30) as $old) {
            unlink($old);
        }
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
}
