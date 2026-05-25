<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'is_smoker',
        'chronic_diseases',
        'current_medications',
        'allergies',
        'family_history',
        'previous_surgeries_name',
        'current_complaints',
    ];

    protected $casts = [
        'chronic_diseases' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
