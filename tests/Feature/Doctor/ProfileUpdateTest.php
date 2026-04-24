<?php

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

beforeEach(function () {
    $this->user = createUserWithType('doctor', 'menna@diagno.com');
    $this->user->update(['name' => 'Dr. Menna']);
    $this->doctor = $this->user->doctor;

    actingAs($this->user, 'sanctum');
});

describe('Profile Update: Success Scenarios', function () {
    it('allows updating only name without specialization', function () {
        $response = patchJson(route('doctor.profile.update'), [
            'name' => 'Only Name Update',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['name' => 'Only Name Update']);
    });
    it('allows updating only specialization without name', function () {
        $oldName = $this->user->name;
        $newSpec = 'Neurology Specialist';
        $response = patchJson(route('doctor.profile.update'), [
            'specialization' => $newSpec,
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('doctors', [
            'id' => $this->doctor->id,
            'specialization' => $newSpec,
        ]);
        expect($this->user->refresh()->name)->toBe($oldName);
    });
    it('allows updating both name and specialization simultaneously', function () {
        $newData = [
            'name' => 'Dr. Menna Baligh (PhD)',
            'specialization' => 'Cardiology Expert',
        ];
        $response = patchJson(route('doctor.profile.update'), $newData);
        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => $newData['name'],
        ]);
        $this->assertDatabaseHas('doctors', [
            'id' => $this->doctor->id,
            'specialization' => $newData['specialization'],
        ]);
    });
});
