<?php

namespace App\Http\Requests\Auth;

use App\Rules\UserData\ValidContactRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'contact' => [
                'required',
                new ValidContactRule,
                'bail',
                Rule::unique('users', 'contact'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required' => 'The contact field is required.',
            'contact.unique' => 'The contact has already been taken.',
        ];
    }
}
