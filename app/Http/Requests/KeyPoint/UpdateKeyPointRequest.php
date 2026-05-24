<?php

namespace App\Http\Requests\KeyPoint;

use Illuminate\Contracts\Validation\ValidationRule;
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'insight' => 'required|string',
        ];
    }
}
