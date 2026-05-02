<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNextVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('store', $this->route('patient'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'has_next_visit' => ['required', 'boolean'],
            'next_visit_date' => ['required_if:has_next_visit,true', 'prohibited_if:has_next_visit,false', 'date'],
            'action' => ['required', 'string', 'in:save,next'],
        ];
    }
}
