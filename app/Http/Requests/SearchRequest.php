<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => 'required|string|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'Please enter a name or national ID to search.',
        ];
    }
}
