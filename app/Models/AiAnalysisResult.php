<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnalysisResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'response',
        'ai_insight',
        'ai_summary',
        'status',
        'ocr_file_path',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function keyPoints(): HasMany
    {
        return $this->hasMany(KeyPoint::class);
    }

    public function decisionSupports(): HasMany
    {
        return $this->hasMany(DecisionSupport::class);
    }
}
