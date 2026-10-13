<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierTrackingRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $deliveryId;

    public string $deliveryNumber;

    public int $courierId;

    public bool $isWatching;

    public ?int $watcherId;

    public ?string $watcherName;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $deliveryId,
        string $deliveryNumber,
        int $courierId,
        bool $isWatching = true,
        ?int $watcherId = null,
        ?string $watcherName = null
    ) {
        $this->deliveryId = $deliveryId;
        $this->deliveryNumber = $deliveryNumber;
        $this->courierId = $courierId;
        $this->isWatching = $isWatching;
        $this->watcherId = $watcherId;
        $this->watcherName = $watcherName;
    }

    /**
     * Broadcast on the courier's private channel.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('courier.'.$this->courierId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'courier.tracking.requested';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->deliveryId,
            'delivery_number' => $this->deliveryNumber,
            'courier_id' => $this->courierId,
            'is_watching' => $this->isWatching,
            'watcher_id' => $this->watcherId,
            'watcher_name' => $this->watcherName,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
