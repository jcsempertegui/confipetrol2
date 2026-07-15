<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'actor_login',
        'modulo',
        'accion',
        'descripcion',
        'modelo_id',
        'valores_anteriores',
        'valores_nuevos',
        'ip',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function changes(): array
    {
        $before = $this->flattenValues($this->normalizeValues($this->valores_anteriores));
        $after = $this->flattenValues($this->normalizeValues($this->valores_nuevos));
        $changes = [];

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;
            if ($old !== $new) {
                $changes[] = ['field' => $field, 'before' => $old, 'after' => $new];
            }
        }

        return $changes;
    }

    private function normalizeValues(mixed $values): array
    {
        if (is_array($values)) {
            return $values;
        }
        if (is_string($values)) {
            $decoded = json_decode($values, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function flattenValues(array $values, string $prefix = ''): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $field = $prefix === '' ? (string) $key : $prefix.' › '.$key;
            if (is_array($value)) {
                $result += $this->flattenValues($value, $field);
            } else {
                $result[$field] = is_bool($value) ? ($value ? 'Sí' : 'No') : $value;
            }
        }

        return $result;
    }
}
