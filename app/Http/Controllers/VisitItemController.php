<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitItemRequest;
use App\Http\Resources\MedicationResource;
use App\Http\Resources\TaskResource;
use App\Http\Responses\ApiResponse;
use App\Models\Visit;

class VisitItemController extends Controller
{
    public function store(StoreVisitItemRequest $request, $visit)
    {
        $visit = Visit::query()->findOrFail($visit);
        if (! $visit->next_visit_date) {
            if ($request->type == 'task' && ! $request->next_visit_date) {
                return response()->json(['message' => 'Next visit date is required for tasks.'], 422);
            }
            $date = $request->next_visit_date;
            $visit->update(['next_visit_date' => $date]);
        }
        if ($request->action == 'save') {
            $visit->update(['status' => 'completed']);
        }
        if ($request->type == 'task') {
            $item = auth()->user()->doctor->tasks()->create([
                'title' => $request->title,
                'description' => $request->description ?? null,
                'notes' => $request->notes ?? null,
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
            ]);
            $item['action'] = $request->action;
            $item->load('visit');
            $item = new TaskResource($item);
        } else {
            $item = auth()->user()->doctor->medications()->create([
                'name' => $request->name,
                'dosage' => $request->dosage,
                'frequency' => $request->frequency,
                'duration' => $request->duration ?? null,
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
            ]);
            $item['action'] = $request->action;
            $item->load('visit');
            $item = new MedicationResource($item);
        }

        return ApiResponse::success(
            message: ucfirst($request->type).' created successfully',
            data: $item,
            statusCode: 200
        );
    }
}
