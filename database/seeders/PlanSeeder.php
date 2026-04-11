<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'price' => 1200.00,
                'summaries_limit' => 200,
                'duration_days' => 30,
                'features' => json_encode([
                    'Key Important Information',
                    'Comparative Analysis',
                ]),
            ],
            [
                'name' => 'Pro',
                'price' => 3000.00,
                'summaries_limit' => 350,
                'duration_days' => 30,
                'features' => json_encode([
                    'Key Important Information',
                    'Comparative Analysis',
                    'Decision Support',
                ]),
            ],
            [
                'name' => 'Premium',
                'price' => 5500.00,
                'summaries_limit' => 550,
                'duration_days' => 30,
                'features' => json_encode([
                    'Key Important Information',
                    'Comparative Analysis',
                    'Decision Support',
                    'DiagnoBot',
                ]),
            ],
        ];
        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
