<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidContactRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact' => ['required', new ValidContactRule],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }
}
