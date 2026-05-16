<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DecisionSupport>
 */
class DecisionSupportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'condition' => $this->faker->randomElement([
                'Type 2 Diabetes Mellitus',
                'Iron Deficiency Anemia',
                'Chronic Hypertension',
                'Hyperthyroidism',
                'Acute Bronchitis',
            ]),
            'probability' => $this->faker->randomFloat(2, 0.5, 0.99),
            'status' => $this->faker->randomElement(['HIGH LIKELIHOOD', 'LOW LIKELIHOOD', 'POSSIBLE']),
            'clinical_reasoning' => $this->faker->paragraph(2),
        ];
    }
}
