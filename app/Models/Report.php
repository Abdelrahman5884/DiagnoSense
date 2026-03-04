<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Report extends Model
{
    use LogsActivity;
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
