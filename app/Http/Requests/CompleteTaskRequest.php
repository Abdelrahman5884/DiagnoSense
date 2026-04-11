<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = Task::find($this->route('task'));

        $patient = auth()->user()->patient;

        if (! $task || ! $patient) {
            return false;
        }

        return $task->patient_id === $patient->id;
    }

    public function rules(): array
    {
        return [];
    }
}
