<?php

namespace App\Traits;

use App\Models\Log as AuditModel;

trait AuditLog
{
    /**
     * Registra una acción en el log de auditoría del sistema.
     *
     * @param  string  $modulo  Módulo del sistema (USUARIOS, REMITOS, PRODUCTOS, etc.)
     * @param  string  $accion  Acción realizada (CREAR, EDITAR, ELIMINAR, RESTAURAR, ANULAR)
     * @param  string  $descripcion  Descripción legible del evento
     * @param  int|null  $modeloId  ID del registro afectado
     * @param  array|null  $valoresAnteriores  Valores antes del cambio
     * @param  array|null  $valoresNuevos  Valores después del cambio
     */
    protected function logActivity(
        string $modulo,
        string $accion,
        string $descripcion,
        ?int $modeloId = null,
        ?array $valoresAnteriores = null,
        ?array $valoresNuevos = null
    ): void {
        try {
            $anteriores = $this->sanitizeAuditValues($valoresAnteriores);
            $nuevos = $this->sanitizeAuditValues($valoresNuevos);
            AuditModel::create([
                'user_id' => auth()->id(),
                'actor_login' => auth()->user()?->login ?? 'Sistema',
                'modulo' => strtoupper($modulo),
                'accion' => strtoupper($accion),
                'descripcion' => $descripcion,
                'modelo_id' => $modeloId,
                'valores_anteriores' => $anteriores ?: null,
                'valores_nuevos' => $nuevos ?: null,
                'ip' => request()->ip(),
            ]);
        } catch (\Throwable) {
            // No romper la app por un error de log
        }
    }

    private function sanitizeAuditValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $hidden = ['password', 'password_confirmation', 'remember_token', 'token'];

        return collect($values)
            ->reject(fn ($value, $key) => in_array(strtolower((string) $key), $hidden, true))
            ->map(function ($value) {
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d H:i:s');
                }
                if (is_array($value)) {
                    return $this->sanitizeAuditValues($value);
                }

                return $value;
            })->all();
    }
}
