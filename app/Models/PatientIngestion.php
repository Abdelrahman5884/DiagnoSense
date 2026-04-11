<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientIngestion extends Model
{
    protected $table = 'patient_ingestions';

    protected $fillable = [
        'patient_id',
        'status',
        'error_message',
        'files_hash',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
