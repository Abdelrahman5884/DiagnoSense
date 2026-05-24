<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Resources\LabReportResource;
use App\Http\Resources\MedicalFileResource;
use App\Http\Resources\MedicationListResource;
use App\Http\Resources\RadiologyReportResource;
use App\Http\Resources\TimelineResource;
use App\Services\MedicalFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalFileController extends Controller
{
    public function __construct(
        protected MedicalFileService $medicalFileService
    ) {}

    public function medicalHistoryFiles(Request $request): JsonResponse
    {
        try {
            $files = $this->medicalFileService
                ->getMedicalHistoryFiles(
                    patient: auth()->user()->patient,
                    search: $request->query('search')
                );

            return ApiResponse::success(
                message: 'Medical history files retrieved successfully',
                data: MedicalFileResource::collection($files),
                status: 200
            );
        } catch (\Exception $e) {
            \Log::error('Error retrieving medical history files: '.$e->getMessage());

            return ApiResponse::error(message: $e->getMessage(), status: $e->getCode() ?: 500
            );
        }
    }

    public function labReports(Request $request): JsonResponse
    {
        try {
            $reports = $this->medicalFileService
                ->getLabReports(
                    patient: auth()->user()->patient,
                    search: $request->query('search')
                );

            return ApiResponse::success(
                message: 'Lab reports retrieved successfully',
                data: LabReportResource::collection($reports),
                status: 200
            );

        } catch (\Exception $e) {
            \Log::error('Error retrieving lab reports: '.$e->getMessage());

            return ApiResponse::error(message: $e->getMessage(), status: $e->getCode() ?: 500);
        }
    }

    public function radiologyReports(Request $request): JsonResponse
    {
        try {
            $reports = $this->medicalFileService
                ->getRadiologyReports(
                    patient: auth()->user()->patient,
                    search: $request->query('search')
                );

            return ApiResponse::success(
                message: 'Radiology reports retrieved successfully',
                data: RadiologyReportResource::collection($reports),
                status: 200
            );

        } catch (\Exception $e) {
            \Log::error('Error retrieving radiology reports: '.$e->getMessage());

            return ApiResponse::error(message: $e->getMessage(), status: $e->getCode() ?: 500);
        }
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
}
