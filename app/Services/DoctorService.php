<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

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
}
