<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class DecisionSupport extends Model
{
    use LogsActivity;

    protected $fillable = ['condition', 'probability', 'status', 'clinical_reasoning', 'ai_analysis_result_id'];

    public function aiAnalysisResult()
    {
        return $this->belongsTo(AiAnalysisResult::class);
    }
}
