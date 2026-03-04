<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        //        'specialization',
        //        'phone',
        //        'profile_image',
        //        'bio',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'doctor_patient', 'doctor_id', 'patient_id');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function medications()
    {
        return $this->hasMany(Medication::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
