<?php

namespace App\Http\Requests\NextVisit;

use App\Models\Visit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNextVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patient = $this->route('patient');
        Gate::authorize('store', [Visit::class, $patient]);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'has_next_visit' => ['required', 'boolean'],
            'next_visit_date' => ['required_if:has_next_visit,true', 'prohibited_if:has_next_visit,false', 'date', 'after:now'],
            'action' => ['required', 'string', 'in:save,next'],
        ];
    }
}
