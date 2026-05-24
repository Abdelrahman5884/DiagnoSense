<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteKeyInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patient = $this->route('patient');
        $keyPoint = $this->route('keyPoint');

        return $patient->aiAnalysisResults()->whereHas('keyPoints', function ($query) use ($keyPoint) {
            $query->where('id', $keyPoint->id);
        })->exists();
    }
}
