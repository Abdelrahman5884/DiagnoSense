<?php

namespace App\Http\Requests;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisitItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        $currentDoctor = auth()->user()->doctor;
        if (! $currentDoctor) {
            return false;
        }
        $visit = Visit::query()->findOrFail($this->route('visit'));
        $patient = $visit->patient;

        return $currentDoctor->patients()->whereKey($patient->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255', 'in:medication,task'],
            'name' => ['required_if:type,medication', 'prohibited_if:type,task', 'string', 'max:255'],
            'dosage' => ['required_if:type,medication', 'prohibited_if:type,task', 'string', 'max:255'],
            'frequency' => ['required_if:type,medication', 'prohibited_if:type,task', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'title' => ['required_if:type,task', 'prohibited_if:type,medication', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'next_visit_date' => ['nullable', 'date'],
            'action' => ['required', 'string', 'max:255', 'in:save,save_and_create_another'],
        ];
    }
}
