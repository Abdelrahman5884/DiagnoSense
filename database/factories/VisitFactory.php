<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'next_visit_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d H:i:s'),
            'status' => fake()->randomElement(['completed', 'draft', 'attended']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
