<?php

namespace App\Http\Requests\NextVisit;


use Illuminate\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $visit = $this->route('visit');
        Gate::authorize('manage', $visit);
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
            'title' => ['required','string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'next_visit_date' => ['nullable', 'date'],
            'action' => ['required', 'string', 'max:255', 'in:save,save_and_create_another'],
        ];
    }
}
