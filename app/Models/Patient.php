<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'id',
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

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_patient', 'patient_id', 'doctor_id');
    }

    public function medicalHistory()
    {
        return $this->hasOne(MedicalHistory::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function aiAnalysisResults()
    {
        return $this->hasMany(AiAnalysisResult::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function medications()
    {
        return $this->hasMany(Medication::class);
    }
}
