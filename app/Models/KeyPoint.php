<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeyPoint extends Model
{
    use HasFactory , LogsActivity ,  SoftDeletes;

    protected array $logOnlyEvents = ['created', 'updated', 'deleted'];

    public function toActivityDisplayName(): string
    {
        return 'Doctor Note';
    }

    public function getActivityPatientId(): ?int
    {
        if ($this->aiAnalysisResult) {
            return $this->aiAnalysisResult->patient_id;
        }

        if (isset($this->ai_analysis_result_id)) {
            return AiAnalysisResult::find($this->ai_analysis_result_id)?->patient_id;
        }

        return null;
    }

    protected $fillable = [
        'ai_analysis_result_id',
        'priority',
        'title',
        'insight',
        'is_ai_generated',
        'evidence',
    ];

    protected $casts = [
        'evidence' => 'array',
    ];

    public function aiAnalysisResult()
    {
        return $this->belongsTo(AiAnalysisResult::class);
    }
}
