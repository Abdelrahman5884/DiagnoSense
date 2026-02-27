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
        $search = $request->get('search');
        $doctorId = auth()->user()->doctor->id;
        if (empty($search)) {
            return ApiResponse::error(message: 'The search parameter is required for searching', errors: null, statusCode: 400);
        }
        $query = User::query()
            ->select('users.id', 'users.name')
            ->join('patients', 'patients.user_id', '=', 'users.id')
            ->join('doctor_patient', 'doctor_patient.patient_id', '=', 'patients.id')
            ->where('doctor_patient.doctor_id', $doctorId)
            ->with([
                'patient:id,user_id,age,status,created_at,national_id',
                'patient.latestAiAnalysisResult:id,patient_id,ai_insight',
            ]);

        if (is_numeric($search)) {
            $query->where('patients.national_id', $search);
        } else {
            $ids = User::search($search)->keys();
            $query->whereIn('users.id', $ids);
        }
        $patients = $query->paginate(9);

        if ($patients->count() > 0) {
            return SearchResource::collection($patients);
        }

        return ApiResponse::error(message: 'No results found', errors: null, statusCode: 404);
    }
}
