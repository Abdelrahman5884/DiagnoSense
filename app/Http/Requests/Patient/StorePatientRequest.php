<?php

namespace App\Http\Requests\Patient;

use App\Rules\ValidContactRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact' => [
                'required',
                new ValidContactRule,
                'bail',
                Rule::unique('users', 'contact'),
            ],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female'],
            'national_id' => ['nullable', 'string', 'max:14', 'unique:patients,national_id'],
            'is_smoker' => ['nullable', 'boolean'],
            'chronic_diseases' => ['nullable', 'array'],
            'chronic_diseases.*' => ['string'],
            'previous_surgeries_name' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'lab' => ['required_without_all:radiology,medical_history', 'array'],
            'lab.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
            'radiology' => ['required_without_all:lab,medical_history', 'array'],
            'radiology.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
            'medical_history' => ['required_without_all:lab,radiology', 'array'],
            'medical_history.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
            'current_complaints' => ['nullable', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'contact.required' => 'The contact field is required.',
            'contact.unique' => 'The contact has already been taken.',
            'lab.required_without_all' => 'Please upload at least one lab test result or radiology report or medical history report.',
            'radiology.required_without_all' => 'Please upload at least one lab test result or radiology report or medical history report.',
            'medical_history.required_without_all' => 'Please upload at least one lab test result or radiology report or medical history report.',
            'lab.*.mimes' => 'Each lab test must be a PDF document Or jpg image.',
            'radiology.*.mimes' => 'Each radiology report must be a PDF document Or jpg image',
            'medical_history.*.mimes' => 'Each medical history file must be a PDF document Or jpg image.',
        ];
    }
}
