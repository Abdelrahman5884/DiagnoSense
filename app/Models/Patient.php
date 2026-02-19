<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'age',
        'gender',
        'national_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doctor()
    {
        return $this->belongsToMany(Doctor::class, 'doctors_patients', 'patient_id', 'doctor_id');
    }

    public function medicalHistory()
    {
        return $this->hasOne(MedicalHistory::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
