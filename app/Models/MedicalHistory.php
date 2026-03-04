<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class MedicalHistory extends Model
{
    use LogsActivity;
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
