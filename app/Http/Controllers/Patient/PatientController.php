<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Resources\PatientListResource;
use App\Http\Responses\ApiResponse;
use App\Jobs\ProcessAi;
use App\Models\AiAnalysisResult;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(){
        $patients = Patient::with(['user','aiAnalysisResults' => function($query){
            $query->latest()->first();
        }])->paginate(9);
        return PatientListResource::collection($patients);
    }
    public function store(StorePatientRequest $request)
    {
        DB::beginTransaction();
        try {
        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email ?? null,
            'phone' => $request->phone ?? null,
            'type' => 'patient',
            'password' => Str::random(10),
        ]);

        $patient = Patient::query()->create([
            'user_id' => $user->id,
            'age' => $request->age ?? null,
            'gender' => $request->gender ?? null,
            'national_id' => $request->national_id ?? null,
        ]);

        $patient->doctors()->attach($request->user()->doctor->id);

        $medicalHistory = MedicalHistory::query()->create([
            'patient_id' => $patient->id,
            'is_smoker' => $request->is_smoker ?? null,
            'previous_surgeries' => $request->previous_surgeries ?? null,
            'chronic_diseases' => $request->chronic_diseases ?? null,
            'previous_surgeries_name' => $request->previous_surgeries_name ?? null,
            'medications' => $request->medications ?? null,
            'allergies' => $request->allergies ?? null,
            'family_history' => $request->family_history ?? null,
        ]);

        $reportsTypes = ['lab', 'radiology', 'medical_history'];
        $pathsForAI = [
            'lab' => [],
            'radiology' => [],
            'medical_history' => []
        ];

        foreach ($reportsTypes as $type) {
            if ($request->hasFile($type)) {
                foreach ($request->file($type) as $file) {
                    $fileName = $file->getClientOriginalName();
                    $uniqueName = time() . '_' . Str::random(5) . '.' . $file->getClientOriginalExtension();
                    $filePath = Storage::disk('azure')->putFileAs($type, $file, $uniqueName);
                    if (!$filePath) {
                        throw new \Exception("Failed to upload $fileName file to azure blob storage.");
                    }
                    $mimeType = $file->getMimeType();
                    Report::query()->create([
                        'patient_id' => $patient->id,
                        'type' => $type,
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'mime_type' => $mimeType,
                    ]);
                    $pathsForAI[$type][] = $filePath;
                }
            }
        }

        $analysisResult = AiAnalysisResult::create([
            'patient_id' => $patient->id,
            'status' => 'processing',
        ]);

        $jobData = [
            'age' => $patient->age,
            'gender' => $patient->gender,
            'history' => $medicalHistory->toArray(),
            'file_paths' => $pathsForAI
        ];


        DB::commit();

        ProcessAi::dispatch($analysisResult->id, $jobData);

        return ApiResponse::success('Patient created successfully and AI analysis is in progress.', [
            'patient_id' => $patient->id,
            'analysis_result_id' => $analysisResult->id,
        ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to create patient: ' . $e->getMessage(), null, 500);
        }
    }
    public function getKeyInfo($patientId){
        $patient = Patient::find($patientId);
        if(!$patient){
            return ApiResponse::error('Patient not found', null, 404);
        }
        $analysis  = $patient->aiAnalysisResults()->latest()->first();
        if(!$analysis){
            return ApiResponse::error('No AI analysis found for this patient.', null, 404);
        }
        if($analysis->status == 'failed'){
            return ApiResponse::error('AI analysis failed.', null, 400);
        }
        if($analysis->status == 'processing'){
            return ApiResponse::error('AI analysis is not completed yet.', null, 400);
        }
        return ApiResponse::success('AI analysis retrieved successfully.', 
            $analysis->response,
            200
        );
    }
}
