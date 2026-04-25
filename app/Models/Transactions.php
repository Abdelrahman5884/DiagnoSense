<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $fillable = [
        'amount',
        'type',
        'status',
        'sourceable_id',
        'sourceable_type',
        'description',
        'doctor_id',
        'payment_id',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function source()
    {
        return $this->morphTo();
    }
}
