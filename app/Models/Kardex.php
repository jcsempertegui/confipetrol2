<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kardex extends Model
{
    protected $table = 'kardexs';
    use HasFactory;
    protected $fillable = [
        'type',
        'description',
        'quantity_in',
        'quantity_out',
        'balance',
        'price',
        'total',
        'product_id',
        'user_id',
        'warehouse_id',
        'transaction_id',
        'transaction_type',
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
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
