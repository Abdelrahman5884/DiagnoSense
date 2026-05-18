<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function view(User $user, Patient $patient): bool
    {
        return $user->doctor && $user->doctor->patients()->where('patients.id', $patient->id)->exists();
    }
}
