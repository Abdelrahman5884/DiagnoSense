<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorService
{
    public function updateProfile(Doctor $doctor, array $data): Doctor
    {
        return DB::transaction(function () use ($doctor, $data) {
            $doctor->user()->update([
                'name' => $data['name'],
            ]);

            if (isset($data['specialization'])) {
                $doctor->update([
                    'specialization' => $data['specialization'],
                ]);
            }

            return $doctor->load('user');
        });
    }
    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword)
        ]);
        $user->tokens()->delete();
    }

}
