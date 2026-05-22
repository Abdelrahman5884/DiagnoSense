<?php

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;

beforeEach(function () {

    $this->user = createUserWithType('doctor', 'abdelrahman@gmail.com');
    $this->doctor = $this->user->doctor;

    actingAs($this->user);
});

describe('Patient Destroy', function () {

    it('successfully soft deletes an owned patient', function () {

        $patient = createUserWithType('patient', 'body1@gmail.com')->patient;

        $this->doctor->patients()->attach($patient->id);

        deleteJson(route('patients.destroy', [
            'patient' => $patient->id,
        ]))
            ->assertOk();

        assertSoftDeleted('patients', [
            'id' => $patient->id,
        ]);
    });

    it('returns 403 when deleting a patient belonging to another doctor', function () {

        $otherDoctor = createUserWithType('doctor', 'body@gmail.com')->doctor;

        $patient = createUserWithType('patient', 'boda@gmail.com')->patient;

        $otherDoctor->patients()->attach($patient->id);

        deleteJson(route('patients.destroy', [
            'patient' => $patient->id,
        ]))
            ->assertStatus(403);
    });

    it('returns 401 if guest tries to delete a patient', function () {

        auth()->logout();

        deleteJson(route('patients.destroy', [
            'patient' => 1,
        ]))
            ->assertStatus(401);
    });

    it('ensures the User record is NOT deleted when Patient is soft deleted', function () {

        $patientUser = createUserWithType('patient', 'abdo@gmail.com');

        $this->doctor->patients()->attach($patientUser->patient->id);

        deleteJson(route('patients.destroy', [
            'patient' => $patientUser->patient->id,
        ]));

        assertDatabaseHas('users', [
            'id' => $patientUser->id,
        ]);
    });

    it('returns 404 for non-existent patient', function () {

        deleteJson(route('patients.destroy', [
            'patient' => 9999,
        ]))
            ->assertStatus(404);
    });
});
