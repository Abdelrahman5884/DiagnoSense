<?php

namespace App\Http\Requests\KeyPoint;

use Illuminate\Foundation\Http\FormRequest;

class DeleteKeyInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
}
