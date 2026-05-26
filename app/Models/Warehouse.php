<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch_id',
        'is_default',
        'status',
    ];

    public function branch()
    {
        return $this->belongsTo(Branche::class, 'branch_id');
    }
}