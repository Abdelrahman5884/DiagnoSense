<?php

use App\Models\ActivityLog;
use App\Models\Patient;

beforeEach(function () {

    $this->doctorUser = createDoctorWithBilling();

    $this->doctor = $this->doctorUser->doctor;

    $this->patient = Patient::factory()->create();

    $this->patient->doctors()->attach($this->doctor->id);

    $this->actingAs($this->doctorUser);
});

describe('Patient Activities', function () {

    it('returns patient activity history successfully', function () {

        ActivityLog::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'changeable_type' => 'KeyPoint',
            'changeable_id' => 1,
            'action' => 'keypoint_created',
            'description' => 'Created activity',
        ]);

        ActivityLog::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'changeable_type' => 'KeyPoint',
            'changeable_id' => 2,
            'action' => 'keypoint_updated',
            'description' => 'Updated activity',
        ]);

        ActivityLog::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'changeable_type' => 'KeyPoint',
            'changeable_id' => 3,
            'action' => 'keypoint_deleted',
            'description' => 'Deleted activity',
        ]);

        $response = $this->getJson(
            route('patients.activities', [
                'patient' => $this->patient->id,
            ])
        );

        $response->assertStatus(200)
            ->assertJsonPath(
                'message',
                'Activity history retrieved successfully'
            )
            ->assertJsonCount(3, 'data');
    });

    it('returns activities ordered by latest first', function () {

        ActivityLog::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'changeable_type' => 'KeyPoint',
            'changeable_id' => 1,
            'action' => 'keypoint_created',
            'description' => 'Created activity',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        ActivityLog::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'changeable_type' => 'KeyPoint',
            'changeable_id' => 2,
            'action' => 'keypoint_updated',
            'description' => 'Updated activity',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        ActivityLog::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'changeable_type' => 'KeyPoint',
            'changeable_id' => 3,
            'action' => 'keypoint_deleted',
            'description' => 'Deleted activity',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson(
            route('patients.activities', [
                'patient' => $this->patient->id,
            ])
        );

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('returns 403 when doctor tries to access unauthorized patient activities', function () {

        $anotherDoctorUser = createDoctorWithBilling();

        $anotherPatient = Patient::factory()->create();

        $anotherPatient->doctors()->attach(
            $anotherDoctorUser->doctor->id
        );

        $response = $this->getJson(
            route('patients.activities', [
                'patient' => $anotherPatient->id,
            ])
        );

        $response->assertStatus(403);
    });

    it('returns 401 for guest user', function () {

        auth()->logout();

        $response = $this->getJson(
            route('patients.activities', [
                'patient' => $this->patient->id,
            ])
        );

        $response->assertStatus(401);
    });

    it('returns empty array when patient has no activities', function () {

        ActivityLog::truncate();

        $response = $this->getJson(
            route('patients.activities', [
                'patient' => $this->patient->id,
            ])
        );

        $response->assertStatus(200)
            ->assertJsonPath(
                'message',
                'Activity history retrieved successfully'
            )
            ->assertJsonCount(0, 'data.data');
    });
});
