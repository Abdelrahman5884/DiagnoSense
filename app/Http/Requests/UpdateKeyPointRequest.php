<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKeyPointRequest extends FormRequest
{
   
    public function rules(): array
    {
        return [
            'insight' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'insight.required' => 'The insight field is required.',
            'insight.string' => 'The insight must be a string.',
        ];
    }
}
