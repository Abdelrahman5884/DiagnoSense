<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class DeleteMedicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('deleteMedication', $this->route('medication'));
    }
}
