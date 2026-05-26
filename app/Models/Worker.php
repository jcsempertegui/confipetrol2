<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'document',
        'cargo',
        'birth_date',
        'phone',
        'status',
    ];
}
