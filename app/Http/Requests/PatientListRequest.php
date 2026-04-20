<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|min:1',
            'status' => ['nullable', Rule::in(['critical', 'stable', 'under review'])],
        ];
    }
}
