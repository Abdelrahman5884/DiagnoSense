<?php

namespace App\Http\Requests;

use App\Rules\ValidContactRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'contact' => [
                'sometimes',
                new ValidContactRule,
                'unique:users,contact,'.$userId,
            ],
        ];
    }
}
