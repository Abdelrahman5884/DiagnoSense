<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorInformationRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
        ];
    }
}
