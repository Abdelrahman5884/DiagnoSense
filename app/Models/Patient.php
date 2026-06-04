<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory , LogsActivity , SoftDeletes;

    const STATUS_CRITICAL = 'critical';

    const STATUS_STABLE = 'stable';

    const STATUS_UNDER_REVIEW = 'under review';

    protected $fillable = [
        'id',
        'user_id',
        'date_of_birth',
        'gender',
        'national_id',
        'status',
        'last_visit_date',
        'next_visit_date',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];
    protected array $logOnlyEvents = ['updated'];

    public function toActivityDisplayName(): string
    {
        return $this->user?->name ?? 'Unknown Patient';
    }

    public function getActivityPatientId(): int
    {
        return $this->id;
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth ? $this->date_of_birth->age : null,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_patient', 'patient_id', 'doctor_id')
            ->withTimestamps();
    }

    public function medicalHistory(): HasOne
    {
        return $this->hasOne(MedicalHistory::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function aiAnalysisResults(): HasMany
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

    public function medications(): HasManyThrough
    {
        return $this->hasManyThrough(
            Medication::class,
            Visit::class,
            'patient_id',
            'visit_id',
            'id',
            'id'
        );
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(
            Task::class,
            Visit::class,
            'patient_id',
            'visit_id',
            'id',
            'id'
        );
    }

    public function latestAiAnalysisResult(): HasOne
    {
        return $this->hasOne(AiAnalysisResult::class)->latest();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'patient_id');
    }

    public function refreshVisitDates(?string $newDate = null): void
    {
        if ($newDate && $this->next_visit_date != $newDate) {
            $this->update([
                'last_visit_date' => $this->next_visit_date ?? $this->last_visit_date,
                'next_visit_date' => $newDate,
            ]);
        }
    }

    public function labResults(): HasMany
    {
        return $this->hasMany(PatientLabResult::class);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_CRITICAL,
            self::STATUS_STABLE,
            self::STATUS_UNDER_REVIEW,
        ];
    }
    public function latestAiAnalysisValue(string $column): ?string
    {
        return $this->aiAnalysisResults()
        ->whereNotNull($column)
        ->where($column, '!=', '')
        ->latest('id')
        ->value($column);
    }
}
