<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use LogsActivity;

    protected $fillable = [
        'next_visit_date',
        'status',
        'doctor_id',
        'patient_id',
    ];

    protected $casts = [
        'next_visit_date' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function medications()
    {
        return $this->hasMany(Medication::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
