<?php

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = createUserWithType('doctor', 'ahmed@diagno.com');
    $this->user->update(['name' => 'Dr. Ahmed']);
    $this->doctor = $this->user->doctor;
    actingAs($this->user);
});

describe('Patients Index: Validation', function () {
    it('validates that status must be a valid enum value', function () {
        getJson(route('patients.index', ['status' => 'invalid_status']))
            ->assertStatus(422)
            ->assertJsonFragment([
                'status' => ['The selected status is invalid.'],
            ]);
    });

    it('allows empty search and status to return all patients', function () {
        getJson(route('patients.index'))
            ->assertOk();
    });
});

describe('Patients Index: Functional Logic (Search & Filter)', function () {
    beforeEach(function () {
        $saraUser = createUserWithType('doctor', 'sara@diagno.com');
        $saraUser->update(['name' => 'Dr. sara']);
        $saraDoctor = $saraUser->doctor;

        $assemUser = createUserWithType('patient', 'assem@test.com');
        $assemUser->update(['name' => 'Assem']);
        $assemUser->patient->update([
            'national_id' => '2990101001',
            'status' => 'critical',
        ]);
        $assem = $assemUser->patient;

        $asmaUser = createUserWithType('patient', 'asma@test.com');
        $asmaUser->update(['name' => 'Asma']);
        $asmaUser->patient->update([
            'national_id' => '2990102002',
            'status' => 'stable',
        ]);
        $asma = $asmaUser->patient;

        $ahmedUser = createUserWithType('patient', 'ahmed@test.com');
        $ahmedUser->update(['name' => 'Ahmed']);
        $ahmedUser->patient->update([
            'national_id' => '2990203003',
            'status' => 'stable',
        ]);
        $ahmed = $ahmedUser->patient;

        $this->doctor->patients()->attach([$assem->id, $asma->id, $ahmed->id]);

        $aminaUser = createUserWithType('patient', 'amina@test.com');
        $aminaUser->update(['name' => 'Amina']);
        $aminaUser->patient->update([
            'national_id' => '3000101001',
            'status' => 'critical',
        ]);
        $amina = $aminaUser->patient;
        $saraDoctor->patients()->attach($amina->id);
    });

    it('returns only current doctor\'s patients', function () {
        getJson(route('patients.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data.data')
            ->assertJsonMissing(['name' => 'Amina']);
    });

    it('filters by name (Prefix Search)', function () {
        getJson(route('patients.index', ['search' => 'as']))
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonFragment(['name' => 'Assem'])
            ->assertJsonFragment(['name' => 'Asma']);
    });

    it('filters by status only', function () {
        getJson(route('patients.index', ['status' => 'stable']))
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonFragment(['name' => 'Asma'])
            ->assertJsonFragment(['name' => 'Ahmed']);
    });

    it('combines search and status filter', function () {
        getJson(route('patients.index', ['search' => 'as', 'status' => 'critical']))
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Assem');
    });

    it('handles numeric prefix search for national id', function () {
        getJson(route('patients.index', ['search' => '29901']))
            ->assertOk()
            ->assertJsonCount(2, 'data.data');
    });
});
