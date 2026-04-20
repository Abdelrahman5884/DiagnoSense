<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory;
    use LogsActivity , SoftDeletes;

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
        'last_visit_date',
        'next_visit_date',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth ? $this->date_of_birth->age : null,
        );
    }

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

    public function visits(): HasMany
    {
        return $this->hasMany(related: Visit::class);
    }

    public function latestVisit(): HasOne
    {
        return $this->hasOne(related: Visit::class)->latestOfMany();
    }

    public function medications()
    {
        return $this->hasMany(Medication::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function latestAiAnalysisResult()
    {
        return $this->hasOne(AiAnalysisResult::class)->latest();
    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'model');
    }

    public function refreshVisitDates($newDate)
    {
        if ($newDate && $this->next_visit_date != $newDate) {
            $this->update([
                'last_visit_date' => $this->next_visit_date ?? $this->last_visit_date,
                'next_visit_date' => $newDate,
            ]);
        }
    }
}
