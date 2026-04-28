<?php

use App\Models\AiAnalysisResult;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = createUserWithType('doctor', 'doctor@gmail.com');
    $this->doctor = $this->user->doctor;
    actingAs($this->user);
});

describe('Dashboard Summary', function () {

    it('returns 200 and full structure for authorized doctor', function () {
        getJson(route('dashboard.summary'))->assertOk();
    });

    it('returns correct doctor name in response', function () {
        $this->user->update(['name' => 'Dr. abdelrahman']);
        getJson(route('dashboard.summary'))->assertJsonFragment(['doctor_name' => 'Dr. abdelrahman']);
    });

    it('returns correct total patients count', function () {
        collect(range(1, 3))->each(fn ($i) => tap(createUserWithType('patient', "patient{$i}@gmail.com")->patient,
            fn ($p) => $this->doctor->patients()->attach($p->id)
        )
        );

        getJson(route('dashboard.summary'))->assertJsonFragment(['total_patients' => '3']);
    });

    it('returns correct today appointments count', function () {
        $patient = createUserWithType('patient', 'patient12@gmail.com')->patient;
        $this->doctor->patients()->attach($patient->id);

        $type = DB::select("SHOW COLUMNS FROM visits LIKE 'status'")[0]->Type;
        preg_match('/enum\(\'(.*)\'\)$/', $type, $matches);
        $firstStatus = explode("','", $matches[1])[0];

        Visit::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'next_visit_date' => now()->toDateString(),
            'status' => $firstStatus,
        ]);

        getJson(route('dashboard.summary'))
            ->assertOk()
            ->assertJsonFragment(['today_appointments' => 1]);
    });

    it('does not count visits from other days in today appointments', function () {
        $patient = createUserWithType('patient', 'patient53@gmail.com')->patient;
        $this->doctor->patients()->attach($patient->id);

        $type = DB::select("SHOW COLUMNS FROM visits LIKE 'status'")[0]->Type;
        preg_match('/enum\(\'(.*)\'\)$/', $type, $matches);
        $firstStatus = explode("','", $matches[1])[0];

        Visit::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'next_visit_date' => now()->subDay()->toDateString(),
            'status' => $firstStatus,
        ]);

        getJson(route('dashboard.summary'))->assertJsonFragment(['today_appointments' => 0]);
    });

    it('returns correct reports analyzed count', function () {
        $patient = createUserWithType('patient', 'patient66t@gmail.com')->patient;
        $this->doctor->patients()->attach($patient->id);

        AiAnalysisResult::create(['patient_id' => $patient->id, 'status' => 'completed']);
        AiAnalysisResult::create(['patient_id' => $patient->id, 'status' => 'completed']);

        getJson(route('dashboard.summary'))->assertJsonFragment(['reports_analyzed' => '2']);
    });

    it('returns trend up when growth is positive', function () {
        $patient = createUserWithType('patient', 'patient23@gmail.com')->patient;
        $this->doctor->patients()->attach($patient->id);

        getJson(route('dashboard.summary'))->assertJsonFragment(['trend' => 'up']);
    });

    it('returns trend down when growth is negative', function () {
        $oldPatient = createUserWithType('patient', 'patient02@gmail.com')->patient;
        $this->doctor->patients()->attach($oldPatient->id);

        DB::table('patients')->where('id', $oldPatient->id)->update([
            'created_at' => now()->subMonth()->startOfMonth(),
        ]);

        getJson(route('dashboard.summary'))->assertJsonFragment(['trend' => 'down']);
    });

    it('returns 401 if guest tries to access dashboard', function () {
        auth()->logout();
        getJson(route('dashboard.summary'))->assertStatus(401);
    });

});
