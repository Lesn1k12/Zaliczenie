<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoardRequest;
use App\Http\Requests\UpdateBoardRequest;
use App\Models\Board;
use Illuminate\Http\JsonResponse;

class BoardController extends Controller
{
    public function index(): JsonResponse
    {
        $boards = auth('api')->user()->boards()->with('tasks')->get();

        return response()->json($boards);
    }

    public function store(StoreBoardRequest $request): JsonResponse
    {
        $board = auth('api')->user()->boards()->create($request->validated());

        return response()->json($board->load('tasks'), 201);
    }

    public function show(Board $board): JsonResponse
    {
        if ($board->user_id !== auth('api')->id()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this board',
                ],
            ], 403);
        }

        return response()->json($board->load('tasks'));
    }

    public function update(UpdateBoardRequest $request, Board $board): JsonResponse
    {
        $board->update($request->validated());

        return response()->json($board->load('tasks'));
    }

    public function destroy(Board $board): JsonResponse
    {
        if ($board->user_id !== auth('api')->id()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this board',
                ],
            ], 403);
        }

        $board->delete();

        return response()->json(null, 204);
    }
}