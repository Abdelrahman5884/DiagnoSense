<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'next_visit_date',
        'status',
        'doctor_id',
        'patient_id',
    ];

    protected $casts = [
        'next_visit_date' => 'datetime',
    ];
    protected array $logOnlyEvents = ['created', 'updated', 'deleted'];

    public function toActivityDisplayName(): string
    {
        return 'Visit on '.\Carbon\Carbon::parse($this->next_visit_date)->format('M d, Y');
    }


    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    public function tasks():HasMany
    {
        return $this->hasMany(Task::class);
    }
}
