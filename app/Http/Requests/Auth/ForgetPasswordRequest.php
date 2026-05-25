<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\UserData\ValidContactRule;
use Illuminate\Foundation\Http\FormRequest;

class ForgetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');
        $user = User::where('contact', $this->input('contact'))->first();
        return $user && $user->type === $type;
    }

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
