<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $doctor = $this->user()->doctor;
        $patient = $this->route('patient');
        return $doctor->patients()->where('patients.id', $patient->id)->exists();
    }

    public function rules(): array
    {
        return [
            'insight' => 'required|string',
            'priority' => 'required|string|in:high,medium,low',
        ];
    }

}
