<?php

use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->validData = [
        'name' => 'Test User',
        'password' => 'password',
        'password_confirmation' => 'password',
        'specialization' => 'Cardiologist',
    ];
});

it('allow user to register', function (string $contact) {
    Event::fake();
    $response = $this->postJson(
        route('register'),
        array_merge($this->validData, ['contact' => $contact])
    );
    Event::assertDispatched(function (UserRegistered $event) use ($contact) {
        return $event->user->contact === $contact;
    });
    $response->assertStatus(201);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'user' => [
                'id',
                'name',
                'contact',
                'created_at',
                'updated_at',
            ],
            'token',
        ],
    ]);
    $this->assertDatabaseHas('users', [
        'contact' => $response->json('data.user.contact'),
    ]);
    $this->assertDatabaseHas('doctors', [
        'specialization' => $this->validData['specialization'],
    ]);
})->with([
    'email' => [fake()->unique()->safeEmail()],
    'phone' => [fake()->randomElement(['010', '011', '012', '015']).fake()->numerify('########')],
]);

describe('registration validation', function () {
    it('fails registration if contact is already taken', function () {
        Event::fake();
        $user = User::factory()->create();
        $response = $this->postJson(
            route('register'),
            array_merge($this->validData, ['contact' => $user->contact])
        );
        Event::assertNotDispatched(UserRegistered::class);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Validation Errors',
            'data' => [
                'contact' => ['The contact has already been taken.'],
            ],
        ]);
    });

    it('fails registration with invalid data', function (array $invalidField, array $expectedErrors) {
        Event::fake();
        $response = $this->postJson(
            route('register'),
            array_merge($this->validData, ['contact' => fake()->unique()->safeEmail()], $invalidField)
        );
        Event::assertNotDispatched(UserRegistered::class);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Validation Errors',
            'data' => $expectedErrors,
        ]);
    })->with([
        'name missing' => [['name' => null], ['name' => ['The name field is required.']]],
        'contact missing' => [['contact' => null], ['contact' => ['The contact field is required.']]],
        'contact is not valid' => [['contact' => 'not-an-email-or-phone'], ['contact' => ['The contact must be a valid email address or a valid phone number starting with 010, 011, 012, or 015 followed by 8 digits.']]],
        'password not match' => [['password_confirmation' => 'wrongpassword'], ['password' => ['The password field confirmation does not match.']]],
    ]);
});
