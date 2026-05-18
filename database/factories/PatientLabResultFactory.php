<?php

namespace Database\Factories;

use App\Models\AiAnalysisResult;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientLabResult>
 */
class PatientLabResultFactory extends Factory
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
            'ai_analysis_result_id' => AiAnalysisResult::factory(),
            'category' => $this->faker->randomElement(['Hematology', 'Biochemistry', 'Lipid Profile']),
            'standard_name' => $this->faker->randomElement([
                'Hemoglobin', 'Blood Glucose', 'Cholesterol', 'Creatinine', 'White Blood Cells',
            ]),
            'numeric_value' => (string) $this->faker->randomFloat(1, 5, 200),
            'unit' => $this->faker->randomElement(['g/dL', 'mg/dL', 'mmol/L', 'cells/mcL']),
            'status' => $this->faker->randomElement(['Normal', 'High', 'Low']),
            'created_at' => now(),
        ];
    }
}
