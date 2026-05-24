<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'name' => fake()->randomElement([
                'Panadol Extra',
                'Concor 5mg',
                'Augmentin 1g',
                'Brufen 400mg',
                'Catafast 50mg',
                'Amaryl 2mg',
            ]),
            'dosage' => fake()->randomElement([
                '1 tablet',
                '2 tablets',
                '5ml syrup',
                '1 capsule',
            ]),
            'frequency' => fake()->randomElement([
                'Once daily',
                'Twice per day',
                'Three times daily',
                'Every 8 hours',
            ]),
            'duration' => fake()->randomElement([
                '5 days',
                '1 week',
                '10 days',
                '1 month',
            ]),
        ];
    }
}
