<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Models\KeyPoint;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Task;
use App\Models\Visit;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $modelName = class_basename($model);
            if ($modelName === 'KeyPoint' && $model->is_manual) {
                $model->logActivity('created');
            } elseif (in_array($modelName, ['Task', 'Medication', 'Visit'])) {
                $model->logActivity('created');
            }
        });

        static::updated(function ($model) {
            $modelName = class_basename($model);
            if (in_array($modelName, ['Patient', 'KeyPoint', 'Task', 'Medication', 'Visit'])) {
                $model->logActivity('updated');
            }
        });

        static::deleted(function ($model) {
            $modelName = class_basename($model);
            if (in_array($modelName, ['KeyPoint', 'Task', 'Medication', 'Visit'])) {
                $model->logActivity('deleted');
            }
        });
    }

    public function logActivity(string $event)
    {
        $doctor = auth()->user()?->doctor;
        $patientId = null;

        if ($this instanceof Patient) {
            $patientId = $this->id;
        } elseif (isset($this->patient_id)) {
            $patientId = $this->patient_id;
        } elseif (method_exists($this, 'patient')) {
            $patientId = $this->patient?->id;
        } elseif (method_exists($this, 'aiAnalysisResult')) {
            $analysis = $this->aiAnalysisResult()->first();

            if ($analysis && isset($analysis->patient_id)) {
                $patientId = $analysis->patient_id;
            }
        }

        $changes = [];
        $original = [];

        if ($event === 'updated') {
            $changes = $this->getChanges();
            $original = $this->getOriginal();

            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }
        }

        $formattedChanges = [];

        foreach ($changes as $field => $newValue) {
            $formattedChanges[$field] = [
                'old' => $original[$field] ?? null,
                'new' => $newValue,
            ];
        }

        ActivityLog::create([
            'doctor_id' => $doctor?->id,
            'patient_id' => $patientId,
            'model_type' => class_basename($this),
            'model_id' => $this->id,
            'action' => strtolower(class_basename($this)).'_'.$event,
            'description' => $this->generateDescription($event, $formattedChanges),
            'changes' => $formattedChanges ?: null,
        ]);
    }

    protected function generateDescription($event, $changes)
    {
        $doctorName = request()->user()?->doctor?->user?->name ?? 'System';
        $modelName = class_basename($this);

        $displayName = match (true) {
            $this instanceof Visit => 'Visit on '.\Carbon\Carbon::parse($this->next_visit_date)->format('M d, Y'),
            $this instanceof Patient => $this->user?->name,
            $this instanceof KeyPoint => ($this->is_manual ? 'Doctor Note' : 'Key Point') ,
            $this instanceof Task => "Task: '{$this->title}'",
            $this instanceof Medication => "Medication: '{$this->name}'",
            default => "{$modelName} (ID: {$this->id})"
        };

        if ($event === 'created') {
            return "Dr. {$doctorName} created {$displayName}";
        }

        if ($event === 'deleted') {
            return "Dr. {$doctorName} deleted {$displayName}";
        }

        if ($event === 'updated') {
            if (isset($changes['last_visit_date'])) {
                unset($changes['last_visit_date']);
            }
            $messages = [];

            foreach ($changes as $field => $values) {
                if (($this instanceof Patient && $field === 'status') || $this instanceof KeyPoint) {
                    $messages[] = "{$field} changed from '{$values['old']}' to '{$values['new']}'";
                } else {
                    if ($field === 'next_visit_date') {
                        $values['new'] = \Carbon\Carbon::parse($values['new'])->format('D, F j, Y');
                        $field = 'next visit date';
                    }
                    $messages[] = "updated {$field} to '{$values['new']}'";
                }
            }

            return "Dr. {$doctorName}: ".implode(', ', $messages);
        }

        return "{$modelName} {$event}";
    }
}
