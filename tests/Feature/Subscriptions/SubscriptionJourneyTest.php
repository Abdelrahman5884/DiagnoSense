<?php

use App\Models\Plan;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->plan = Plan::factory()->create([
        'name' => 'Premium',
        'price' => 500.00,
        'duration_days' => 30,
        'summaries_limit' => 50,
        'features' => json_encode(['Comparative Analysis', 'Key Information']),
    ]);

    $this->user = createDoctorWithBilling(billingMode: 'pay-per-use', balance: 80000);
    $this->doctor = $this->user->doctor;
    $this->wallet = $this->doctor->wallet;

    actingAs($this->user);
});

it('allows a doctor to subscribe to a plan successfully', function () {
    $response = $this->postJson(route('subscriptions.subscribe', ['plan' => $this->plan->id]));
    $response->assertStatus(201);
});

it('returns the correct current subscription metrics and features', function () {
    $this->doctor->update(['billing_mode' => 'subscription']);
    $this->wallet->update(['balance' => 300.00]);
    $this->doctor->subscriptions()->create([
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'started_at' => now(),
        'expires_at' => now()->addDays(30),
        'used_summaries' => 0,
    ]);

    $response = getJson(route('subscriptions.current'));
    $response->assertStatus(200)->assertJsonPath('data.billing_mode', 'subscription');
});

it('cancels the active subscription and returns helpful UX message', function () {
    $this->doctor->update(['billing_mode' => 'subscription']);
    $subscription = $this->doctor->subscriptions()->create([
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'started_at' => now(),
        'expires_at' => now()->addDays(30),
        'used_summaries' => 0,
    ]);

    $response = postJson(route('subscriptions.cancel'));
    $response->assertStatus(200);
    $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'cancelled']);
});
