<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{

    public function authorize(): bool
    {
        $authenticatedDoctorId = auth()->user()->doctor->id;
        $requestedDoctor = $this->route('doctor');
        $requestedDoctorId = $requestedDoctor instanceof Doctor ? $requestedDoctor->id : $requestedDoctor;

        return (int)$authenticatedDoctorId === (int)$requestedDoctorId;
    }


    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
        ];
    }
}
