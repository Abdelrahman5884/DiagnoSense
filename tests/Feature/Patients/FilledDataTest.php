<?php

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {

    $this->user = createUserWithType(
        'doctor',
        'doctor@gmail.com'
    );

    $this->doctor = $this->user->doctor;

    actingAs($this->user);
});

describe('Patient Edit', function () {

    it('returns 200 and patient edit data for authorized doctor', function () {

        $patientUser = createUserWithType(
            'patient',
            'patient@gmail.com'
        );

        $patient = $patientUser->patient;

        $this->doctor->patients()
            ->attach($patient->id);

        getJson(
            route('patients.edit', [
                'patientId' => $patient->id,
            ])
        )
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Data retrieved successfully',
            ]);
    });

    it('returns patient name in response', function () {

        $patientUser = createUserWithType(
            'patient',
            'body@gmail.com'
        );

        $patientUser->update([
            'name' => 'Ahmed Khaled',
        ]);

        $patient = $patientUser->patient;

        $this->doctor->patients()
            ->attach($patient->id);

        getJson(
            route('patients.edit', [
                'patientId' => $patient->id,
            ])
        )
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Ahmed Khaled',
            ]);
    });

    it('returns 401 for guest user', function () {

        auth()->logout();

        getJson(
            route('patients.edit', [
                'patientId' => 1,
            ])
        )
            ->assertStatus(401);
    });

    it('returns reports in response', function () {

        $patient = createUserWithType(
            'patient',
            'report@gmail.com'
        )->patient;

        $this->doctor->patients()
            ->attach($patient->id);

        getJson(
            route('patients.edit', [
                'patientId' => $patient->id,
            ])
        )
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'existing_files',
                ],
            ]);
    });
});
