<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPatientDataForUpdateRequest extends FormRequest
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
        $patient = $this->route('patient');

        return $currentDoctor->patients()->where('patients.id', $patient->id)->exists();
    }
}
