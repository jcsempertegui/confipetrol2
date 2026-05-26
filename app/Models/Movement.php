<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Movement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'type_movements',
        'description',
        'amount',
        'payment_id',
        'cash_box_id',
        'branch_id',
        'user_id',
        'transaction_type',
        'transaction_id',
        'status',
    ];
    public function transaction()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branche::class);
    }

    public function cashBox()
    {
        return $this->belongsTo(CashBoxe::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'transaction_id', 'transaction_id');
    }

}