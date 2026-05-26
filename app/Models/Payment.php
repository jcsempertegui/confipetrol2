<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'amount',
        'transaction_id',
        'transaction_type',
    ];

    public function transaction()
    {
        return $this->morphTo();
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'transaction_id')->where('transaction_type', 'sales');
    }
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'transaction_id')->where('transaction_type', 'sales');
    }
}