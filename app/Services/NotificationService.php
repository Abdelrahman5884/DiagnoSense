<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Visit;
use Carbon\Carbon;

class NotificationService
{
    public function getPatientNotifications($patientId)
    {
        $activities = ActivityLog::where('patient_id', $patientId)
            ->whereIn('model_type', ['Task', 'Visit', 'Medication'])
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'title' => $this->getTitle($item->model_type),
                    'description' => $item->description,
                    'time' => $item->created_at->diffForHumans(),
                    'date' => $item->created_at,
                    'type' => 'activity',
                ];
            });

        $visits = Visit::where('patient_id', $patientId)
            ->whereDate('next_visit_date', Carbon::tomorrow())
            ->with('doctor.user')
            ->get()
            ->unique('id')
            ->values();

        $reminders = $visits->map(function ($visit) {
            return [
                'title' => 'Visit Reminder',
                'description' => 'You have a visit tomorrow at '.
                 $visit->next_visit_date->format('h:i A').
                ' with Dr. '.
                ($visit->doctor?->user?->name),
                'time' => $visit->next_visit_date->diffForHumans(),
                'date' => $visit->next_visit_date,
                'type' => 'reminder',
            ];
        })->unique('description')
            ->values();

        return $activities
            ->concat($reminders)
            ->sortByDesc('date')
            ->values();
    }

    private function getTitle($type)
    {
        return match ($type) {
            'Task' => 'Task',
            'Medication' => 'Medication',
            'Visit' => 'New Visit',
            default => 'Update',
        };
    }
}
