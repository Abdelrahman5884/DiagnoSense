<?php

namespace App\Http\Requests\Doctor;

use App\Rules\UserData\CheckOldPasswordRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangeDoctorPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', new CheckOldPasswordRule],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
