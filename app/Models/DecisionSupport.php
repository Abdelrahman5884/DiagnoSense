<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionSupport extends Model
{
    use HasFactory;

    protected $fillable = ['condition', 'probability', 'status', 'clinical_reasoning', 'ai_analysis_result_id'];

    public function aiAnalysisResult(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisResult::class);
    }
}
