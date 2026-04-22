<?php

use function Pest\Laravel\{actingAs, putJson};

beforeEach(function () {
    $this->user = createUserWithType('doctor', 'menna@diagno.com');
    $this->user->update(['name' => 'Dr. Menna']);
    $this->doctor = $this->user->doctor;

    actingAs($this->user);
});

describe('Profile Update: Success Scenarios', function () {
    it('allows a doctor to update their own profile successfully', function () {
        $newData = [
            'name' => 'Dr. Menna Baligh',
            'specialization' => 'Software & AI in Medicine'
        ];

        $response = putJson(route('doctor.profile.update', $this->doctor->id), $newData);

        $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => null
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Dr. Menna Baligh'
        ]);

        $this->assertDatabaseHas('doctors', [
            'id' => $this->doctor->id,
            'specialization' => 'Software & AI in Medicine'
        ]);
    });
});

describe('Profile Update: Authorization', function () {
    it('forbids a doctor from updating another doctor\'s profile', function () {
        $otherUser = createUserWithType('doctor', 'hacker@diagno.com');
        $otherDoctor = $otherUser->doctor;
        $response = putJson(route('doctor.profile.update', $otherDoctor->id), [
            'name' => 'Hacked Name'
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['name' => 'Hacked Name']);
    });
});

describe('Profile Update Input Flexibility', function () {
    it('allows updating only name without specialization', function () {
        $response = putJson(route('doctor.profile.update', $this->doctor->id), [
            'name' => 'Only Name Update'
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['name' => 'Only Name Update']);
    });
});
