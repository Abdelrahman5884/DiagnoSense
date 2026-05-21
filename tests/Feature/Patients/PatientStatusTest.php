<?php

use App\Models\Patient;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

beforeEach(function () {

    $this->user = createUserWithType('doctor', 'doctor@gmail.com');

    $this->doctor = $this->user->doctor;

    actingAs($this->user);
});

describe('Update Patient Status', function () {

    it('updates patient status successfully', function () {

        $patient = createUserWithType(
            'patient',
            'patient@gmail.com'
        )->patient;

        $this->doctor
            ->patients()
            ->attach($patient->id);

        patchJson(
            route('patients.update-status', [
                'patient' => $patient->id,
            ]),
            [
                'status' => 'stable',
            ]
        )
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Patient status updated successfully',
                'data' => [
                    'status' => 'stable',
                ],
            ]);
    });

    it('returns 403 if patient does not belong to doctor', function () {

        $patient = createUserWithType(
            'patient',
            'patient2@gmail.com'
        )->patient;

        patchJson(
            route('patients.update-status', [
                'patient' => $patient->id,
            ]),
            [
                'status' => 'stable',
            ]
        )
            ->assertStatus(403);
    });

    it('returns 404 if patient does not exist', function () {

        patchJson(
            route('patients.update-status', [
                'patient' => 9999,
            ]),
            [
                'status' => 'stable',
            ]
        )
            ->assertStatus(404);
    });

    it('returns 401 for guest user', function () {

        auth()->logout();

        $patient = Patient::factory()->create();

        patchJson(
            route('patients.update-status', [
                'patient' => $patient->id,
            ]),
            [
                'status' => 'stable',
            ]
        )
            ->assertStatus(401);
    });

    it('validates required status field', function () {

        $patient = createUserWithType(
            'patient',
            'patient3@gmail.com'
        )->patient;

        $this->doctor
            ->patients()
            ->attach($patient->id);

        patchJson(
            route('patients.update-status', [
                'patient' => $patient->id,
            ]),
            []
        )
            ->assertStatus(422);
    });

    it('updates patient status in database', function () {

        $patient = createUserWithType(
            'patient',
            'patient4@gmail.com'
        )->patient;

        $this->doctor
            ->patients()
            ->attach($patient->id);

        patchJson(
            route('patients.update-status', [
                'patient' => $patient->id,
            ]),
            [
                'status' => 'critical',
            ]
        );

        expect(
            $patient->fresh()->status
        )->toBe('critical');
    });
});