<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DoctorService
{
    public function getDoctorProfileData(User $user): User
    {
        $user['specialization'] = $user->doctor->specialization;

        return $user;
    }

    public function updateProfile(Doctor $doctor, array $data): void
    {
        DB::transaction(function () use ($doctor, $data) {
            if (isset($data['name']) && $data['name'] !== $doctor->user->name) {
                $doctor->user->update(['name' => $data['name']]);
            }

            $doctorData = collect($data)->except('name')->toArray();
            if (! empty($doctorData) && $doctorData !== $doctor->only(array_keys($doctorData))) {
                $doctor->update($doctorData);
            }
        });
    }

    public function deleteDoctorAccount(User $user): void
    {
        $user->doctor()->delete();
        $user->tokens()->delete();
        $user->delete();
    }
}
