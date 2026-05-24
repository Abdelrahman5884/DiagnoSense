<?php

namespace App\Http\Requests\Patient;

use App\Rules\ValidContactRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientProfileRequest extends FormRequest
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
