<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\CompleteTaskRequest;
use App\Http\Resources\TaskResource;
use App\Http\Responses\ApiResponse;
use App\Models\Task;

class TaskController extends Controller
{
    public function index()
    {
        $patient = auth()->user()->patient;

        if (! $patient) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $tasks = $patient->tasks()
            ->with('visit')
            ->latest()
            ->get();

        return ApiResponse::success(
            message: 'Tasks retrieved successfully',
            data: TaskResource::collection($tasks),
            statusCode: 200
        );
    }

    public function show($task)
    {
        $patient = auth()->user()->patient;
        $task = Task::with('visit')->findOrFail($task);

        if ($task->patient_id !== $patient->id) {

            return ApiResponse::error(
                'Unauthorized access',
                null,
                403
            );
        }

        return ApiResponse::success(
            message: 'Task retrieved successfully',
            data: new TaskResource($task),
            statusCode: 200
        );
    }

    public function complete(CompleteTaskRequest $request, $task)
    {
        $task = Task::findOrFail($task);
        $task->update([
            'is_completed' => ! $task->is_completed,
        ]);

        return ApiResponse::success(
            message: $task->is_completed
               ? 'Task marked as completed'
               : 'Task marked as uncompleted',
            data: new TaskResource($task),
            statusCode: 200
        );
    }
}
