<?php

namespace App\Http\Requests\Visit;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GetNextVisitDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patient = $this->route('patient');
        Gate::authorize('view', [Visit::class, $patient]);

        return true;
    }
}
