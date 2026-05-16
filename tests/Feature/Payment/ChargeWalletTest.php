<?php

use function Pest\Laravel\actingAs;

beforeEach(function(){
    $this->doctor = createUserWithType('doctor', fake()->unique()->safeEmail());
    actingAs($this->doctor);
    Http::fake([
        config('services.paymob.base_url') . 'v1/intention/' => Http::response([
            'client_secret' => 'test-client-secret'
        ], 200)
    ]);
});

it('allow a doctor to charge his wallet successfully' , function (){
    $response = $this->postJson(route('wallets.charge'), [
        'balance' => 10000
    ]);
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'checkout_url'
        ]
    ]);
});
