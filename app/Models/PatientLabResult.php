<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientLabResult extends Model
{
    use HasFactory;
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
