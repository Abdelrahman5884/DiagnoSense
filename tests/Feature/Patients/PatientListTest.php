<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Dr. Ahmed', 'type' => 'doctor']);
    $this->doctor = Doctor::factory()->create(['user_id' => $this->user->id]);
    actingAs($this->user);
});

describe('Patients Index: Validation', function () {
    it('validates that status must be a valid enum value', function () {
        getJson(route('patients.index', ['status' => 'invalid_status']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    it('allows empty search and status to return all patients', function () {
        getJson(route('patients.index'))
            ->assertOk();
    });
});

describe('Patients Index: Functional Logic (Search & Filter)', function () {
    beforeEach(function () {
        $saraUser = User::factory()->create(['name' => 'Dr. Sara', 'type' => 'doctor']);
        $saraDoctor = Doctor::factory()->create(['user_id' => $saraUser->id]);

        $assem = Patient::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Assem']),
            'notional_id' => '2990101001',
            'status' => 'critical',
        ]);
        $asma = Patient::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Asma']),
            'notional_id' => '2990102002',
            'status' => 'stable',
        ]);
        $ahmed = Patient::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Ahmed']),
            'notional_id' => '2990203003',
            'status' => 'stable',
        ]);

        $this->doctor->patients()->attach([$assem->id, $asma->id, $ahmed->id]);

        $amina = Patient::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Amina']),
            'notional_id' => '3000101001',
            'status' => 'critical',
        ]);
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
