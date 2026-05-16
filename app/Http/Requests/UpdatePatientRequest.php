<?php

namespace App\Http\Requests;

use App\Rules\ValidContactRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentDoctor = auth()->user()->doctor;
        if (! $currentDoctor) {
            return false;
        }
        $patient = $this->route('patient');

        return $currentDoctor->patients()->where('patients.id', $patient->id)->exists();
    }

    public function rules(): array
    {
        $patient = $this->route('patient');

        return [
            'name' => ['required', 'string', 'max:255'],

            'contact' => [
                'required',
                new ValidContactRule,
                'bail',
                Rule::unique('users', 'contact')->ignore($patient?->user_id),
            ],

            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female'],

            'national_id' => ['nullable', 'string', 'max:14', Rule::unique('patients')->ignore($patient?->id)],

            'is_smoker' => ['nullable', 'boolean'],
            'chronic_diseases' => ['nullable', 'array'],
            'chronic_diseases.*' => ['string'],
            'previous_surgeries_name' => ['nullable', 'string'],

            'current_medications' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'current_complaints' => ['nullable', 'string'],

            'lab' => ['nullable', 'array'],
            'lab.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
            'radiology' => ['nullable', 'array'],
            'radiology.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
            'medical_history' => ['nullable', 'array'],
            'medical_history.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
        ];

    }
}
