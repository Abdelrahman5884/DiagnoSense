<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidContactRule;
use Illuminate\Foundation\Http\FormRequest;

class ForgetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'contact' => ['required', 'string', new ValidContactRule],
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required' => 'Please enter your email or phone number.',
        ];
    }
}
