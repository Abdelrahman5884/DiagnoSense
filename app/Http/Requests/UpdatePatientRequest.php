<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $currentDoctor = auth()->user()->doctor;
        if (! $currentDoctor) {
            return false;
        }

        return $currentDoctor->patients()->whereKey($this->patientId)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $patientId = $this->route('patientId');
        $user = auth()->user();
        $patient = $user ? $user->doctor->patients()->find($patientId) : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required_without:phone', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($patient?->user_id)->where(fn ($q) => $q->where('type', 'patient')),
            ],
            'phone' => [
                'required_without:email', 'string', 'max:15',
                Rule::unique('users')->ignore($patient?->user_id)->where(fn ($q) => $q->where('type', 'patient')),
            ],
            'age' => ['required', 'integer'],
            'gender' => ['required', 'string', 'in:male,female'],
            'national_id' => ['nullable', 'string', Rule::unique('patients')->ignore($patient?->id)],
            'is_smoker' => ['nullable', 'boolean'],
            'previous_surgeries' => ['nullable', 'boolean'],
            'chronic_diseases' => ['nullable', 'array'],
            'chronic_diseases.*' => ['string'],
            'previous_surgeries_name' => ['required_if:previous_surgeries,true', 'prohibited_if:previous_surgeries,false', 'string'],
            'medications' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'current_complaint' => ['nullable', 'string'],
            'lab' => ['nullable', 'array'],
            'lab.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
            'radiology' => ['nullable', 'array'],
            'radiology.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
            'medical_history' => ['nullable', 'array'],
            'medical_history.*' => ['file', 'mimes:pdf,jpg', 'max:10240'],
        ];
    }
}
