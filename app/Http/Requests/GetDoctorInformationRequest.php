<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class GetDoctorInformationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $loginDoctor = auth()->user()->doctor->id;
        $currentDoctor = Doctor::query()->findOrFail($this->route('doctorId'))->id;

        return $loginDoctor === $currentDoctor;
    }
}
