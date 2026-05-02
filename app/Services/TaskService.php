<?php

namespace App\Services;

use App\Actions\Visit\StoreTaskAction;
use App\Models\Task;
use App\Models\Visit;

class TaskService
{
    public function store(Visit $visit, array $data): Task|bool
    {
        return (new StoreTaskAction)->execute($visit, $data);
    }

    public function delete(Task $task) : void
    {
        $task->delete();
    }
}
