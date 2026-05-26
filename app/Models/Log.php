<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Log extends Model
{
     use HasFactory;

    // Agregar los campos que se pueden asignar masivamente
    protected $fillable = [
        'user_id', 
        'evento',
        'ip',
        'detalle',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}