<?php

namespace App\Http\Requests\Auth;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\ValidContactRule;

class EmailVerificationRequest extends FormRequest
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
            'contact' => ['required',new ValidContactRule,'bail',],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required' => 'The contact field is required.',
            'otp.required' => 'The OTP field is required.',
            'otp.size' => 'The OTP must be exactly 6 digits.',
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
