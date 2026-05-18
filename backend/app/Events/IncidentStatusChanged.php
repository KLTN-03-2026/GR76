<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $incidentId;
    public string $status;
    public string $triggeredBy; // 'admin' | 'auto'

    public function __construct(int $incidentId, string $status, string $triggeredBy = 'admin')
    {
        $this->incidentId  = $incidentId;
        $this->status      = $status;
        $this->triggeredBy = $triggeredBy;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('incidents');
    }

    public function broadcastAs(): string
    {
        return 'IncidentStatusChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'id_su_co'     => $this->incidentId,
            'trang_thai'   => $this->status,
            'triggered_by' => $this->triggeredBy,
            'updated_at'   => now()->toISOString(),
        ];
    }
}
