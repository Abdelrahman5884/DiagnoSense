<?php

namespace App\Policies;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\Task;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Auth\Access\Response;

class VisitPolicy
{
    public function store(User $user, Patient $patient): Response
    {
        $doctor = $user->doctor;
        if (! $doctor) {
            return Response::deny('User is not a doctor.');
        }

        return $doctor->patients()->where('patients.id', $patient->id)->exists()
            ? Response::allow()
            : Response::deny('You do not have permission to create a visit for this patient.');
    }

    public function manage(User $user, Visit $visit): Response
    {
        $doctor = $user->doctor;
        if (! $doctor) {
            return Response::deny('User is not a doctor.');
        }

        return $doctor->visits()->where('visits.id', $visit->id)->exists()
            ? Response::allow()
            : Response::deny('You do not have permission to manage this visit.');
    }

    public function deleteTask(User $user, Task $task): Response
    {
        $doctor = $user->doctor;
        if (! $doctor) {
            return Response::deny('User is not a doctor.');
        }

        return $doctor->visits()->where('visits.id', $task->visit_id)->exists()
            ? Response::allow()
            : Response::deny('You do not have permission to delete this task.');
    }

    public function deleteMedication(User $user, Medication $medication): Response
    {
        $doctor = $user->doctor;
        if (! $doctor) {
            return Response::deny('User is not a doctor.');
        }

        return $doctor->visits()->where('visits.id', $medication->visit_id)->exists()
            ? Response::allow()
            : Response::deny('You do not have permission to delete this medication.');
    }
}
