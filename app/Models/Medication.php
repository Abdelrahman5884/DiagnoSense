<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'dosage',
        'frequency',
        'doctor_id',
        'duration',
        'visit_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}
