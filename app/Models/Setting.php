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
        'license_plan',
        'payment_type',
        'license_start_date',
        'license_end_date',
        'months_paid',
        'years_paid',
    ];

    public function branch()
    {
        return $this->belongsTo(Branche::class);
    }
}