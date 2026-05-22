<?php

namespace App\Actions\Visit;

use App\Models\Task;
use App\Models\Visit;

class StoreTaskAction extends StoreVisitRequirementAction
{
    public function execute(Visit $visit, array $data): Task|bool
    {
        if (! $visit->next_visit_date && ! isset($data['next_visit_date'])) {
            return false;
        }
        $this->updateVisitIfNeeded($visit, $data);
        $task = $visit->tasks()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'visit_id' => $visit->id,
        ]);
        $task['action'] = $data['action'];
        $task->load('visit');

        return $task;
    }
}
