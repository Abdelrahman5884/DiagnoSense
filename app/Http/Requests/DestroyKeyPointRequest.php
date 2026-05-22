<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DestroyKeyPointRequest extends FormRequest
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
            //
        ];
    }
}
