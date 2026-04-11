<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientLabResult extends Model
{
    protected $fillable = [
        'patient_id',
        'ai_analysis_result_id',
        'category',
        'standard_name',
        'numeric_value',
        'unit',
        'status',
        'created_at',
    ];
}
