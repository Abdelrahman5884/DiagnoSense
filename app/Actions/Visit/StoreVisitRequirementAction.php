<?php

namespace App\Actions\Visit;

use App\Models\Visit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class StoreVisitRequirementAction
{
    protected function updateVisitIfNeeded(Visit $visit, array $data): void
    {
        if (! $visit->next_visit_date && isset($data['next_visit_date'])) {
               $validator =  Validator::make($data, [
                    'next_visit_date' => ['date', 'after:now'],
                ], [
                    'next_visit_date.after' => 'Next visit date must be a future date.',
                    ]);
                if($validator->fails()) {
                    throw new ValidationException($validator);
                }
            $visit->update(['next_visit_date' => $data['next_visit_date']]);
        }
        if ($data['action'] == 'save') {
            $visit->update(['status' => 'completed']);
        }
    }
}
