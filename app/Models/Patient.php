<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'age',
        'gender',
        'national_id',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function Rooms()
    {
        return $this->hasMany(Room::class);
    }
    public function medicalHistory()
    {
        return $this->hasOne(MedicalHistory::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }


}
