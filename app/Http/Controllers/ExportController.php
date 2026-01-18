<?php

namespace App\Http\Controllers;

use App\Jobs\ExportBoardJob;
use App\Models\Board;
use App\Models\ExportJob;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    public function export(Board $board): JsonResponse
    {
        if ($board->user_id !== auth('api')->id()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this board',
                ],
            ], 403);
        }

        $exportJob = ExportJob::create([
            'user_id' => auth('api')->id(),
            'board_id' => $board->id,
            'status' => 'pending',
        ]);

        ExportBoardJob::dispatch($exportJob);

        return response()->json([
            'job_id' => $exportJob->id,
            'status' => $exportJob->status,
            'message' => 'Export job queued successfully',
        ], 202);
    }

    public function status(ExportJob $job): JsonResponse
    {
        if ($job->user_id !== auth('api')->id()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this job',
                ],
            ], 403);
        }

        return response()->json([
            'id' => $job->id,
            'status' => $job->status,
            'progress' => $job->progress,
            'file_path' => $job->file_path,
            'error_message' => $job->error_message,
        ]);
    }

    public function download(ExportJob $job): JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if ($job->user_id !== auth('api')->id()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have access to this job',
                ],
            ], 403);
        }

        if ($job->status !== 'completed' || !$job->file_path) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_READY',
                    'message' => 'Export is not ready for download',
                ],
            ], 400);
        }

        return response()->download(storage_path('app/' . $job->file_path));
    }
}