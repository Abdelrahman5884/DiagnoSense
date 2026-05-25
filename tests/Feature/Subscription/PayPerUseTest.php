<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\Subscription\PayPerUseActivated;
use Illuminate\Support\Facades\Notification;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    Notification::fake();
    $this->doctorUser = createDoctorWithBilling();
    $this->doctor = $this->doctorUser->doctor;
    actingAs($this->doctorUser);
});

it('switches doctor to pay per use mode successfully', function () {
    $plan = Plan::create([
        'name' => 'Basic',
        'price' => 100,
        'summaries_limit' => 10,
        'duration_days' => 30,
        'features' => json_encode(['feature1']),
    ]);

    Subscription::create([
        'doctor_id' => $this->doctor->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now(),
        'expires_at' => now()->addMonth(),
        'used_summaries' => 0,
    ]);

    $response = $this->postJson(route('subscriptions.pay-per-use'));

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Switched to Pay-Per-Use mode. 20EGP will be charged per file.');

    expect($this->doctor->fresh()->billing_mode)->toBe('pay-per-use');

    $this->assertDatabaseHas('subscriptions', [
        'doctor_id' => $this->doctor->id,
        'status' => 'cancelled',
    ]);

    Notification::assertSentTo($this->doctor, PayPerUseActivated::class);
});

it('returns 401 for guest user', function () {
    auth()->logout();

    $response = $this->postJson(route('subscriptions.pay-per-use'));

    $response->assertStatus(401);
});

it('cancels all active subscriptions when switching to pay per use', function () {
    $plan = Plan::create([
        'name' => 'Basic',
        'price' => 100,
        'summaries_limit' => 10,
        'duration_days' => 30,
        'features' => json_encode(['feature1']),
    ]);

    Subscription::create([
        'doctor_id' => $this->doctor->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now(),
        'expires_at' => now()->addMonth(),
        'used_summaries' => 0,
    ]);

    Subscription::create([
        'doctor_id' => $this->doctor->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now(),
        'expires_at' => now()->addMonth(),
        'used_summaries' => 0,
    ]);

    $this->postJson(route('subscriptions.pay-per-use'));

    $this->assertDatabaseMissing('subscriptions', [
        'doctor_id' => $this->doctor->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('subscriptions', [
        'doctor_id' => $this->doctor->id,
        'status' => 'cancelled',
    ]);
});
