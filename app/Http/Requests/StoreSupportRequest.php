<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'in:technical,billing,general'],
            'urgency' => ['required', 'in:low,medium,high'],
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,png,pdf', 'max:5000'],
        ];
    }
}
