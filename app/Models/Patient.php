<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
class Patient extends Model
{
    use LogsActivity;
    protected $fillable = [
        'id',
        'user_id',
        'name',
        'email',
        'phone',
        'age',
        'gender',
        'national_id',
        'status',
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

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function medications()
    {
        return $this->hasMany(Medication::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    public function latestAiAnalysisResult()
    {
        return $this->hasOne(AiAnalysisResult::class)->latest();
    }
    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'model');
    }
}
