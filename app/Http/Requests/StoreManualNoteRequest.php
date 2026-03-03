<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $doctor = $this->user()->doctor;
        $patientId = $this->route('patientId');
        return $doctor->patients()
            ->where('patients.id', $patientId)
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'insight' => 'required|string',
            'priority' => 'required|string|in:high,medium,low',
        ];
    }
    public function messages(): array
    {
        return [
            'insight.required' => 'The insight field is required.',
            'insight.string' => 'The insight must be a string.',
            'priority.required' => 'The priority field is required.',
            'priority.string' => 'The priority must be a string.',
            'priority.in' => 'The priority must be one of the following: high, medium, low.',
        ];
    }
}
