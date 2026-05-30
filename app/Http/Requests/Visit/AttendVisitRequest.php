<?php

namespace App\Http\Requests\Visit;

use Illuminate\Foundation\Http\FormRequest;

class AttendVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $doctor = auth()->user()->doctor;
        $visit = $this->route('visit');
        return $doctor->visits()->where('visits.id', $visit->id)->exists();
    }
}
