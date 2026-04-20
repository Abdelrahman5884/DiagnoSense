<?php
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Services\SearchService;
use Illuminate\Support\Facades\Log;
use function Pest\Laravel\{getJson, actingAs, assertDatabaseHas};

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Dr. Ahmed', 'type' => 'doctor']);
    $this->doctor = Doctor::factory()->create(['user_id' => $this->user->id]);
    actingAs($this->user);
});
describe('Patients Search: Security', function () {
    it('requires authentication to access search', function () {
        auth()->logout();
        getJson(route('patients.search', ['search' => 'any']))
            ->assertUnauthorized();
    });
});
describe('Patients Search: Validation', function () {
    it('validates that search term is required with a custom message', function () {
        getJson(route('patients.search'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['search'])
            ->assertJsonPath('errors.search.0', 'Please enter a name or national ID to search.');
    });
});
describe('Patients Search: Functional Logic & Isolation', function () {
    beforeEach(function () {
        $saraUser = User::factory()->create(['name' => 'Dr. Sara', 'type' => 'doctor']);
        $saraDoctor = Doctor::factory()->create(['user_id' => $saraUser->id]);

        $assem = Patient::factory()->create([
            'user_id' => User::factory()->patient()->create(['name' => 'Assem']),
            'notional_id' => '2990101001'
        ]);
        $asma = Patient::factory()->create([
            'user_id' => User::factory()->patient()->create(['name' => 'Asma']),
            'notional_id' => '2990102002'
        ]);
        $ahmed = Patient::factory()->create([
            'user_id' => User::factory()->patient()->create(['name' => 'Ahmed']),
            'notional_id' => '2990203003'
        ]);
        $this->doctor->patients()->attach([$assem->id, $asma->id, $ahmed->id]);

        $amina = Patient::factory()->create([
            'user_id' => User::factory()->patient()->create(['name' => 'Amina']),
            'notional_id' => '3000101001'
        ]);
        $saraDoctor->patients()->attach($amina->id);
    });

    it('returns only Ahmed\'s 3 patients when searching for "a" (Excluding Sara\'s patient)', function () {
        getJson(route('patients.search', ['search' => 'a']))
            ->assertOk()
            ->assertJsonCount(3, 'data.data')
            ->assertJsonFragment(['name' => 'Assem'])
            ->assertJsonFragment(['name' => 'Asma'])
            ->assertJsonFragment(['name' => 'Ahmed'])
            ->assertJsonMissing(['name' => 'Amina']);
    });

    it('refines results as the search term becomes more specific ("as" -> 2 patients)', function () {
        getJson(route('patients.search', ['search' => 'as']))
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonFragment(['name' => 'Assem'])
            ->assertJsonFragment(['name' => 'Asma']);
    });

    it('returns exactly one patient for highly specific name search ("asm")', function () {
        getJson(route('patients.search', ['search' => 'asm']))
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Asma');
    });

    it('handles numeric search refinement using national ID', function () {
        getJson(route('patients.search', ['search' => '299']))
            ->assertOk()
            ->assertJsonCount(3, 'data.data');

        getJson(route('patients.search', ['search' => '29901']))
            ->assertOk()
            ->assertJsonCount(2, 'data.data');
    });
});

describe('Patients Search: Pagination', function () {
    it('returns correct pagination metadata and limits per page', function () {
        Patient::factory()->count(15)->create()->each(function ($p) {
            $p->user->update(['name' => 'Z-Patient ' . fake()->uuid()]);
            $this->doctor->patients()->attach($p->id);
        });

        $response = getJson(route('patients.search', ['search' => 'z']));

        $response->assertOk();
        expect($response->json('data.meta.per_page'))->toBe(12)
            ->and($response->json('data.meta.total'))->toBe(15);
    });
});

describe('Patients Search: Error Handling', function () {
    it('logs the exception and returns a 500 error on service failure', function () {
        Log::shouldReceive('error')->once();

        $this->mock(SearchService::class)
            ->shouldReceive('search')
            ->andThrow(new Exception('Unexpected DB Error'));

        getJson(route('patients.search', ['search' => 'test']))
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'An error occurred during search');
    });
});
