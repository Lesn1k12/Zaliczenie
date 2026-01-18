<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $task, public int $boardId)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('board.' . $this->boardId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'task' => $this->task,
        ];
    }
}