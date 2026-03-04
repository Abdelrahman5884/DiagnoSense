<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class KeyPoint extends Model
{
    use SoftDeletes;
    use LogsActivity;
    protected $fillable = [
        'ai_analysis_result_id',
        'priority',
        'title',
        'insight',
        'is_manual',
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
