<?php

namespace App\Http\Requests;

use App\Rules\CheckOldPasswordRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteDoctorAccountRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', new CheckOldPasswordRule, 'bail', 'confirmed'],
        ];
    }
}
