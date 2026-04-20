<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'changeable_id',
        'changeable_type',
        'action',
        'description',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    public function changeable(): MorphTo
    {
        return $this->morphTo();
    }
}
