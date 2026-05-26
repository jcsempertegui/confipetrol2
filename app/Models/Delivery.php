<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Delivery extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'delivery_number',
        'observations',
        'status',
        'worker_id',
        'branch_id',
        'user_id',
        'created_at',
        'updated_at',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branche::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(DeliveryDetail::class);
    }
}
