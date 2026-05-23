<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKeyPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $patient = $this->route('patient');
        $keyPoint = $this->route('keyPoint');

        return $patient->aiAnalysisResults()->whereHas('keyPoints', function ($query) use ($keyPoint) {
            $query->where('id', $keyPoint->id);
        })->exists();
    }

    public function rules(): array
    {
        return [
            'insight' => 'required|string',
        ];
    }
}
