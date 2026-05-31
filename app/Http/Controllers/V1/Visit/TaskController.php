<?php

namespace App\Http\Controllers\V1\Visit;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Task\CompleteTaskRequest;
use App\Http\Requests\Task\DeleteTaskRequest;
use App\Http\Requests\Task\GetTaskDetailsRequest;
use App\Http\Requests\Visit\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Visit;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $patient = auth()->user()->patient;
            $tasks = $this->taskService->getTasks($patient);

            return ApiResponse::success(
                message: 'Tasks retrieved successfully',
                data: TaskResource::collection($tasks),
            );
        } catch (\Exception $e) {
            \Log::error('Error fetching tasks: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(message: 'Failed to retrieve tasks, please try again later.', status: 500);
        }
    }

    public function store(StoreTaskRequest $request, Visit $visit): JsonResponse
    {
        try {
            $data = $request->validated();
            $task = $this->taskService->store($visit, $data);
            if (! $task) {
                return ApiResponse::error(message: 'Next visit date is required for tasks.', status: 422);
            }

            return ApiResponse::success(message: 'Task created successfully', data: new TaskResource($task));
        } catch (\Exception $e) {
            \Log::error('Error creating task: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(message: 'Failed to create task, please try again later.', status: 500);
        }
    }

    public function destroy(DeleteTaskRequest $request, Task $task): JsonResponse
    {
        try {
            $this->taskService->delete($task);

            return ApiResponse::success(message: 'Task deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Error deleting task: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(message: 'Failed to delete task, please try again later.', status: 500);
        }
    }

    public function show(GetTaskDetailsRequest $request, Task $task): JsonResponse
    {
        try {
            $task->load('visit');

            return ApiResponse::success(
                message: 'Task details retrieved successfully',
                data: new TaskResource($task),
            );
        } catch (\Exception $e) {
            \Log::error('Error fetching task details: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(message: 'Failed to fetch task details, please try again later.', status: 500);
        }
    }

    public function complete(CompleteTaskRequest $request, Task $task): JsonResponse
    {
        $task->update([
            'is_completed' => ! $task->is_completed,
        ]);
        $task->load('visit');

        return ApiResponse::success(
            message: $task->is_completed
               ? 'Task marked as completed'
               : 'Task marked as uncompleted',
            data: new TaskResource($task),
        );
    }
}
