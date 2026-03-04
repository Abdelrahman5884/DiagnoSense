<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity(string $event)
    {
        $doctor = request()->user()?->doctor;
        $patientId = null;

        if ($this instanceof \App\Models\Patient) {
            $patientId = $this->id;
        } elseif (array_key_exists('patient_id', $this->getAttributes())) {
            $patientId = $this->getAttribute('patient_id');
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
            $this instanceof \App\Models\Patient => $this->user?->name,

            $this instanceof \App\Models\KeyPoint => "Key Point: '{$this->insight}'",

            default => "{$modelName} (ID: {$this->id})"
        };

        if ($event === 'created') {
            return "Dr. {$doctorName} created {$displayName}";
        }

        if ($event === 'deleted') {
            return "Dr. {$doctorName} deleted {$displayName}";
        }

        if ($event === 'updated') {

            $messages = [];

            foreach ($changes as $field => $values) {
                $messages[] = "{$field} changed from '{$values['old']}' to '{$values['new']}'";
            }

            return "Dr. {$doctorName} updated {$displayName}: ".implode(', ', $messages);
        }

        return "{$modelName} {$event}";
    }
}
