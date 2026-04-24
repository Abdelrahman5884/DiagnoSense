<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorService
{
    public function updateProfile(Doctor $doctor, array $data): void
    {
        DB::transaction(function () use ($doctor, $data) {
            if (isset($data['name']) && $data['name'] !== $doctor->user->name) {
                $doctor->user->update(['name' => $data['name']]);
            }

            $doctorData = collect($data)->except('name')->toArray();
            if (!empty($doctorData) && $doctorData !== $doctor->only(array_keys($doctorData))) {
                $doctor->update($doctorData);
            }
        });
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
        $user->tokens()->delete();
    }
}
