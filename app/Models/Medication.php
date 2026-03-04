<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Medication extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'dosage',
        'frequency',
        'patient_id',
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
