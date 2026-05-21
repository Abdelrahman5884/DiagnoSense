<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiAnalysisSeeder extends Seeder
{
    public function run(): void
    {
        $patients = DB::table('patients')->pluck('id');

        foreach ($patients as $patientId) {

            $analysisId = DB::table('ai_analysis_results')->insertGetId([
                'patient_id' => $patientId,
                'response' => json_encode([
                    'summary' => 'AI generated medical analysis',
                ]),
                'status' => 'completed',
                'ai_insight' => 'Patient shows stable vitals with mild concerns.',
                'ai_summary' => 'Overall condition is stable.',
                'ocr_file_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('key_points')->insert([
                [
                    'ai_analysis_result_id' => $analysisId,
                    'priority' => 'high',
                    'title' => 'Blood Pressure',
                    'insight' => 'Elevated blood pressure detected.',
                    'is_ai_generated' => true,
                    'evidence' => json_encode([
                        'BP: 145/95',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'ai_analysis_result_id' => $analysisId,
                    'priority' => 'medium',
                    'title' => 'Heart Rate',
                    'insight' => 'Heart rate slightly above normal.',
                    'is_ai_generated' => true,
                    'evidence' => json_encode([
                        'HR: 102 bpm',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],

                [
                    'ai_analysis_result_id' => $analysisId,
                    'priority' => 'low',
                    'title' => 'Hydration',
                    'insight' => 'Patient may need better hydration.',
                    'is_ai_generated' => true,
                    'evidence' => json_encode([
                        'Dryness indicators found.',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}