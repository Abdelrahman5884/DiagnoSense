<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Carbon;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            if ($model->shouldLogEvent('created')) {
                $model->logActivity('created');
            }
        });

        static::updated(function ($model) {
            if ($model->shouldLogEvent('updated')) {
                $model->logActivity('updated');
            }
        });

        static::deleted(function ($model) {
            if ($model->shouldLogEvent('deleted')) {
                $model->logActivity('deleted');
            }
        });
    }

    protected function shouldLogEvent(string $event): bool
    {
        if (! request()->user() || ! request()->user()->doctor) {
            return false;
        }

        if (property_exists($this, 'logOnlyEvents') && ! in_array($event, $this->logOnlyEvents)) {
            return false;
        }

        if (class_basename($this) === 'KeyPoint' && $event === 'created' && ($this->is_ai_generated ?? false)) {
            return false;
        }

        return true;
    }

    public function logActivity(string $event): void
    {
        $doctor = request()->user()?->doctor;
        $patientId = $this->determinePatientId();

        $changes = [];
        $original = [];

        if ($event === 'updated') {
            $changes = $this->getChanges();
            $original = $this->getOriginal();

            unset($changes['updated_at'], $changes['last_visit_date']);

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
            'changeable_type' => $this->getMorphClass(),
            'changeable_id' => $this->id,
            'action' => strtolower(class_basename($this)).'_'.$event,
            'description' => $this->generateDescription($event, $formattedChanges),
            'changes' => $formattedChanges ?: null,
        ]);
    }

    protected function determinePatientId(): ?int
    {
        if (method_exists($this, 'getActivityPatientId')) {
            return $this->getActivityPatientId();
        }

        if (property_exists($this, 'patient_id') || isset($this->patient_id)) {
            return $this->patient_id;
        }

        if (method_exists($this, 'patient')) {
            return $this->patient?->id;
        }

        return null;
    }

    protected function generateDescription(string $event, array $changes): string
    {
        $doctorName = request()->user()?->doctor?->user?->name ?? 'System';

        if (method_exists($this, 'toActivityDisplayName')) {
            $displayName = $this->toActivityDisplayName();
        } else {
            $displayName = class_basename($this)." (ID: {$this->id})";
        }

        if ($event === 'created' || $event === 'deleted') {
            return "Dr. {$doctorName} {$event} {$displayName}";
        }

        if ($event === 'updated') {
            $messages = [];
            foreach ($changes as $field => $values) {
                if ((class_basename($this) === 'Patient' && $field === 'status') || class_basename($this) === 'KeyPoint') {
                    $messages[] = "{$field} changed from '{$values['old']}' to '{$values['new']}'";
                } else {
                    if ($field === 'next_visit_date') {
                        $values['new'] = Carbon::parse($values['new'])->format('D, F j, Y');
                        $field = 'next visit date';
                    }
                    $messages[] = "updated {$field} to '{$values['new']}'";
                }
            }

            return "Dr. {$doctorName}: ".implode(', ', $messages);
        }

        return class_basename($this)." {$event}";
    }
}
