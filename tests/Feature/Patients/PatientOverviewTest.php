<?php

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {

    $this->user = createUserWithType('doctor', 'abdelrahman@gmail.com');
    $this->doctor = $this->user->doctor;

    actingAs($this->user);
});

describe('Patient Overview', function () {

    it('returns 200 and full structure for authorized doctor', function () {

        $patientUser = createUserWithType('patient', 'doaa@gmail.com');
        $patient = $patientUser->patient;

        $this->doctor->patients()->attach($patient->id);

        getJson(route('patients.overview', ['patient' => $patient->id]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'patientName' => $patientUser->name,
                    'status' => 'stable',
                ],
            ]);
    });

    it('returns 403 if doctor tries to view a patient not in their list', function () {

        $stranger = createUserWithType('patient', 'abdelrahman2@gmail.com')->patient;

        getJson(route('patients.overview', ['patient' => $stranger->id]))
            ->assertStatus(403);
    });

    it('returns 401 if a guest tries to access overview', function () {

        auth()->logout();

        getJson(route('patients.overview', ['patient' => 1]))
            ->assertStatus(401);
    });

    it('returns the correct patient name in the data field', function () {

        $patientUser = createUserWithType('patient', 'abdo2@gmail.com');

        $patientUser->update([
            'name' => 'Kareem',
        ]);

        $this->doctor->patients()->attach($patientUser->patient->id);

        getJson(route('patients.overview', [
            'patient' => $patientUser->patient->id,
        ]))
            ->assertOk()
            ->assertJsonFragment([
                'patientName' => 'Kareem',
            ]);
    });

    it('verifies that smart summary exists in response', function () {

        $patient = createUserWithType('patient', 'body5@gmail.com')->patient;

        $this->doctor->patients()->attach($patient->id);

        getJson(route('patients.overview', ['patient' => $patient->id]))
            ->assertOk()
            ->assertJsonFragment([
                'smart_summary' => 'No AI analysis generated for this patient yet.',
            ]);
    });
});
