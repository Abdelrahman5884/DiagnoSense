<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

class DoctorService
{
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
}
