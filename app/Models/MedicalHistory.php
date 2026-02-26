<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    protected $fillable = [
        'patient_id',
        'is_smoker',
        'previous_surgeries',
        'chronic_diseases',
        'medications',
        'allergies',
        'family_history',
        'previous_surgeries_name',
        'current_complaint',
    ];

    protected $casts = [
        'chronic_diseases' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
