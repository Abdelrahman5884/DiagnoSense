<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    protected $fillable = [
        'patient_id',
        'type',
        'file_name',
        'file_path',
        'mime_type',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
