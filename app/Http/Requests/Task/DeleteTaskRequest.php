<?php

namespace App\Http\Requests\Task;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Gate::authorize('deleteTask', [Visit::class, $this->route('task')]);

        return true;
    }
}
