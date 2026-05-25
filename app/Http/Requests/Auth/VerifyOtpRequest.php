<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\UserData\ValidContactRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');
        $user = User::where('contact', $this->input('contact'))->first();
        return $user && $user->type === $type;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact' => ['required', new ValidContactRule],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }
}
