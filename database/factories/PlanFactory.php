<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Basic', 'Premium', 'Pro']),
            'price' => $this->faker->randomElement([299.00, 499.00, 999.00]),
            'duration_days' => $this->faker->randomElement([30, 90, 365]),
            'summaries_limit' => $this->faker->randomElement([50, 150, 500]),
            'features' => json_encode([
                'Key Information',
                'Comparative Analysis',
                '24/7 Dedicated Support',
                'DiagnoBot AI Assistant',
            ]),
        ];
    }
}
