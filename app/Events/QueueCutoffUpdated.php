<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class QueueCutoffUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public bool $closed;

    public function __construct(bool $closed)
    {
        $this->closed = $closed;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('queue.cutoff')
        ];
    }

    public function broadcastAs(): string
    {
        return 'cutoff.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'closed' => $this->closed,
        ];
    }
}
