<?php

namespace App\Events;

use App\Models\RiderProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RiderProfile $riderProfile)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('ops.riders')];
    }

    public function broadcastAs(): string
    {
        return 'rider.status.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'rider_profile_id' => $this->riderProfile->id,
            'user_id' => $this->riderProfile->user_id,
            'is_online' => $this->riderProfile->is_online,
            'approval_status' => $this->riderProfile->approval_status,
            'updated_at' => optional($this->riderProfile->updated_at)?->toIso8601String(),
        ];
    }
}
