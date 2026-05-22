<?php

namespace App\Services;

use App\Actions\Visit\StoreTaskAction;
use App\Models\Patient;
use App\Models\Task;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    public function getTasks(Patient $patient): Collection
    {
        return Task::whereHas('visit', function ($q) use ($patient) {
            $q->where('patient_id', $patient->id);
        })->with('visit')->latest()->get();
    }

    public function store(Visit $visit, array $data): Task|bool
    {
        return (new StoreTaskAction)->execute($visit, $data);
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
