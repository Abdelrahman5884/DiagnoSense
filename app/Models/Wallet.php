<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Wallet extends Model
{
    protected $fillable = [
        'balance',
        'doctor_id',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transactions::class, 'source');
    }
}
