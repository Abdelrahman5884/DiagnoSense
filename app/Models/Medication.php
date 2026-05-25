<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medication extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'dosage',
        'frequency',
        'doctor_id',
        'duration',
        'visit_id',
    ];
    protected array $logOnlyEvents = ['created', 'updated', 'deleted'];

    public function toActivityDisplayName(): string
    {
        return "Medication: '{$this->name}'";
    }


    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit(): BelongsTo

    {
        return $this->belongsTo(Visit::class);
    }
}
