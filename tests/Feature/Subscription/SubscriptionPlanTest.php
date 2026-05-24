<?php

use App\Models\Plan;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Notification::fake();
    $this->doctorUser = createDoctorWithBilling();
    actingAs($this->doctorUser);
});

it('returns all available plans successfully', function () {
    Plan::insert([
        [
            'name' => 'Basic',
            'price' => 100,
            'summaries_limit' => 10,
            'duration_days' => 30,
            'features' => json_encode(['feature1']),
        ],
        [
            'name' => 'Pro',
            'price' => 200,
            'summaries_limit' => 20,
            'duration_days' => 30,
            'features' => json_encode(['feature2']),
        ],
        [
            'name' => 'Premium',
            'price' => 300,
            'summaries_limit' => 30,
            'duration_days' => 30,
            'features' => json_encode(['feature3']),
        ],
    ]);

    $response = $this->getJson(route('subscription.plans.index'));

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Available plans retrieved successfully')
        ->assertJsonCount(3, 'data');
});

it('returns empty array when no plans exist', function () {
    $response = $this->getJson(route('subscription.plans.index'));

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Available plans retrieved successfully')
        ->assertJsonCount(0, 'data');
});

it('returns 401 for guest user', function () {
    auth()->logout();

    $response = $this->getJson(route('subscription.plans.index'));

    $response->assertStatus(401);
});
