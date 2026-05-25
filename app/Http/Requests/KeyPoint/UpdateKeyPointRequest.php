<?php

namespace App\Http\Requests\KeyPoint;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKeyPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $doctor = $this->user()->doctor;
        $keyPoint = $this->route('key_point');
        if(!$doctor || ! $keyPoint)
        {
            return false;
        }

        return $doctor->patients()->whereHas('aiAnalysisResults.keyPoints', function ($query) use ($keyPoint) {
            $query->whereKey($keyPoint->id);
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
