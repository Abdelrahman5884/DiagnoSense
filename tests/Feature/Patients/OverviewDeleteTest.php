<?php


use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\assertDatabaseHas;

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

        $response = getJson(route('patients.overview', ['patientId' => $patient->id]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'patientName' => $patientUser->name,
                    'status' => 'stable'
                ]
            ]);
    });

    it('returns 403 if doctor tries to view a patient not in their list', function () {
        $stranger = createUserWithType('patient', 'abselrahman2@gmail.com')->patient;
        getJson(route('patients.overview', ['patientId' => $stranger->id]))
            ->assertStatus(403);
    });

    it('returns 401 if a guest tries to access overview', function () {
        auth()->logout();
        getJson(route('patients.overview', ['patientId' => 1]))->assertStatus(401);
    });

    it('returns the correct patient name in the data field', function () {
        $patientUser = createUserWithType('patient', 'abdo2@gmail.com');
        $patientUser->update(['name' => 'Kareem']);
        $this->doctor->patients()->attach($patientUser->patient->id);

        getJson(route('patients.overview', ['patientId' => $patientUser->patient->id]))
            ->assertOk()
            ->assertJsonFragment(['patientName' => 'Kareem']);
    });

    it('verifies that smart summary exists in response', function () {
        $patient = createUserWithType('patient', 'body5@gmail.com')->patient;
        $this->doctor->patients()->attach($patient->id);

        getJson(route('patients.overview', ['patientId' => $patient->id]))
            ->assertOk()
            ->assertJsonFragment(['smart_summary' => 'No AI analysis generated for this patient yet.']);
    });
});

describe('Patient Destroy', function () {

    it('successfully soft deletes an owned patient', function () {
        $patient = createUserWithType('patient', 'body1@gmail.com')->patient;
        $this->doctor->patients()->attach($patient->id);

        deleteJson(route('patients.destroy', ['patientId' => $patient->id]))->assertOk();
        assertSoftDeleted('patients', ['id' => $patient->id]);
    });

    it('prevents deleting a patient belonging to another doctor', function () {
        $otherDoctor = createUserWithType('doctor', 'body@gmail.com')->doctor;
        $patient = createUserWithType('patient', 'boda@gmail.com')->patient;
        $otherDoctor->patients()->attach($patient->id);

        deleteJson(route('patients.destroy', ['patientId' => $patient->id]))
            ->assertStatus(404);
    });

    it('returns 401 if guest tries to delete a patient', function () {
        auth()->logout();
        deleteJson(route('patients.destroy', ['patientId' => 1]))->assertStatus(401);
    });

    it('ensures the User record is NOT deleted when Patient is soft deleted', function () {
        $patientUser = createUserWithType('patient', 'abdo@gmail.com');
        $this->doctor->patients()->attach($patientUser->patient->id);

        deleteJson(route('patients.destroy', ['patientId' => $patientUser->patient->id]));
        assertDatabaseHas('users', ['id' => $patientUser->id]);
    });

    it('handles non-existent patient ID with 404/403', function () {
        deleteJson(route('patients.destroy', ['patientId' => 9999]))
            ->assertStatus(404);
    });
});