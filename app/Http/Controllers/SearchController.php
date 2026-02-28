<?php

namespace App\Http\Controllers;

use App\Http\Resources\SearchResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $doctorId = auth()->user()->doctor->id;
        $patients = User::search($request->get('search'))
            ->query(function ($query) use ($doctorId) {
                $query->select('users.id', 'users.name')
                    ->join('patients', 'patients.user_id', '=', 'users.id')
                    ->join('doctor_patient', 'doctor_patient.patient_id', '=', 'patients.id')
                    ->where('doctor_patient.doctor_id', $doctorId)
                    ->with([
                        'patient:id,user_id,age,status,created_at',
                        'patient.latestAiAnalysisResult:id,patient_id,ai_insight',
                    ]);
            })->get();

        if ($patients->count() > 0) {
            return SearchResource::collection($patients);
        }

        return ApiResponse::error(message: 'No results found', errors: null, statusCode: 404);
    }
}
