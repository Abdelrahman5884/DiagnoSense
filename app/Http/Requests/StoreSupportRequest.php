<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $currentDoctor = auth()->user()->doctor;
        if (! $currentDoctor) {
            return false;
        }
        return true;
    }

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
            'attachment' => ['nullable', 'file','mimes:jpg,png,pdf', 'max:10240'],
        ];
    }
}
