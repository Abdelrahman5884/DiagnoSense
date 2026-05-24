<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'title' => fake()->randomElement([
                'Take medication after meals',
                'Check blood pressure daily',
                'Measure fasting blood sugar',
                'Drink 3 liters of water daily',
                'Do a complete blood count (CBC) test',
                'Follow up after two weeks',
            ]),
            'description' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'is_completed' => fake()->boolean(20),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
