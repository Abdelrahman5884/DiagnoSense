<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\KeyPoint;
use Illuminate\Http\Request;

class KeyPointController extends Controller
{
    public function destroy(Request $request,$keyPointId)
    {
        $keyPoint = KeyPoint::find($keyPointId);
        if(!$keyPoint) {
            return ApiResponse::error('Key point not found',null, 404);
        }
        $doctor = $request->user()->doctor;
        $patientId = $keyPoint->aiAnalysisResult->patient_id;
        $isAuthorized = $doctor->patients()->where('patients.id', $patientId)->exists();
        if (!$isAuthorized) {
            return ApiResponse::error('Unauthorized: This patient is not under your care.', null, 403);
        }
        $keyPoint->delete();
        return ApiResponse::success('Key point deleted successfully', null, 200);
    }
}
