<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeyPoint extends Model
{
    use LogsActivity , SoftDeletes ,  HasFactory;

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
