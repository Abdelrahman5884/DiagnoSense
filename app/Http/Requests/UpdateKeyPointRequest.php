<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKeyPointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $doctor = $this->user()->doctor;
        $keyPointId = $this->route('keyPointId');

        return $doctor->patients()
            ->whereHas('aiAnalysisResults.keyPoints', function ($query) use ($keyPointId) {
                $query->where('id', $keyPointId);
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

    public function messages(): array
    {
        return [
            'insight.required' => 'The insight field is required.',
            'insight.string' => 'The insight must be a string.',
        ];
    }
}
