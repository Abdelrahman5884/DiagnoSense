<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTaskDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task->visit?->patient_id === $this->user()->patient->id;
    }
}
