<?php

use App\Models\Patient;
use App\Models\User;

beforeEach(function () {

    $this->user = User::factory()->create([
        'contact' => 'old@example.com',
    ]);

    $this->patient = Patient::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user);
});

describe('Update Profile', function () {

    it('updates profile successfully', function () {

        $payload = [
            'contact' => 'new@example.com',
        ];

        $response = $this->patchJson(
            route('profile.update'),
            $payload
        );

        $response->assertStatus(200)
            ->assertJsonPath(
                'message',
                'Profile updated successfully'
            )
            ->assertJsonPath(
                'data.contact',
                'new@example.com'
            );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'contact' => 'new@example.com',
        ]);
    });

    it('updates profile successfully with phone number', function () {

        $payload = [
            'contact' => '01012345678',
        ];

        $response = $this->patchJson(
            route('profile.update'),
            $payload
        );

        $response->assertStatus(200)
            ->assertJsonPath(
                'data.contact',
                '01012345678'
            );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'contact' => '01012345678',
        ]);
    });

    it('returns validation error when contact already exists', function () {

        User::factory()->create([
            'contact' => 'existing@example.com',
        ]);

        $payload = [
            'contact' => 'existing@example.com',
        ];

        $response = $this->patchJson(
            route('profile.update'),
            $payload
        );

        $response->assertStatus(422);
    });

    it('returns validation error for invalid contact format', function () {

        $payload = [
            'contact' => '%%%%',
        ];

        $response = $this->patchJson(
            route('profile.update'),
            $payload
        );

        $response->assertStatus(422);
    });

    it('returns 401 for guest user', function () {

        auth()->logout();

        $response = $this->patchJson(
            route('profile.update'),
            [
                'contact' => 'new@example.com',
            ]
        );

        $response->assertStatus(401);
    });
});
