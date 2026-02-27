<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeyPoint extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'ai_analysis_result_id',
        'priority',
        'title',
        'insight',
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
