<?php

namespace App\Models;

use App\Models\Lot;
use Illuminate\Database\Eloquent\Model;

class DetailLot extends Model
{
    protected $fillable = [
        'detail_type',
        'detail_id',
        'lot_id',
        'quantity',
    ];

    public function detail()
    {
        return $this->morphTo();
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }
}