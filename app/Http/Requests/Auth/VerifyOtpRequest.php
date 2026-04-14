<?php

namespace App\Http\Requests\Auth;

use App\Http\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyOtpRequest extends FormRequest
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
        return [
            'identity' => 'required|string',
            'otp' => 'required|max:6',
        ];
    }

    public function messages(): array
    {
        return [
            'identity.required' => 'Please enter your email or phone number.',
            'otp.required' => 'Please enter your OTP.',
            'otp.max' => 'OTP must not exceed 6 characters.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('This action could not be completed due to validation errors.',
                $validator->errors(),
                422));
    }
}
