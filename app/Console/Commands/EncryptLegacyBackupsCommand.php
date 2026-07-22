<?php

namespace App\Console\Commands;

use App\Services\BackupCryptoService;
use Illuminate\Console\Command;

class EncryptLegacyBackupsCommand extends Command
{
    protected $signature = 'backup:encrypt-legacy';

    protected $description = 'Cifra los respaldos SQL antiguos y elimina las copias sin cifrar';

    public function handle(BackupCryptoService $crypto): int
    {
        $files = glob(storage_path('app/backups/backup_*.sql')) ?: [];
        foreach ($files as $file) {
            $encrypted = $crypto->encryptFile($file);
            if (is_file($file.'.sha256')) {
                unlink($file.'.sha256');
            }
            file_put_contents($encrypted.'.sha256', hash_file('sha256', $encrypted).'  '.basename($encrypted).PHP_EOL, LOCK_EX);
            $this->line('Cifrado: '.basename($file).' → '.basename($encrypted));
        }

        $this->info(count($files).' respaldo(s) antiguo(s) protegidos.');

        return self::SUCCESS;
    }
}
