<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'sale_number',
        'order_number',
        'order_type',
        'observations',
        'total',
        'discount',
        'status',
        'customer_id',
        'cash_box_id',
        'branch_id',
        'user_id',
        'table_id',      
        'waiter_id',
        'created_at',
        'updated_at',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branche::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }
    
    public function payments()
    {
        return $this->hasMany(Payment::class, 'transaction_id', 'id');
    }
    
    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }
}