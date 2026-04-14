<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\StoreVisitItemRequest;
use App\Http\Resources\MedicationResource;
use App\Http\Resources\TaskResource;
use App\Http\Helpers\ApiResponse;
use App\Models\Patient;
use App\Models\Task;
use App\Models\Visit;

class VisitItemController extends Controller
{
    public function index($patient)
    {
        $patient = Patient::query()->findOrFail($patient);
        $currentDoctor = auth()->user()->doctor;
        if (! $currentDoctor->patients->contains($patient->id)) {
            return ApiResponse::error('Unauthorized', null, 403);
        }
        $tasks = $patient->tasks()->with('visit')->get();
        $medications = $patient->medications()->with('visit')->get();
        $data = [
            'tasks' => TaskResource::collection($tasks),
            'medications' => MedicationResource::collection($medications),
            'next_visit_date' => $patient->next_visit_date ?
            \Carbon\Carbon::parse($patient->next_visit_date)->timezone('Africa/Cairo')->format('D, F j, Y - g:i A') : null,
        ];

        return ApiResponse::success(
            message: 'Visit items retrieved successfully',
            data: $data,
            statusCode: 200
        );
    }

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
        if ($request->next_visit_date) {
            $visit->update(['next_visit_date' => $request->next_visit_date]);
            $visit->patient->refreshVisitDates($request->next_visit_date);
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

    public function destroyMedication($patient, $medication)
    {
        $patient = Patient::query()->findOrFail($patient);
        $medication = $patient->medications()->findOrFail($medication);
        if (! $patient->medications->contains($medication->id)) {
            return ApiResponse::error('Unauthorized', null, 403);
        }
        $medication->delete();

        return ApiResponse::success(
            message: 'Medication deleted successfully',
            data: null,
            statusCode: 200
        );
    }

    public function destroyTask($patient, $task)
    {
        $patient = Patient::query()->findOrFail($patient);
        $task = Task::query()->findOrFail($task);
        if (! $patient->tasks->contains($task->id)) {
            return ApiResponse::error('Unauthorized', null, 403);
        }
        $task->delete();

        return ApiResponse::success(
            message: 'Task deleted successfully',
            data: null,
            statusCode: 200
        );
    }
}
