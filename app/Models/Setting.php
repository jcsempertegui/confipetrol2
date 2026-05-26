<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business',
        'owner',
        'nit',
        'email',
        'image',
        'message',
        'branch_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branche::class);
    }
}
