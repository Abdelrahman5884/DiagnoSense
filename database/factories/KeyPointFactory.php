<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KeyPoint>
 */
class KeyPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
            'title' => $this->faker->sentence(),
            'insight' => $this->faker->paragraph(),
            'is_ai_generated' => true,
            'evidence' => ['Some evidence text'],
        ];
    }
}
