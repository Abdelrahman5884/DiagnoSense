<?php

namespace App\Http\Requests\Patient;


use Illuminate\Foundation\Http\FormRequest;

class GetPatientOverviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient->doctors()
            ->where('doctor_id', auth()->user()->doctor->id)
            ->exists();
    }
}
