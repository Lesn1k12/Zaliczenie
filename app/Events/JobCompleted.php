<?php

namespace App\Events;

use App\Models\ExportJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExportJob $exportJob)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->exportJob->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->exportJob->id,
            'board_id' => $this->exportJob->board_id,
            'status' => 'completed',
            'file_path' => $this->exportJob->file_path,
        ];
    }
}