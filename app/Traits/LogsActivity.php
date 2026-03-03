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

        $changes = $this->getChanges();
        $original = $this->getOriginal();

        unset($changes['updated_at']);

        $filteredChanges = [];

        foreach ($changes as $field => $newValue) {
            $filteredChanges[$field] = [
                'old' => $original[$field] ?? null,
                'new' => $newValue
            ];
        }

        $activityData = $this->generateActivityData($event, $filteredChanges);

        ActivityLog::create([
            'doctor_id' => $doctor?->id,
            'model_type' => class_basename($this),
            'model_id' => $this->id,
            'action' => $activityData['type'],
            'description' => $activityData['message'],
            'changes' => $filteredChanges ?: null,
        ]);
    }

    protected function generateActivityData($event, $changes)
{
    $doctorName = request()->user()?->doctor?->user?->name ?? 'System';
    $modelName = class_basename($this);

  
    $displayName = $this->user?->name ?? "{$modelName} (ID: {$this->id})";

    if ($event === 'created') {
        return [
            'type' => strtolower($modelName).'_created',
            'message' => "Dr. {$doctorName} created new {$displayName}"
        ];
    }

    if ($event === 'deleted') {
        return [
            'type' => strtolower($modelName).'_deleted',
            'message' => "Dr. {$doctorName} deleted {$displayName}"
        ];
    }

    if ($event === 'updated' && !empty($changes)) {

        $messages = [];

        foreach ($changes as $field => $values) {

            if ($field === 'status') {
                return [
                    'type' => 'status_updated',
                    'message' => "Dr. {$doctorName} changed {$displayName} status from '{$values['old']}' to '{$values['new']}'"
                ];
            }

            $messages[] = "{$field} from '{$values['old']}' to '{$values['new']}'";
        }

        return [
            'type' => strtolower($modelName).'_updated',
            'message' => "Dr. {$doctorName} updated {$displayName}: ".implode(', ', $messages)
        ];
    }

    return [
        'type' => strtolower($modelName).'_'.$event,
        'message' => "{$modelName} {$event}"
    ];
}
}