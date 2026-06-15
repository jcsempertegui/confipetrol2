<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Remito extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'remito_number',
        'tipo',
        'contrato',
        'senores',
        'atencion',
        'campo',
        'n_orden',
        'observations',
        'despachado_por',
        'transportado_por',
        'placa',
        'status',
        'branch_id',
        'user_id',
        'worker_id',
        'created_at',
        'updated_at',
    ];

    public function branch()
    {
        return $this->belongsTo(Branche::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function details()
    {
        return $this->hasMany(RemitoDetail::class);
    }
}
