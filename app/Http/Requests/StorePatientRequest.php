<?php

namespace App\Http\Requests;

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
            'email' => [
                'required_without:phone',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('type', 'patient');
                }),
            ],
            'phone' => [
                'required_without:email',
                'string',
                'min:10',
                'max:15',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('type', 'patient');
                }),
            ],
            'age' => ['nullable', 'integer'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'national_id' => ['nullable', 'string', 'max:15', 'unique:patients,national_id'],
            'is_smoker' => ['nullable', 'boolean'],
            'previous_surgeries' => ['nullable', 'boolean'],
            'chronic_diseases' => ['nullable', 'array'],
            'chronic_diseases.*' => ['string'],
            'previous_surgeries_name' => ['required_if:previous_surgeries,true','prohibited_if:previous_surgeries,false', 'string'],
            'medications' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'lab' => ['required_without_all:radiology,medical_history', 'array'],
            'lab.*' => ['file', 'mimes:pdf', 'max:10240'],
            'radiology' => ['required_without_all:lab,medical_history', 'array'],
            'radiology.*' => ['file', 'mimes:pdf', 'max:10240'],
            'medical_history' => ['required_without_all:lab,radiology', 'array'],
            'medical_history.*' => ['file', 'mimes:pdf', 'max:10240'],
            'current_complaint' => ['nullable', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'Email already exists.',
            'phone.unique' => 'Phone number already exists.',
            'national_id.unique' => 'National ID already exists.',
            'lab.required_without_all' => 'Please upload at least one lab test result or radiology report or medical history report.',
            'radiology.required_without_all' => 'Please upload at least one lab test result or radiology report or medical history report.',
            'medical_history.required_without_all' => 'Please upload at least one lab test result or radiology report or medical history report.',
            'previous_surgeries_name.required_if' => 'Please provide the names of previous surgeries.',
            'previous_surgeries_name.prohibited_if' => 'Previous surgeries name should not be provided.',
        ];
    }
}
