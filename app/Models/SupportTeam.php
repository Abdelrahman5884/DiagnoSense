<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTeam extends Model
{
    protected $fillable = [
        'doctor_id',
        'name',
        'identity',
        'category',
        'urgency',
        'message',
        'attachment_path',
        'status',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
