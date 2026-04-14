<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\LabReportResource;
use App\Http\Resources\MedicalFileResource;
use App\Http\Resources\MedicationListResource;
use App\Http\Resources\RadiologyReportResource;
use App\Http\Resources\TimelineResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class MedicalFileController extends Controller
{
    /**
     * Medical History Files
     */
    public function medicalHistoryFiles(Request $request)
    {
        $user = $request->user();

        if (! $user->patient) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $patient = $user->patient;

        $search = $request->query('search');

        $files = $patient->reports()
            ->where('type', 'medical_history')
            ->when($search, function ($query) use ($search) {
                $query->where('file_name', 'like', "%{$search}%");
            })
            ->latest()
            ->get();

        return MedicalFileResource::collection($files);
    }

    /**
     * Lab Reports
     */
    public function labReports(Request $request)
    {
        $user = $request->user();

        if (! $user->patient) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $patient = $user->patient;

        $search = $request->query('search');

        $reports = $patient->reports()
            ->where('type', 'lab')
            ->when($search, function ($query) use ($search) {
                $query->where('file_name', 'like', "%{$search}%");
            })
            ->with(['patient.visits.doctor.user'])
            ->latest()
            ->get();

        return LabReportResource::collection($reports);
    }

    /**
     * Radiology Reports
     */
    public function radiologyReports(Request $request)
    {
        $user = $request->user();

        if (! $user->patient) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $patient = $user->patient;

        $search = $request->query('search');

        $reports = $patient->reports()
            ->where('type', 'radiology')
            ->when($search, function ($query) use ($search) {
                $query->where('file_name', 'like', "%{$search}%");
            })
            ->latest()
            ->get();

        return RadiologyReportResource::collection($reports);
    }

    /**
     * Medications
     */
    public function medications(Request $request)
    {

        $user = $request->user();
        if (! $user->patient) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $patient = $user->patient;
        $medications = $patient->medications()
            ->latest()
            ->get();

        return MedicationListResource::collection($medications);
    }

    /**
     * timeline
     */
    public function timeline(Request $request)
    {
        $user = $request->user();
        if (! $user->patient) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $patient = $user->patient;
        $visits = $patient->visits()
            ->with('doctor.user')
            ->get()
            ->map(function ($visit) {
                return [
                    'type' => 'visit',
                    'title' => 'Visit',
                    'description' => $visit->next_visit_date->format('Y-m-d- h:i A'),
                    'doctor' => $visit->doctor?->user?->name,
                    'date' => $visit->created_at,
                ];
            });

        $tasks = $patient->tasks()
            ->with('doctor.user')
            ->get()
            ->map(function ($task) {
                return [
                    'type' => 'task',
                    'title' => $task->title,
                    'description' => $task->description,
                    'doctor' => $task->doctor?->user?->name,
                    'date' => $task->created_at,
                ];
            });

        $timeline = $visits
            ->concat($tasks)
            ->sortByDesc(function ($item) {
                return $item['date'];
            })
            ->values();

        return TimelineResource::collection($timeline);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'phone' => 'sometimes|string|unique:users,phone,'.$user->id.'|max:20',
        ]);
        $user->update($validated);

        return ApiResponse::success(
            message: 'Profile updated successfully',
            data: [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            statusCode: 200
        );
    }
}
