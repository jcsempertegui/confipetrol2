<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
        'valores_nuevos'     => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
