<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreNextVisitRequest;
use App\Http\Resources\NextVisitResource;

class VisitController extends Controller
{
    public function store(StoreNextVisitRequest $request)
    {
        $status = ($request->action == 'save') ? 'completed' : 'draft';
        $nextVisitDate = $request->next_visit_date ?? null;
        $visit = auth()->user()->doctor->visits()->create([
            'patient_id' => $request->patient_id,
            'next_visit_date' => $nextVisitDate,
            'status' => $status,
        ]);
        if ($nextVisitDate) {
            $visit->patient->refreshVisitDates($nextVisitDate);
        }

        return ApiResponse::success(message: 'Visit created successfully', data: new NextVisitResource($visit), statusCode: 200);
    }
}
