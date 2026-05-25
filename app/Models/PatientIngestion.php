<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientIngestion extends Model
{
    protected $table = 'patient_ingestions';

    protected $fillable = [
        'patient_id',
        'status',
        'error_message',
        'file_hash',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
