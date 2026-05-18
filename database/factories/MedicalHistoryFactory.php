<?php

namespace Database\Factories;

use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalHistory>
 */
class MedicalHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'is_smoker' => $this->faker->boolean(),
            'chronic_diseases' => $this->faker->randomElements(['diabetes', 'hypertension', 'asthma'], 2),
            'current_medications' => 'Paracetamol, Aspirin, Tylenol',
            'allergies' => 'Peanuts, Shellfish, Crustaceans',
            'family_history' => $this->faker->sentence(),
            'previous_surgeries_name' => $this->faker->sentence(),
            'current_complaints' => $this->faker->sentence(),
        ];
    }
}
