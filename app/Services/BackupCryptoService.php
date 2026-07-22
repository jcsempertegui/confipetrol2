<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class BackupCryptoService
{
    private const MAGIC = "CFPBK01\0";

    private const SALT_BYTES = 16;

    private const IV_BYTES = 16;

    private const TAG_BYTES = 32;

    private const CHUNK_BYTES = 1048576;

    public function encryptFile(string $plainPath, ?string $encryptedPath = null, bool $deletePlain = true): string
    {
        if (! is_file($plainPath)) {
            throw new RuntimeException('No se encontró el archivo que se debe cifrar.');
        }

        $encryptedPath ??= preg_replace('/\.sql$/i', '', $plainPath).'.cfpbak';
        $temporaryPath = $encryptedPath.'.tmp-'.Str::lower(Str::random(8));
        $salt = random_bytes(self::SALT_BYTES);
        $iv = random_bytes(self::IV_BYTES);
        [$encryptionKey, $authenticationKey] = $this->deriveKeys($salt);
        $header = self::MAGIC.$salt.$iv;

        $input = fopen($plainPath, 'rb');
        $output = fopen($temporaryPath, 'wb');
        if (! is_resource($input) || ! is_resource($output)) {
            is_resource($input) && fclose($input);
            is_resource($output) && fclose($output);
            @unlink($temporaryPath);

            throw new RuntimeException('No se pudo abrir el respaldo para cifrarlo.');
        }

        $hmac = hash_init('sha256', HASH_HMAC, $authenticationKey);
        try {
            $this->writeAll($output, $header);
            hash_update($hmac, $header);
            $counter = $iv;
            $remaining = filesize($plainPath);

            while ($remaining > 0) {
                $plain = $this->readExact($input, min(self::CHUNK_BYTES, $remaining));
                $cipher = openssl_encrypt($plain, 'aes-256-ctr', $encryptionKey, OPENSSL_RAW_DATA, $counter);
                if ($cipher === false) {
                    throw new RuntimeException('OpenSSL no pudo cifrar el respaldo.');
                }
                $this->writeAll($output, $cipher);
                hash_update($hmac, $cipher);
                $this->incrementCounter($counter, intdiv(strlen($plain) + 15, 16));
                $remaining -= strlen($plain);
            }

            $this->writeAll($output, hash_final($hmac, true));
        } catch (\Throwable $exception) {
            fclose($input);
            fclose($output);
            @unlink($temporaryPath);

            throw $exception;
        }

        fclose($input);
        fclose($output);
        if (! rename($temporaryPath, $encryptedPath)) {
            @unlink($temporaryPath);
            throw new RuntimeException('No se pudo guardar el respaldo cifrado.');
        }
        @chmod($encryptedPath, 0600);

        if ($deletePlain && ! unlink($plainPath)) {
            @unlink($encryptedPath);
            throw new RuntimeException('El respaldo se cifró, pero no se pudo retirar la copia SQL sin cifrar.');
        }

        return $encryptedPath;
    }

    public function decryptToTemporary(string $encryptedPath): string
    {
        $this->verify($encryptedPath);
        $directory = storage_path('app/backups/tmp');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el área temporal de restauración.');
        }

        $temporaryPath = $directory.DIRECTORY_SEPARATOR.'restore-'.Str::lower(Str::random(16)).'.sql';
        $input = fopen($encryptedPath, 'rb');
        $output = fopen($temporaryPath, 'wb');
        if (! is_resource($input) || ! is_resource($output)) {
            is_resource($input) && fclose($input);
            is_resource($output) && fclose($output);
            @unlink($temporaryPath);

            throw new RuntimeException('No se pudo abrir el respaldo cifrado.');
        }

        try {
            $header = $this->readExact($input, strlen(self::MAGIC) + self::SALT_BYTES + self::IV_BYTES);
            $salt = substr($header, strlen(self::MAGIC), self::SALT_BYTES);
            $counter = substr($header, strlen(self::MAGIC) + self::SALT_BYTES, self::IV_BYTES);
            [$encryptionKey] = $this->deriveKeys($salt);
            $remaining = filesize($encryptedPath) - strlen($header) - self::TAG_BYTES;

            while ($remaining > 0) {
                $length = min(self::CHUNK_BYTES, $remaining);
                $cipher = $this->readExact($input, $length);
                $plain = openssl_decrypt($cipher, 'aes-256-ctr', $encryptionKey, OPENSSL_RAW_DATA, $counter);
                if ($plain === false) {
                    throw new RuntimeException('OpenSSL no pudo descifrar el respaldo.');
                }
                $this->writeAll($output, $plain);
                $this->incrementCounter($counter, intdiv(strlen($plain) + 15, 16));
                $remaining -= $length;
            }
        } catch (\Throwable $exception) {
            fclose($input);
            fclose($output);
            @unlink($temporaryPath);

            throw $exception;
        }

        fclose($input);
        fclose($output);
        @chmod($temporaryPath, 0600);

        return $temporaryPath;
    }

    public function verify(string $path): void
    {
        $minimumSize = strlen(self::MAGIC) + self::SALT_BYTES + self::IV_BYTES + self::TAG_BYTES + 1;
        if (! is_file($path) || filesize($path) < $minimumSize) {
            throw new RuntimeException('El respaldo cifrado está vacío o dañado.');
        }

        $handle = fopen($path, 'rb');
        if (! is_resource($handle)) {
            throw new RuntimeException('No se pudo leer el respaldo cifrado.');
        }

        try {
            $headerLength = strlen(self::MAGIC) + self::SALT_BYTES + self::IV_BYTES;
            $header = $this->readExact($handle, $headerLength);
            if (! hash_equals(self::MAGIC, substr($header, 0, strlen(self::MAGIC)))) {
                throw new RuntimeException('El archivo no es un respaldo cifrado válido de Confipetrol.');
            }

            $salt = substr($header, strlen(self::MAGIC), self::SALT_BYTES);
            [, $authenticationKey] = $this->deriveKeys($salt);
            $hmac = hash_init('sha256', HASH_HMAC, $authenticationKey);
            hash_update($hmac, $header);
            $remaining = filesize($path) - $headerLength - self::TAG_BYTES;

            while ($remaining > 0) {
                $chunk = $this->readExact($handle, min(self::CHUNK_BYTES, $remaining));
                hash_update($hmac, $chunk);
                $remaining -= strlen($chunk);
            }

            $expected = $this->readExact($handle, self::TAG_BYTES);
            if (! hash_equals($expected, hash_final($hmac, true))) {
                throw new RuntimeException('El respaldo cifrado no superó la verificación de autenticidad.');
            }
        } finally {
            fclose($handle);
        }
    }

    public function isEncrypted(string $path): bool
    {
        if (str_ends_with(strtolower($path), '.cfpbak')) {
            return true;
        }
        if (! is_file($path)) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if (! is_resource($handle)) {
            return false;
        }
        $magic = fread($handle, strlen(self::MAGIC));
        fclose($handle);

        return is_string($magic) && hash_equals(self::MAGIC, $magic);
    }

    private function deriveKeys(string $salt): array
    {
        $material = hash_hkdf('sha512', $this->applicationKey(), 64, 'confipetrol2-backup-v1', $salt);

        return [substr($material, 0, 32), substr($material, 32, 32)];
    }

    private function applicationKey(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            $key = $decoded === false ? '' : $decoded;
        }
        if (strlen($key) < 16) {
            throw new RuntimeException('APP_KEY no es válida para proteger los respaldos.');
        }

        return $key;
    }

    private function incrementCounter(string &$counter, int $blocks): void
    {
        $carry = $blocks;
        for ($index = strlen($counter) - 1; $index >= 0 && $carry > 0; $index--) {
            $sum = ord($counter[$index]) + ($carry & 0xFF);
            $counter[$index] = chr($sum & 0xFF);
            $carry = intdiv($carry, 256) + intdiv($sum, 256);
        }
        if ($carry > 0) {
            throw new RuntimeException('El contador criptográfico excedió su capacidad.');
        }
    }

    private function readExact($handle, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length && ! feof($handle)) {
            $chunk = fread($handle, $length - strlen($buffer));
            if ($chunk === false) {
                throw new RuntimeException('No se pudo leer por completo el respaldo.');
            }
            $buffer .= $chunk;
        }
        if (strlen($buffer) !== $length) {
            throw new RuntimeException('El respaldo está truncado o dañado.');
        }

        return $buffer;
    }

    private function writeAll($handle, string $contents): void
    {
        $written = 0;
        $length = strlen($contents);
        while ($written < $length) {
            $result = fwrite($handle, substr($contents, $written));
            if ($result === false || $result === 0) {
                throw new RuntimeException('No se pudo escribir por completo el respaldo.');
            }
            $written += $result;
        }
    }
}
