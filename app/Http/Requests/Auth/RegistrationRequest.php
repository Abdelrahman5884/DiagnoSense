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
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->route('type');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required_without:phone',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($type) {
                    return $query->where('type', $type);
                }),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'phone' => [
                'required_without:email',
                'string',
                'min:10',
                'max:15',
                Rule::unique('users')->where(function ($query) use ($type) {
                    return $query->where('type', $type);
                }),
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email is already in use.',
            'phone.required' => 'Phone number is required.',
            'phone.unique' => 'Phone number is already in use.',
            'phone.min' => 'Phone number must be at least 10 digits.',
            'phone.max' => 'Phone number must not exceed 15 digits.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('This action could not be completed due to validation errors.',
                $validator->errors(),
                422)
        );
    }
}
