<?php

namespace App\Events;

use App\Models\SuCo;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewIncidentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $incident;

    /**
     * Create a new event instance.
     */
    public function __construct(SuCo $suCo)
    {
        // Load relations for richer broadcast data
        $suCo->loadMissing(['loaiSuCo', 'mucDoKhanCap', 'nguoiDung']);
        $this->incident = $suCo->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('incidents');
    }

    /**
     * The event name clients subscribe to.
     */
    public function broadcastAs(): string
    {
        return 'NewIncidentCreated';
    }

    /**
     * Data sent to clients.
     */
    public function broadcastWith(): array
    {
        return [
            'incident' => $this->incident,
        ];
    }
}
