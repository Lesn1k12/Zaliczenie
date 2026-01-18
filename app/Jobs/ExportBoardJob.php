<?php

namespace App\Jobs;

use App\Events\JobCompleted;
use App\Events\JobFailed;
use App\Events\JobProgress;
use App\Events\JobStarted;
use App\Models\ExportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportBoardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ExportJob $exportJob)
    {
    }

    public function handle(): void
    {
        $this->exportJob->update(['status' => 'processing']);
        broadcast(new JobStarted($this->exportJob));

        try {
            $board = $this->exportJob->board->load('tasks');
            $totalSteps = 5;

            $this->updateProgress(1, $totalSteps);
            sleep(1);

            $csv = "ID,Title,Description,Status,Position,Created At,Updated At\n";

            $this->updateProgress(2, $totalSteps);
            sleep(1);

            foreach ($board->tasks as $index => $task) {
                $csv .= sprintf(
                    "%d,\"%s\",\"%s\",%s,%d,%s,%s\n",
                    $task->id,
                    str_replace('"', '""', $task->title),
                    str_replace('"', '""', $task->description ?? ''),
                    $task->status,
                    $task->position,
                    $task->created_at,
                    $task->updated_at
                );
            }

            $this->updateProgress(3, $totalSteps);
            sleep(1);

            $filename = sprintf('exports/board_%d_%s.csv', $board->id, now()->format('Y-m-d_His'));
            Storage::put($filename, $csv);

            $this->updateProgress(4, $totalSteps);
            sleep(1);

            $this->exportJob->update([
                'status' => 'completed',
                'progress' => 100,
                'file_path' => $filename,
            ]);

            $this->updateProgress(5, $totalSteps);

            broadcast(new JobCompleted($this->exportJob));

        } catch (\Exception $e) {
            $this->exportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            broadcast(new JobFailed($this->exportJob, $e->getMessage()));

            throw $e;
        }
    }

    private function updateProgress(int $step, int $total): void
    {
        $progress = (int) (($step / $total) * 100);
        $this->exportJob->update(['progress' => $progress]);
        broadcast(new JobProgress($this->exportJob, $progress));
    }
}