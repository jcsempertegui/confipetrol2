<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Installment extends Model
{
    use HasFactory;
    protected $fillable = [
        'amount',
        'credit_id',
        'status',
        'cash_box_id',
        'user_id',
    ];
}
