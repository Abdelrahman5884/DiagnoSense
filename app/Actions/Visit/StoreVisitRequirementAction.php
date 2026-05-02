<?php

namespace App\Actions\Visit;

use App\Models\Visit;

abstract class StoreVisitRequirementAction
{
    protected function updateVisitIfNeeded(Visit $visit, array $data): void
    {
        if (! $visit->next_visit_date && isset($data['next_visit_date'])) {
            $visit->update(['next_visit_date' => $data['next_visit_date']]);
        }
        if ($data['action'] == 'save') {
            $visit->update(['status' => 'completed']);
        }
    }
}
