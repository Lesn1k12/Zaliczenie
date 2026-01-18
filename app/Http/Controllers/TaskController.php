<?php

namespace App\Http\Controllers;

use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TaskUpdated;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Board;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function index(Board $board): JsonResponse
    {
        if ($board->user_id !== auth('api')->id()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this board',
                ],
            ], 403);
        }

        return response()->json($board->tasks);
    }

    public function store(StoreTaskRequest $request, Board $board): JsonResponse
    {
        $task = $board->tasks()->create([
            'user_id' => auth('api')->id(),
            ...$request->validated(),
        ]);

        broadcast(new TaskCreated($task))->toOthers();

        return response()->json($task, 201);
    }

    public function show(Board $board, Task $task): JsonResponse
    {
        if ($task->board_id !== $board->id || $board->user_id !== auth('api')->id()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this task',
                ],
            ], 403);
        }

        return response()->json($task);
    }

    public function update(UpdateTaskRequest $request, Board $board, Task $task): JsonResponse
    {
        if ($task->board_id !== $board->id) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Task not found in this board',
                ],
            ], 404);
        }

        $task->update($request->validated());

        broadcast(new TaskUpdated($task))->toOthers();

        return response()->json($task);
    }

    public function destroy(Board $board, Task $task): JsonResponse
    {
        if ($task->board_id !== $board->id || $task->user_id !== auth('api')->id()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this task',
                ],
            ], 403);
        }

        $taskData = $task->toArray();
        $task->delete();

        broadcast(new TaskDeleted($taskData, $board->id))->toOthers();

        return response()->json(null, 204);
    }
}