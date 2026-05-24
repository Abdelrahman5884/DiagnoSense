<?php

namespace App\Http\Requests;

use App\Rules\CheckOldPasswordRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteDoctorAccountRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', new CheckOldPasswordRule, 'bail', 'confirmed'],
        ];
    }
}
