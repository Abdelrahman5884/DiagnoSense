<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetPatientMedicalFilesRequest extends FormRequest
{
    public function all($keys = null): array
    {
        return array_merge(parent::all(), $this->query());
    }

    public function rules(): array
    {
        return [
            'type' => [
                'nullable',
                Rule::in(['lab', 'medical_history', 'radiology']),
            ],
            'search' => ['nullable', 'string'],
        ];
    }
}
