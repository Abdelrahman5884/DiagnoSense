<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\CompleteTaskRequest;
use App\Http\Requests\DeleteTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Visit;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ){}
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

    public function store(StoreTaskRequest $request, Visit $visit) : JsonResponse
    {
        try{
            $data = $request->validated();
            $task = $this->taskService->store($visit, $data);
            if( !$task) {
                return ApiResponse::error(message: 'Next visit date is required for tasks.', status: 422);
            }
            return ApiResponse::success(message: 'Task created successfully', data: new TaskResource($task));
        }catch (\Exception $e){
            \Log::error('Error creating task: '.$e->getMessage(), ['exception' => $e]);
            return ApiResponse::error(message: 'Failed to create task, please try again later.', status: 500);
        }
    }

    public function destroy(DeleteTaskRequest $request, Task $task): JsonResponse
    {
        try{
            $this->taskService->delete($task);
            return ApiResponse::success(message: 'Task deleted successfully');
        }catch (\Exception $e){
            \Log::error('Error deleting task: '.$e->getMessage(), ['exception' => $e]);
            return ApiResponse::error(message: 'Failed to delete task, please try again later.', status: 500);
        }
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
