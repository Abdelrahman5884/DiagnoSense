<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Ahmed Zaki', 'Sara Aly', 'Mona Zaid', 'Omaima Hassan', 'Kamal Nour'];

        foreach ($names as $index => $name) {

            $userId = DB::table('users')->insertGetId([
                'name' => $name,
                'contact' => 'p_'.time().rand(1, 999).'@diagnosense.com',
                'contact_verified_at' => now(),
                'password' => bcrypt('password123'),
                'type' => 'patient',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $patientId = DB::table('patients')->insertGetId([
                'user_id' => $userId,
                'gender' => ($index % 2 == 0) ? 'male' : 'female',
                'date_of_birth' => now()->subYears(rand(20, 50))->format('Y-m-d'),
                'notional_id' => '29'.rand(1000000000, 9999999999),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('doctor_patient')->insert([
                'doctor_id' => 1,
                'patient_id' => $patientId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
