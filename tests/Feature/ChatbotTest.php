<?php

use App\Jobs\Ai\IngestPatientJob;
use App\Models\PatientIngestion;
use App\Models\Plan;
use App\Models\Report;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->doctor = createUserWithType('doctor', fake()->unique()->safeEmail());
    $this->doctor->doctor->billing_mode = 'subscription';
    $this->doctor->doctor->save();
    actingAs($this->doctor);
    $this->patient = createUserWithType('patient', fake()->unique()->safeEmail());
    $this->doctor->doctor->patients()->attach($this->patient->patient->id);
    $this->reports = Report::create([
        'patient_id' => $this->patient->patient->id,
        'type' => 'lab',
        'file_name' => 'test.pdf',
        'file_path' => 'test.pdf',
        'mime_type' => 'application/pdf',
    ]);
    $plan = Plan::create([
        'name' => 'Premium',
        'price' => 5500.00,
        'summaries_limit' => 550,
        'duration_days' => 30,
        'features' => json_encode([
            'Key Important Information',
            'Comparative Analysis',
            'Decision Support',
            'DiagnoBot',
        ])
    ]);
    $this->subscription = Subscription::create([
        'doctor_id' => $this->doctor->doctor->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'used_summaries' => 0,
        'started_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);
    Queue::fake();
    Http::fake([
        config('services.ai.url') . '/query' => Http::response([
            'answer' => 'This is a test answer from the AI.',
        ])
    ]);
});

it('dispatches ingestion job when patient data is not yet ingested', function () {
    $response = $this->post(route('patients.chatbot.ask', $this->patient->patient->id), [
        'question' => 'What is the diagnosis for this patient?',
    ]);
    $response->assertStatus(202);
    Queue::assertPushed(IngestPatientJob::class);
});
it('returns chatbot answer when patient data is already ingested', function () {
     $hash = hash('sha256', $this->patient->patient->reports->pluck('file_path')->sort()->implode(','));
     PatientIngestion::create([
        'patient_id' => $this->patient->patient->id,
        'status' => 'completed',
        'file_hash' => $hash,
    ]);
     $response = $this->post(route('patients.chatbot.ask', $this->patient->patient->id), [
        'question' => 'What is the diagnosis for this patient?',
     ]);
     $response->assertStatus(200);
     $response->assertJsonStructure([
        'message',
        'data'
     ]);
});

it('denies chatbot access when doctor plan does not include DiagnoBot feature', function () {
   $plan = Plan::create([
        'name' => 'Basic',
        'price' => 1200.00,
        'summaries_limit' => 200,
        'duration_days' => 30,
        'features' => json_encode([
            'Key Important Information',
            'Comparative Analysis',
        ])
    ]);
    $this->subscription->plan_id = $plan->id;
    $this->subscription->save();
    $response = $this->post(route('patients.chatbot.ask', $this->patient->patient->id), [
        'question' => 'What is the diagnosis for this patient?',
     ]);
     $response->assertStatus(403);
});
