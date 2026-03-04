<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class AiAnalysisResult extends Model
{
    use LogsActivity;

    protected $fillable = [
        'patient_id',
        'response',
        'ai_insight',
        'ai_summary',
        'status',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function keyPoints()
    {
        return $this->hasMany(KeyPoint::class);
    }

    public function decisionSupports()
    {
        return $this->hasMany(DecisionSupport::class);
    }
}
