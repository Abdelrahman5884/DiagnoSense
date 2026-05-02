<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class DeleteTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('deleteTask', $this->route('task'));
    }
}
