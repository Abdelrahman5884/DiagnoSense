<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SearchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userDoc1 = User::create([
            'name' => 'Dr. Ahmed Mansour',
            'contact' => 'doctor1@diagno.com',
            'password' => Hash::make('password'),
            'type' => 'doctor',
            'is_active' => true,
        ]);
        $doctor1 = Doctor::create(['user_id' => $userDoc1->id, 'specialization' => 'Cardiology']);
        auth()->login($userDoc1);
        $token = $userDoc1->createToken('TestToken')->plainTextToken;
        $this->command->info('---------------------------------------------');
        $this->command->info("Token for Dr. Ahmed: {$token}");
        $this->command->info('---------------------------------------------');
        $userDoc2 = User::create([
            'name' => 'Dr. Sarah Ali',
            'contact' => 'doctor2@diagno.com',
            'password' => Hash::make('password'),
            'type' => 'doctor',
            'is_active' => true,
        ]);
        $doctor2 = Doctor::create(['user_id' => $userDoc2->id, 'specialization' => 'Neurology']);
        auth()->login($userDoc2);
        $p1 = $this->createPatient('Assem Mohamed', '29901011234567', $doctor1);
        $p2 = $this->createPatient('Asma Khalil', '29505051234568', $doctor1);
        $p3 = $this->createPatient('Ahmed Zaki', '28010101234569', $doctor1);
        $p4 = $this->createPatient('Amina Hassan', '29001019999999', $doctor2);
        Visit::create([
            'patient_id' => $p1->id,
            'doctor_id' => $doctor1->id,
            'next_visit_date' => now()->addDays(7),
            'status' => 'completed',
            'created_at' => now()->subMonth(),
        ]);
        Visit::create([
            'patient_id' => $p2->id,
            'doctor_id' => $doctor1->id,
            'next_visit_date' => null,
            'status' => 'attended',
            'created_at' => now()->subDays(2),
        ]);
    }

    private function createPatient($name, $nationalId, $doctor)
    {
        $user = User::create([
            'name' => $name,
            'contact' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'type' => 'patient',
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'user_id' => $user->id,
            'date_of_birth' => '1995-05-20',
            'gender' => 'male',
            'notional_id' => $nationalId,
            'status' => 'stable',
        ]);

        $doctor->patients()->attach($patient->id);

        return $patient;
    }
}
