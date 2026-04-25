<?php

namespace Database\Factories;

use App\Models\AiAnalysisResult;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiAnalysisResult>
 */
class AiAnalysisResultFactory extends Factory
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
            'status' => 'processing',
        ];
    }
}
