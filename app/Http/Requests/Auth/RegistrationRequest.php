<?php

namespace App\Http\Requests\Auth;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
                Rule::unique('users', 'contact'),
                Rule::when(filter_var($this->input('contact'), FILTER_VALIDATE_EMAIL), ['email'], ['regex:/^01[0125][0-9]{8}$/']),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'specialization' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required' => 'The contact field is required.',
            'contact.unique' => 'The contact has already been taken.',
            'contact.email' => 'The contact must be a valid email address.',
            'contact.regex' => 'The contact must be a valid phone number starting with 010, 011, 012, or 015 followed by 8 digits.',
        ];
    }
}
