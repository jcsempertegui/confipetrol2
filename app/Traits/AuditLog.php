<?php

namespace App\Traits;

use App\Models\Log as AuditModel;

trait AuditLog
{
    /**
     * Registra una acción en el log de auditoría del sistema.
     *
     * @param string      $modulo           Módulo del sistema (USUARIOS, REMITOS, PRODUCTOS, etc.)
     * @param string      $accion           Acción realizada (CREAR, EDITAR, ELIMINAR, RESTAURAR, ANULAR)
     * @param string      $descripcion      Descripción legible del evento
     * @param int|null    $modeloId         ID del registro afectado
     * @param array|null  $valoresAnteriores Valores antes del cambio
     * @param array|null  $valoresNuevos    Valores después del cambio
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
            AuditModel::create([
                'user_id'            => auth()->id(),
                'modulo'             => strtoupper($modulo),
                'accion'             => strtoupper($accion),
                'descripcion'        => $descripcion,
                'modelo_id'          => $modeloId,
                'valores_anteriores' => $valoresAnteriores ? json_encode($valoresAnteriores, JSON_UNESCAPED_UNICODE) : null,
                'valores_nuevos'     => $valoresNuevos ? json_encode($valoresNuevos, JSON_UNESCAPED_UNICODE) : null,
                'ip'                 => request()->ip(),
            ]);
        } catch (\Throwable) {
            // No romper la app por un error de log
        }
    }
}
