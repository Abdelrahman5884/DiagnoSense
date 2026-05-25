<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
